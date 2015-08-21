<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011-2015, Sebastian Staudt
 */

namespace SteamCondenser\Community;

use SteamCondenser\Exceptions\SteamCondenserException;

/**
 * This class represents a game available on Steam
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class SteamGame {

    /**
     * @var array
     */
    private static $games = [];

    /**
     * @var int
     */
    private $appId;

    /**
     * @var bool
     */
    private $hasStats;

    /**
     * @var string
     */
    private $iconHash;

    /**
     * @var string
     */
    private $logoHash;

    /**
     * @var string
     */
    private $name;

    /**
     * Creates a new or cached instance of the game specified by the given XML
     * data
     *
     * @param \stdClass $gameData The Web API data of the game
     * @return SteamGame The game instance for the given data
     * @see __construct()
     */
    public static function create(\stdClass $gameData) {
        if(array_key_exists($gameData->appid, self::$games)) {
            return self::$games[$gameData->appid];
        } else {
            return new SteamGame($gameData);
        }
    }

    /**
     * Checks if a game is up-to-date by reading information from a
     * <var>steam.inf</var> file and comparing it using the Web API
     *
     * @param string $path The file system path of the <var>steam.inf</var>
     *        file
     * @return bool <var>true</var> if the game is up-to-date
     * @throws SteamCondenserException if the <var>steam.inf</var> is invalid
     */
    public static function checkSteamInf($path) {
        $steamInf = file_get_contents($path);
        preg_match('/^\s*appID=(\d+)\s*$/im', $steamInf, $appId);
        preg_match('/^\s*PatchVersion=([\d\.]+)\s*$/im', $steamInf, $version);

        if($appId == null || $version == null) {
            throw new SteamCondenserException("The steam.inf file at \"$path\" is invalid.");
        }

        $appId = (int) $appId[1];
        $version = (int) str_replace('.', '', $version[1]);

        return self::checkUpToDate($appId, $version);
    }

    /**
     * Returns whether the given version of the game with the given application
     * ID is up-to-date
     *
     * @param int $appId The application ID of the game to check
     * @param int $version The version to check against the Web API
     * @return boolean <var>true</var> if the given version is up-to-date
     * @throws SteamCondenserException if the Web API request fails
     */
    public static function checkUpToDate($appId, $version) {
        $params = ['appid' => $appId, 'version' => $version];
        $result = WebApi::getJSONObject('ISteamApps', 'UpToDateCheck', 1, $params);
        $result = $result->response;
        if(!$result->success) {
            throw new SteamCondenserException($result->error);
        }
        return $result->up_to_date;
    }

    /**
     * Creates a new instance of a game with the given data and caches it
     *
     * @param \stdClass $gameData The Web API data of the game
     */
    private function __construct(\stdClass $gameData) {
        $this->appId = $gameData->appid;
        if (property_exists($gameData, 'has_community_visible_stats')) {
            $this->hasStats = $gameData->has_community_visible_stats === true;
        }
        $this->iconHash = $gameData->img_icon_url;
        $this->logoHash = $gameData->img_logo_url;
        $this->name = $gameData->name;

        self::$games[$this->appId] = $this;
    }

    /**
     * Returns the Steam application ID of this game
     *
     * @return int The Steam application ID of this game
     */
    public function getAppId() {
        return $this->appId;
    }

    /**
     * Returns the URL for the logo thumbnail image of this game
     *
     * @return string The URL for the game logo thumbnail
     */
    public function getIconUrl() {
        if ($this->iconHash == null) {
            return null;
        } else {
            return "http://media.steampowered.com/steamcommunity/public/images/apps/{$this->appId}/{$this->iconHash}.jpg";
        }
    }

    /**
     * Returns the leaderboard for this game and the given leaderboard ID or
     * name
     *
     * @param mixed $id The ID or name of the leaderboard to return
     * @return GameLeaderboard The matching leaderboard if available
     */
    public function getLeaderboard($id) {
        return GameLeaderboard::getLeaderboard($this->appId, $id);
    }

    /**
     * Returns an array containing all of this game's leaderboards
     *
     * @return array The leaderboards for this game
     */
    public function getLeaderboards() {
        return GameLeaderboard::getLeaderboards($this->appId);
    }

    /**
     * Returns the URL for the logo image of this game
     *
     * @return string The URL for the game logo
     */
    public function getLogoUrl() {
        if ($this->logoHash == null) {
            return null;
        } else {
            return "http://media.steampowered.com/steamcommunity/public/images/apps/{$this->appId}/{$this->logoHash}.jpg";
        }
    }

    /**
     * Returns the URL for the logo thumbnail image of this game
     *
     * @return string The URL for the game logo thumbnail
     */
    public function getLogoThumbnailUrl() {
        if ($this->logoHash == null) {
            return null;
        } else {
            return "http://media.steampowered.com/steamcommunity/public/images/apps/{$this->appId}/{$this->logoHash}_thumb.jpg";
        }
    }

    /**
     * Returns the full name of this game
     *
     * @return string The full name of this game
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the overall number of players currently playing this game
     *
     * @return int The number of players playing this game
     */
    public function getPlayerCount() {
        $params = ['appid' => $this->appId];
        $result = WebApi::getJSONObject('ISteamUserStats', 'GetNumberOfCurrentPlayers', 1, $params);

        return $result->response->player_count;
    }

    /**
     * Returns the URL of this game's page in the Steam Store
     *
     * @return string This game's store page
     */
    public function getStoreUrl() {
        return "http://store.steampowered.com/app/{$this->appId}";
    }

    /**
     * Creates a stats object for the given user and this game
     *
     * @param string $steamId The custom URL or the 64bit Steam ID of the user
     * @return GameStats The stats of this game for the given user
     */
    public function getUserStats($steamId) {
        if(!$this->hasStats()) {
            return null;
        }

        return GameStats::create($steamId, $this->appId);
    }

    /**
     * Returns whether this game has statistics available
     *
     * @return bool <var>true</var> if this game has stats
     */
    public function hasStats() {
        return $this->hasStats;
    }

    /**
     * Returns whether the given version of this game is up-to-date
     *
     * @param int $version The version to check against the Web API
     * @return boolean <var>true</var> if the given version is up-to-date
     */
    public function isUpToDate($version) {
        return self::checkUpToDate($this->appId, $version);
    }

}
