<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011, Nicholas Hastings
 *               2011-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

use SteamCondenser\Exceptions\SteamCondenserException;

/**
 * The GameLeaderboard class represents a single leaderboard for a specific
 * game
 *
 * @author     Nicholas Hastings
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameLeaderboard {

    const LEADERBOARD_DISPLAY_TYPE_NONE         = 0;
    const LEADERBOARD_DISPLAY_TYPE_NUMERIC      = 1;
    const LEADERBOARD_DISPLAY_TYPE_SECONDS      = 2;
    const LEADERBOARD_DISPLAY_TYPE_MILLISECONDS = 3;

    const LEADERBOARD_SORT_METHOD_NONE = 0;
    const LEADERBOARD_SORT_METHOD_ASC  = 1;
    const LEADERBOARD_SORT_METHOD_DESC = 2;

    /**
     * @var array
     */
    private static $leaderboards = [];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $entryCount;

    /**
     * @var int
     */
    protected $sortMethod;

    /**
     * @var int
     */
    protected $displayType;

    /**
     * Returns the leaderboard for the given game and leaderboard ID or name
     *
     * @param string $gameName The short name of the game
     * @param mixed $id The ID or name of the leaderboard to return
     * @return GameLeaderboard The matching leaderboard if available
     */
    public static function getLeaderboard($gameName, $id) {
        $leaderboards = self::getLeaderboards($gameName);

        if (is_int($id)) {
            if (array_key_exists($id, $leaderboards)) {
                return $leaderboards[$id];
            } else {
                return null;
            }
        } else {
            foreach(array_values($leaderboards) as $board) {
                if($board->getName() == $id) {
                    return $board;
                }
            }
        }
    }

    /**
     * Returns an array containing all of a game's leaderboards
     *
     * @param string $gameName The name of the game
     * @return GameLeaderboard[] The leaderboards for this game
     */
    public static function getLeaderboards($gameName) {
        if(!array_key_exists($gameName, self::$leaderboards)) {
            self::loadLeaderboards($gameName);
        }

        return self::$leaderboards[$gameName];
    }

    /**
     * Loads the leaderboards of the specified games into the cache
     *
     * @param string $gameName The short name of the game
     * @throws SteamCondenserException if an error occurs while fetching the
     *         leaderboards
     */
    private static function loadLeaderboards($gameName) {
        $url = "http://steamcommunity.com/stats/$gameName/leaderboards/?xml=1";
        $boardsData = new \SimpleXMLElement(file_get_contents($url));

        if(!empty($boardsData->error)) {
            throw new SteamCondenserException((string) $boardsData->error);
        }

        self::$leaderboards[$gameName] = [];
        foreach($boardsData->leaderboard as $boardData) {
            $leaderboard = new GameLeaderboard($boardData);
            self::$leaderboards[$gameName][$leaderboard->getId()] = $leaderboard;
        }
    }

    /**
     * Creates a new leaderboard instance with the given XML data
     *
     * @param \SimpleXMLElement $boardData The XML data of the leaderboard
     */
    private function __construct(\SimpleXMLElement $boardData) {
        $this->url         = (string) $boardData->url;
        $this->id          = (int)    $boardData->lbid;
        $this->name        = (string) $boardData->name;
        $this->entryCount  = (int)    $boardData->entries;
        $this->sortMethod  = (int)    $boardData->sortmethod;
        $this->displayType = (int)    $boardData->displaytype;
    }

    /**
     * Returns the name of the leaderboard
     *
     * @return string The name of the leaderboard
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the ID of the leaderboard
     *
     * @return int The ID of the leaderboard
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the number of entries on this leaderboard
     *
     * @return int The number of entries on this leaderboard
     */
    public function getEntryCount() {
        return $this->entryCount;
    }

    /**
     * Returns the method that is used to sort the entries on the leaderboard
     *
     * @return int The sort method
     */
    public function getSortMethod() {
        return $this->sortMethod;
    }

    /**
     * Returns the display type of the scores on this leaderboard
     *
     * @return int The display type of the scores
     */
    public function getDisplayType() {
        return $this->displayType;
    }

    /**
     * Returns the entry on this leaderboard for the user with the given
     * SteamID
     *
     * @param mixed $steamId The 64bit SteamID or the <var>SteamId</var> object
     *        of the user
     * @return GameLeaderboardEntry The entry of the user if available
     * @throws SteamCondenserException if the XML data is marked as erroneous
     */
    public function getEntryForSteamId($steamId) {
        if (is_object($steamId)) {
            $id = $steamId->getSteamId64();
        } else {
            $id = $steamId;
        }

        $xml = $this->loadDataForSteamId($id);

        foreach($xml->entries->entry as $entryData) {
            if($entryData->steamid == $id) {
                return new GameLeaderboardEntry($entryData, $this);
            }
        }

        return null;
    }

    /**
     * Returns an array of entries on this leaderboard for the user with the
     * given SteamID and his/her friends
     *
     * @param string|SteamId $steamId The 64bit SteamID or the
     *        <var>SteamId</var> object of the user
     * @return array The entries of the user and his/her friends
     * @throws SteamCondenserException if the XML data is marked as erroneous
     */
    public function getEntryForSteamIdFriends($steamId) {
        $xml = $this->loadDataForSteamId($steamId);

        return $this->parseEntries($xml);
    }

    /**
     * Returns the entries on this leaderboard for a given rank range
     *
     * The range is inclusive and a maximum of 5001 entries can be returned in
     * a single request.
     *
     * @param int $first The first entry to return from the leaderboard
     * @param int $last The last entry to return from the leaderboard
     * @return GameLeaderboardEntry[] The entries that match the given rank range
     * @throws SteamCondenserException if the XML data is marked as erroneous
     *         or the range is incorrect
     */
    public function getEntryRange($first, $last) {
        if($last < $first) {
            throw new SteamCondenserException('First entry must be prior to last entry for leaderboard entry lookup.');
        }
        if(($last - $first) > 5000) {
            throw new SteamCondenserException('Leaderboard entry lookup is currently limited to a maximum of 5001 entries per request.');
        }

        $xml = $this->loadData(['start' => $first, 'end' => $last]);

        return $this->parseEntries($xml);
    }

    /**
     * Loads leaderboard data for the given parameters
     *
     * @param array $params The parameters for the leaderboard data
     * @return \SimpleXMLElement The requested XML Data
     * @throws SteamCondenserException if the XML data is marked as erroneous
     */
    protected function loadData(array $params) {
        $url = $this->url;
        if (!empty($params)) {
            $url_params = [];
            foreach($params as $k => $v) {
                $url_params[] = "$k=$v";
            }
            $url .= '&' . join('&', $url_params);
        }

        $xml = new \SimpleXMLElement(file_get_contents($url));

        if (!empty($xml->error)) {
            throw new SteamCondenserException((string) $xml->error);
        }

        return $xml;
    }

    /**
     * Loads leaderboard data for the given user
     *
     * @param string|SteamId $steamId The 64bit SteamID or the
     *        <var>SteamId</var> object of the user
     * @return \SimpleXMLElement The XML data for the given user
     * @throws SteamCondenserException if the XML data is marked as erroneous
     */
    protected function loadDataForSteamId($steamId) {
        if (is_object($steamId)) {
            $id = $steamId->getSteamId64();
        } else {
            $id = $steamId;
        }

        return $this->loadData(['steamid' => $id]);
    }

    /**
     * Parses leaderboard entries from the given XML data
     *
     * @param \SimpleXMLElement $xml The XML data to parse
     * @return GameLeaderboardEntry The leaderboard entries for the given data
     */
    protected function parseEntries(\SimpleXMLElement $xml) {
        $entries = [];
        foreach($xml->entries->entry as $entryData) {
            $rank = (int) $entryData->rank;
            $entries[$rank] = new GameLeaderboardEntry($entryData, $this);
        }

        return $entries;
    }

}
