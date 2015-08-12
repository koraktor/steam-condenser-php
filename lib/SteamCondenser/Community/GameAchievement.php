<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

use SteamCondenser\Community\GameAchievement\Instance;

/**
 * The GameAchievement class represents a specific achievement for a single
 * game and for a single user
 *
 * It also provides the ability to load the global unlock percentages of all
 * achievements of a specific game.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameAchievement {

    /**
     * @var string
     */
    private $apiName;

    /**
     * @var bool
     */
    protected $hidden;

    /**
     * @var string
     */
    private $iconClosedUrl;

    /**
     * @var string
     */
    private $iconOpenUrl;

    /**
     * @var GameStatsSchema
     */
    protected $schema;

    /**
     * Loads the global unlock percentages of all achievements for the given
     * game
     *
     * @param int $appId The unique Steam Application ID of the game (e.g.
     *        <var>440</var> for Team Fortress 2). See
     *        http://developer.valvesoftware.com/wiki/Steam_Application_IDs for
     *        all application IDs
     * @return array The symbolic achievement names with the corresponding
     *         global unlock percentages
     * @throws \SteamCondenser\Exceptions\WebApiException if a request to
     *         Steam's Web API fails
     */
    public static function getGlobalPercentages($appId) {
        $params = ['gameid' => $appId];
        $data = WebApi::getJSONObject('ISteamUserStats', 'GetGlobalAchievementPercentagesForApp', 2, $params);

        $percentages = [];
        foreach($data->achievementpercentages->achievements as $achievementData) {
            $percentages[$achievementData->name] = (float) $achievementData->percent;
        }

        return $percentages;
    }

    /**
     * Creates the achievement with the given name for the given user and game
     * and achievement data
     *
     * @param GameStatsSchema $schema The game this achievement belongs to
     * @param \stdClass $data The achievement data extracted from the game schema
     */
    public function __construct(GameStatsSchema $schema, $data) {
        $this->apiName = $data->name;
        $this->schema = $schema;
        $this->hidden = $data->hidden == 1;
        $this->iconClosedUrl = $data->icon;
        $this->iconOpenUrl = $data->icongray;
    }

    /**
     * Returns the symbolic API name of this achievement
     *
     * @return string The API name of this achievement
     */
    public function getApiName() {
        return $this->apiName;
    }

    /**
     * Returns the url for the closed icon of this achievement
     *
     * @return string The url of the closed achievement icon
     */
    public function getIconClosedUrl() {
        return $this->iconClosedUrl;
    }

    /**
     * Returns the url for the open icon of this achievement
     *
     * @return string The url of the open achievement icon
     */
    public function getIconOpenUrl() {
        return $this->iconOpenUrl;
    }

    /**
     * Returns an instance of this achievement for the given user and the given
     * unlock state
     *
     * @param SteamId $user The user the instance should be returned for
     * @param bool $unlocked The state of the achievement for this user
     * @return Instance The achievement instance for this user
     */
    public function getInstance(SteamId $user, $unlocked) {
        return new Instance($this, $user, $unlocked);
    }

    /**
     * Returns the stats schema of the game this achievement belongs to
     *
     * @return GameStatsSchema The stats schema of game this achievement
     *         belongs to
     */
    public function getSchema() {
        return $this->schema;
    }

    /**
     * Returns whether this achievement is hidden
     *
     * @return bool <var>true</var> if this achievement is hidden
     */
    public function isHidden() {
        return $this->hidden;
    }

}
