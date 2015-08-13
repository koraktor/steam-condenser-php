<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

class GameStatsSchema {

    use Cacheable;

    /**
     * @var string
     */
    private static $defaultLanguage = 'english';

    /**
     * @var GameAchievement[]
     */
    protected $achievements;

    /**
     * @var array
     */
    protected $achievementTranslations;

    /**
     * @var int
     */
    protected $appId;

    /**
     * @var string
     */
    protected $appName;

    /**
     * @var int
     */
    protected $appVersion;

    /**
     * @var GameStatsDatum[]
     */
    protected $data;

    /**
     * @return array
     */
    protected $datumNames;

    public static function getDefaultLanguage() {
        return self::$defaultLanguage;
    }

    public static function initialize() {
        self::cacheableWithIds('appId');
    }

    public static function setDefaultLanguage($defaultLanguage) {
        self::$defaultLanguage = $defaultLanguage;
    }

    protected function __construct($appId) {
        $this->appId = $appId;
    }

    public function __toString() {
        return sprintf("%s{%d \"%s\" (%d)}", get_class(), $this->appId, $this->appName, $this->appVersion);
    }

    protected function addLanguage($language) {
        $schema = $this->fetchLanguage($language);

        $initial = false;
        if (empty($this->data)) {
            $this->appName = $schema->gameName;
            $this->appVersion = $schema->gameVersion;
            $this->data = [];
            $initial = true;
        }

        $this->datumNames[$language] = [];
        foreach ($schema->availableGameStats->stats as $data) {
            $this->datumNames[$language][$data->name] = $data->displayName;

            if ($initial) {
                $this->data[$data->name] = new GameStatsDatum($this, $data);
            }
        }

        $this->achievementTranslations[$language] = [];
        foreach ($schema->availableGameStats->achievements as $data) {
            $this->achievementTranslations[$language][$data->name] = [
                'description' => $data->description,
                'name' => $data->displayName
            ];

            if ($initial) {
                $this->achievements[$data->name] = new GameAchievement($this, $data);
            }
        }
    }

    /**
     * @param $apiName
     * @return GameAchievement
     */
    public function getAchievement($apiName) {
        return $this->achievements[$apiName];
    }

    /**
     * Returns all achievements available for the game
     *
     * @return GameAchievement[] The achievements for the game
     */
    public function getAchievements() {
        return $this->achievements;
    }

    /**
     * @param string $language
     * @return mixed
     */
    public function getAchievementTranslations($language) {
        if (!array_key_exists($language, $this->achievementTranslations)) {
            $this->addLanguage($language);
        }

        return $this->achievementTranslations[$language];
    }

    /**
     * @return int
     */
    public function getAppId() {
        return $this->appId;
    }

    /**
     * @param $apiName
     * @return GameStatsDatum
     */
    public function getDatum($apiName) {
        return $this->data[$apiName];
    }

    public function getDatumNames($language) {
        if (!array_key_exists($language, $this->datumNames)) {
            $this->addLanguage($language);
        }

        return $this->datumNames[$language];
    }

    protected function fetchLanguage($language) {
        $params = [ 'appid' => $this->appId, 'l' => $language ];
        $data = WebApi::getJSONObject('ISteamUserStats', 'GetSchemaForGame', 2, $params);

        return $data->game;
    }

    protected function internalFetch() {
        $this->addLanguage(self::getDefaultLanguage());
    }

}

GameStatsSchema::initialize();
