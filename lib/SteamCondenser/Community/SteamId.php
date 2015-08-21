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

use SteamCondenser\Exceptions\SteamCondenserException;

/**
 * The SteamId class represents a Steam Community profile (also called Steam
 * ID)
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class SteamId extends XMLData {

    use Cacheable;

    /**
     * @var string
     */
    private $customUrl;

    /**
     * @var array
     */
    private $friends;

    /**
     * @var array
     */
    private $games;

    /**
     * @var array
     */
    private $groups;

    /**
     * @var bool
     */
    private $limited;

    /**
     * @var string
     */
    private $nickname;

    /**
     * @var array
     */
    private $playtimes;

    /**
     * @var string
     */
    private $steamId64;

    /**
     * @var string
     */
    private $tradeBanState;

    /**
     * Converts a 64bit numeric SteamID as used by the Steam Community to a
     * SteamID as reported by game servers
     *
     * @param string $communityId The SteamID string as used by the Steam
     *        Community
     * @return string The converted SteamID, like <var>STEAM_0:0:12345</var>
     * @throws SteamCondenserException if the community ID is to small
     */
    public static function convertCommunityIdToSteamId($communityId) {
        $steamId1 = bcmod($communityId, 2);
        $steamId2 = bcsub($communityId, 76561197960265728);

        if ($steamId2 <= 0) {
            throw new SteamCondenserException("SteamID $communityId is too small.");
        }

        $steamId2 = ($steamId2 - $steamId1) / 2;

        return "STEAM_0:$steamId1:$steamId2";
    }

    /**
     * Converts a 64bit numeric SteamID as used by the Steam Community to the
     * modern SteamID format (also known as SteamID 3)
     *
     * @param string $communityId The SteamID string as used by the Steam
     *        Community
     * @return string The converted SteamID, like `[U:1:12345]`
     * @throws SteamCondenserException if the community ID is to small
     */
    public static function convertCommunityIdToSteamId3($communityId) {
        // Only the public universe (1) is supported
        $steamId1 = 1;
        $steamId2 = bcsub($communityId, 76561197960265728);

        if ($steamId2 <= 0) {
            throw new SteamCondenserException("SteamID $communityId is too small.");
        }

        return "[U:$steamId1:$steamId2]";
    }

    /**
     * Converts a SteamID as reported by game servers to a 64bit numeric
     * SteamID as used by the Steam Community
     *
     * @param string $steamId The SteamID string as used on servers, like
     *        <var>STEAM_0:0:12345</var>
     * @return string The converted 64bit numeric SteamID
     * @throws SteamCondenserException if the SteamID doesn't have the correct
     *         format
     */
    public static function convertSteamIdToCommunityId($steamId) {
        if($steamId == 'STEAM_ID_LAN' || $steamId == 'BOT') {
            throw new SteamCondenserException("Cannot convert SteamID \"$steamId\" to a community ID.");
        }
        if (preg_match('/^STEAM_[0-1]:[0-1]:[0-9]+$/', $steamId)) {
            $steamId = explode(':', substr($steamId, 8));
            return bcadd($steamId[0] + $steamId[1] * 2, 76561197960265728);
        } elseif (preg_match('/^\[U:[0-1]:[0-9]+\]$/', $steamId)) {
            $steamId = explode(':', substr($steamId, 3, strlen($steamId) - 1));
            return bcadd($steamId[0] + $steamId[1], 76561197960265727);
        } else {
            throw new SteamCondenserException("SteamID \"$steamId\" doesn't have the correct format.");
        }
    }

    /**
     * Creates a new <var>SteamId</var> instance using a SteamID as used on
     * servers
     *
     * The SteamID from the server is converted into a 64bit numeric SteamID
     * first before this is used to retrieve the corresponding Steam Community
     * profile.
     *
     * @param string $steamId The SteamID string as used on servers, like
     *        <var>STEAM_0:0:12345</var>
     * @return SteamId The <var>SteamId</var> instance belonging to the given
     *         SteamID
     * @see convertSteamIdToCommunityId()
     * @see __construct()
     */
    public static function getFromSteamId($steamId) {
        return SteamId::create(self::convertSteamIdToCommunityId($steamId));
    }

    public static function initialize() {
        self::cacheableWithIds('customUrl', 'steamId64');
    }

    /**
     * Resolves a vanity URL of a Steam Community profile to a 64bit numeric
     * SteamID
     *
     * @param string $vanityUrl The vanity URL of a Steam Community profile
     * @return string The 64bit SteamID for the given vanity URL
     * @throws WebApiException if the request to Steam's Web API fails
     */
    public static function resolveVanityUrl($vanityUrl) {
        $params = ['vanityurl' => $vanityUrl];

        $result = WebApi::getJSONObject('ISteamUser', 'ResolveVanityURL', 1, $params);
        $result = $result->response;

        if ($result->success != 1) {
            return null;
        }

        return $result->steamid;
    }

    /**
     * Creates a new <var>SteamId</var> instance for the given ID
     *
     * @param string $id The custom URL of the group specified by the player
     *        or the 64bit SteamID
     * @throws SteamCondenserException if the Steam ID data is not available,
     *         e.g. when it is private
     */
    public function __construct($id) {
        if(is_numeric($id)) {
            $this->steamId64 = $id;
        } else {
            $this->customUrl = strtolower($id);
        }
    }

    /**
     * Fetches the friends of this user
     *
     * This creates a new <var>SteamId</var> instance for each of the friends
     * without fetching their data.
     *
     * @return SteamId[]
     * @see getFriends()
     * @throws SteamCondenserException if an error occurs while parsing the
     *         data
    */
    public function fetchFriends() {
        $friendsData = $this->getData($this->getBaseUrl() . '/friends?xml=1');
        $this->friends = [];
        foreach($friendsData->friends->friend as $friend) {
            $this->friends[] = self::create((string) $friend, false);
        }

        return $this->friends;
    }

    /**
     * Fetches the games this user owns
     *
     * @see getGames()
     * @throws SteamCondenserException if an error occurs while parsing the
     *         data
     */
    public function fetchGames() {
        $params = [
                'steamid' => $this->getSteamId64(),
                'include_appinfo' => 1,
                'include_played_free_games' => 1
        ];
        $gamesData = WebApi::getJSONObject('IPlayerService', 'GetOwnedGames', 1, $params);

        foreach ($gamesData->response->games as $gameData) {
            $game = SteamGame::create($gameData);
            $this->games[$game->getAppId()] = $game;
            if (property_exists($gameData, 'playtime_2weeks')) {
                $recent = $gameData->playtime_2weeks;
            } else {
                $recent = 0;
            }
            $total = $gameData->playtime_forever;
            $this->playtimes[$game->getAppId()] = [$recent, $total];
        }

        return $this->games;
    }

    /**
     * Fetches the groups this user is member of
     *
     * Uses the ISteamUser/GetUserGroupList interface.
     *
     * @return SteamGroup[] The groups of this user
     * @see getGroups()
     */
    public function fetchGroups() {
        $params = ['steamid' => $this->getSteamId64()];
        $result = WebApi::getJSONObject('ISteamUser', 'GetUserGroupList', 1, $params);

        $this->groups = [];
        foreach ($result->response->groups as $groupData) {
            $this->groups[] = SteamGroup::create($groupData->gid, false);
        }

        return $this->groups;
    }

    /**
     * Returns the base URL for this Steam ID
     *
     * This URL is different for Steam IDs having a custom URL.
     *
     * @return string The base URL for this SteamID
     */
    protected function getBaseUrl() {
        if(empty($this->customUrl)) {
            return "http://steamcommunity.com/profiles/{$this->steamId64}";
        } else {
            return "http://steamcommunity.com/id/{$this->customUrl}";
        }
    }

    /**
     * Returns the custom URL of this Steam ID
     *
     * The custom URL is a user specified unique string that can be used
     * instead of the 64bit SteamID as an identifier for a Steam ID.
     *
     * <strong>Note:</strong> The custom URL is not necessarily the same as the
     * user's nickname.
     *
     * @return string The custom URL of this Steam ID
     */
    public function getCustomUrl() {
        return $this->customUrl;
    }

    /**
     * Returns the Steam Community friends of this user
     *
     * If the friends haven't been fetched yet, this is done now.
     *
     * @return SteamId[] The friends of this user
     * @see fetchFriends()
     */
    public function getFriends() {
        return $this->friends ?: $this->fetchFriends();
    }

    /**
     * Returns the URL of the full-sized version of this user's avatar
     *
     * @return string The URL of the full-sized avatar
     */
    public function getFullAvatarUrl() {
        return $this->imageUrl . '_full.jpg';
    }

    /**
     * Returns the games this user owns
     *
     * The keys of the hash are the games' application IDs and the values are
     * the corresponding game instances.
     *
     * If the friends haven't been fetched yet, this is done now.
     *
     * @return SteamGame[] The games this user owns
     * @see fetchGames()
     */
    public function getGames() {
        return $this->games ?: $this->fetchGames();
    }

    /**
     * Returns the stats for the given game for the owner of this SteamID
     *
     * @param int $appId The application ID of the game stats should be fetched
     *        for
     * @return GameStats The statistics for the game with the given name
     * @throws SteamCondenserException if the user does not own this game or it
     *         does not have any stats
     */
    public function getGameStats($appId) {
        return GameStats::create($this->getId(), $appId);
    }

    /**
     * Returns all groups where this user is a member
     *
     * @return SteamGroup[] The groups of this user
     * @see fetchGroups()
     */
    public function getGroups() {
        return $this->groups ?: $this->fetchGroups();
    }

    /**
     * Returns the URL of the icon version of this user's avatar
     *
     * @return string The URL of the icon-sized avatar
     */
    public function getIconAvatarUrl() {
        return $this->imageUrl . '.jpg';
    }

    /**
     * Returns a unique identifier for this Steam ID
     *
     * This is either the 64bit numeric SteamID or custom URL
     *
     * @return string The 64bit numeric SteamID or the custom URL
     */
    public function getId() {
        return $this->customUrl ?: $this->steamId64;
    }

    /**
     * Returns the URL of the medium-sized version of this user's avatar
     *
     * @return string The URL of the medium-sized avatar
     */
    public function getMediumAvatarUrl() {
        return $this->imageUrl . '_medium.jpg';
    }

    /**
     * Returns the Steam nickname of the user
     *
     * @return string The Steam nickname of the user
     */
    public function getNickname() {
        return $this->nickname;
    }

    /**
     * Returns this user's 64bit SteamID
     *
     * If the SteamID is not known yet it is resolved from the vanity URL.
     *
     * @return string This user's 64bit SteamID
     * @see resolveVanityUrl
     */
    public function getSteamId64() {
        if (empty($this->steamId64)) {
            $this->steamId64 = self::resolveVanityUrl($this->customUrl);
        }

        return $this->steamId64;
    }

    /**
     * Returns the time in minutes this user has played this game in the last
     * two weeks
     *
     * @param int $appId The application ID of the game
     * @return int The number of minutes this user played the given game in the
     *         last two weeks
     */
    public function getRecentPlaytime($appId) {
        if (empty($this->playtimes)) {
            $this->fetchGames();
        }

        return $this->playtimes[$appId][0];
    }

    /**
     * Returns the total time in minutes this user has played this game
     *
     * @param int $appId The application ID of the game
     * @return int The total number of minutes this user played the given game
     */
    public function getTotalPlaytime($appId) {
        if (empty($this->playtimes)) {
            $this->fetchGames();
        }

        return $this->playtimes[$appId][1];
    }

    /**
     * Returns this user's ban state in Steam's trading system
     *
     * @return string This user's trading ban state
     */
    public function getTradeBanState() {
        return $this->tradeBanState;
    }

    /**
     * Fetchs data from the Steam Community by querying the XML version of the
     * profile specified by the ID of this Steam ID
     *
     * @throws SteamCondenserException if the Steam ID data is not available,
     *         e.g. when it is private, or when it cannot be parsed
     */
    protected function internalFetch() {
        $profile = $this->getData($this->getBaseUrl() . '?xml=1');

        if(!empty($profile->error)) {
            throw new SteamCondenserException((string) $profile->error);
        }

        if(!empty($profile->privacyMessage)) {
            throw new SteamCondenserException((string) $profile->privacyMessage);
        }

        $this->nickname      = htmlspecialchars_decode((string) $profile->steamID);
        $this->steamId64     = (string) $profile->steamID64;
        $this->limited       = (bool)(int) $profile->isLimitedAccount;
        $this->tradeBanState = (string) $profile->tradeBanState;
        $this->vacBanned     = (bool)(int) $profile->vacBanned;

        $this->imageUrl = substr((string) $profile->avatarIcon, 0, -4);
        $this->onlineState = (string) $profile->onlineState;
        $this->privacyState = (string) $profile->privacyState;
        $this->stateMessage = (string) $profile->stateMessage;
        $this->visibilityState = (int) $profile->visibilityState;

        if($this->isPublic()) {
            $this->customUrl = strtolower((string) $profile->customURL);
            $this->hoursPlayed = (float) $profile->hoursPlayed2Wk;
            $this->location = (string) $profile->location;
            $this->memberSince = (string) $profile->memberSince;
            $this->realName = htmlspecialchars_decode((string) $profile->realname);
            $this->summary = htmlspecialchars_decode((string) $profile->summary);
        }
    }

    /**
     * Returns whether the owner of this Steam ID is VAC banned
     *
     * @return bool <var>true</var> if the user has been banned by VAC
     */
    public function isBanned() {
        return $this->vacBanned;
    }

    /**
     * Returns whether the owner of this Steam ID is playing a game
     *
     * @return bool <var>true</var> if the user is in-game
     */
    public function isInGame() {
        return $this->onlineState == 'in-game';
    }

    /**
     * Returns whether this Steam account is limited
     *
     * @return bool <var>true</var> if this account is limited
     */
    public function isLimited() {
        return $this->limited;
    }

    /**
     * Returns whether the owner of this Steam ID is currently logged into
     * Steam
     *
     * @return bool <var>true</var> if the user is online
     */
    public function isOnline() {
        return ($this->onlineState == 'online') || ($this->onlineState == 'in-game');
    }

    /**
     * Returns whether this Steam ID is publicly accessible
     *
     * @return bool <var>true</var> if this Steam ID is public
     */
    public function isPublic() {
        return $this->privacyState == 'public';
    }

}

SteamId::initialize();
