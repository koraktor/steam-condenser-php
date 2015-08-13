<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2014, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

use SteamCondenser\Exceptions\SteamCondenserException;
use SteamCondenser\Community\AlienSwarmStats;

/**
 * This class represents the game statistics for a single user and a specific
 * game
 *
 * It is subclassed for individual games if the games provide special
 * statistics that are unique to this game.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameStats {

    use Cacheable;

    /**
     * @var GameAchievement[]
     */
    protected $achievements;

    /**
     * @var GameStatsSchema
     */
    protected $schema;

    /**
     * @var string
     */
    protected $steamId64;

    /**
     * @var SteamId
     */
    protected $user;

    /**
     * @var GameStatsDatum[]
     */
    protected $values;

    public static function initialize() {
        self::cacheableWithIds('appId', 'steamId64');
    }

    /**
     * Creates a <var>GameStats</var> object and fetches data from the Steam
     * Community for the given user and game
     *
     * @param string $userId The custom URL or the 64bit Steam ID of the user
     * @param string $appId The app ID or friendly name of the game
     * @throws SteamCondenserException if the stats cannot be fetched
     */
    protected function __construct($userId, $appId) {
        $this->schema = GameStatsSchema::create($appId);
        $this->user = SteamId::create($userId, false);

        $this->appId = $appId;
        $this->steamId64 = $this->user->getSteamId64();

        $params = [ 'appid' => $this->schema->getAppId(), 'steamid' => $this->steamId64 ];
        $data = WebApi::getJSONObject('ISteamUserStats', 'GetUserStatsForGame', 2, $params);

        $this->achievements = [];
        foreach ($data->playerstats->achievements as $achievement) {
            $apiName = $achievement->name;
            $unlocked = $achievement->achieved == 1;
            $this->achievements[] = $this->schema->getAchievement($apiName)->getInstance($this->user, $unlocked);
        }

        $this->values = [];
        foreach ($data->playerstats->stats as $datum) {
            $this->values[] = $this->schema->getDatum($datum->name)->getInstance($this->user, $datum->value);
        }
    }

    /**
     * Returns the number of achievements done by this player
     *
     * @return int The number of achievements completed
     * @see getAchievements()
     */
    public function getAchievementsDone() {
        return sizeof($this->achievements);
    }

    /**
     * Returns the percentage of achievements done by this player
     *
     * @return float The percentage of achievements completed
     * @see #getAchievementsDone
     */
    public function getAchievementsPercentage() {
        return sizeof($this->achievements) / sizeof($this->schema->getAchievements());
    }

    protected function internalFetch() {
    }

}

GameStats::initialize();
