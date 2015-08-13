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

/**
 * The SteamGroup class represents a group in the Steam Community
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class SteamGroup extends XMLData {

    use Cacheable;

    const AVATAR_URL = 'http://media.steampowered.com/steamcommunity/public/images/avatars/%s/%s%s.jpg';

    /**
     * @var string
     */
    private $avatarHash;

    /**
     * @var string
     */
    private $customUrl;

    /**
     * @var int
     */
    private $groupId64;

    /**
     * @var string
     */
    private $headline;

    /**
     * @var int
     */
    private $memberCount;

    /**
     * @var array
     */
    private $members;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $summary;

    public static function initialize() {
        self::cacheableWithIds('customUrl', 'groupId64');
    }

    /**
     * Creates a new <var>SteamGroup</var> instance for the group with the
     * given ID
     *
     * @param string $id The custom URL of the group specified by the group
     *        admin or the 64bit group ID
     */
    public function __construct($id) {
        if(is_numeric($id)) {
            $this->groupId64 = $id;
        } else {
            $this->customUrl = $id;
        }

        $this->members = [];
    }

    /**
     * Returns the URL to this group's full avatar
     *
     * @return string The URL to this group's full avatar
     */
    public function getAvatarFullUrl() {
        return sprintf(self::AVATAR_URL, substr($this->avatarHash, 0, 2), $this->avatarHash, '_full');
    }

    /**
     * Returns the URL to this group's icon avatar
     *
     * @return string The URL to this group's icon avatar
     */
    public function getAvatarIconUrl() {
        return sprintf(self::AVATAR_URL, substr($this->avatarHash, 0, 2), $this->avatarHash, '');
    }

    /**
     * Returns the URL to this group's medium avatar
     *
     * @return string The URL to this group's medium avatar
     */
    public function getAvatarMediumUrl() {
        return sprintf(self::AVATAR_URL, substr($this->avatarHash, 0, 2), $this->avatarHash, '_medium');
    }

    /**
     * Returns the base URL for this group's page
     *
     * This URL is different for groups having a custom URL.
     *
     * @return string The base URL for this group
     */
    public function getBaseUrl() {
        if(empty($this->customUrl)) {
            return "http://steamcommunity.com/gid/{$this->groupId64}";
        } else {
            return "http://steamcommunity.com/groups/{$this->customUrl}";
        }
    }

    /**
     * Returns the custom URL of this group
     *
     * The custom URL is a admin specified unique string that can be used
     * instead of the 64bit SteamID as an identifier for a group.
     *
     * @return string The custom URL of this group
     */
    public function getCustomUrl() {
        return $this->customUrl;
    }

    /**
     * Returns this group's 64bit SteamID
     *
     * @return int This group's 64bit SteamID
     */
    public function getGroupId64() {
        return $this->groupId64;
    }

    /**
     * Returns this group's headline text
     *
     * @return string This group's headline text
     */
    public function getHeadline() {
        return $this->headline;
    }

    /**
     * Returns the number of members this group has
     *
     * If the members have already been fetched the size of the member array is
     * returned. Otherwise the group size is separately fetched without needing
     * multiple requests for big groups.
     *
     * @return int The number of this group's members
     * @see fetchPage()
     */
    public function getMemberCount() {
        if(empty($this->memberCount)) {
            $totalPages = $this->fetchPage(1);
            if($totalPages == 1) {
                $this->fetchTime = time();
            }
        }

        return $this->memberCount;
    }

    /**
     * Returns the members of this group
     *
     * If the members haven't been fetched yet, this is done now.
     *
     * @return array The Steam ID's of the members of this group
     * @see fetch()
     */
    public function getMembers() {
        if(sizeof($this->members) != $this->memberCount) {
            $this->fetch();
        }

        return $this->members;
    }

    /**
     * Returns this group's name
     *
     * @return string This group's name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns this group's summary text
     *
     * @return string This group's summary text
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * Fetches a specific page of the member listing of this group
     *
     * @param int $page The member page to fetch
     * @return int The total number of pages of this group's member listing
     * @see fetch()
     */
    private function fetchPage($page) {
        $url = "{$this->getBaseUrl()}/memberslistxml?p=$page";
        $memberData = $this->getData($url);

        if($page == 1) {
            preg_match('/\/([0-9a-f]+)\.jpg$/', (string) $memberData->groupDetails->avatarIcon, $matches);
            $this->avatarHash = $matches[1];
            $this->customUrl  = (string) $memberData->groupDetails->groupURL;
            $this->groupId64  = (string) $memberData->groupID64;
            $this->name       = (string) $memberData->groupDetails->groupName;
            $this->headline   = (string) $memberData->groupDetails->headline;
            $this->summary    = (string) $memberData->groupDetails->summary;
        }
        $this->memberCount = (int) $memberData->memberCount;
        $totalPages = (int) $memberData->totalPages;

        foreach($memberData->members->steamID64 as $member) {
            array_push($this->members, SteamId::create((string) $member, false));
        }

        return $totalPages;
    }

    /**
     * Loads information about and members of this group
     *
     * This includes the ID, name, headline, summary and avatar and custom URL.
     *
     * This might take several HTTP requests as the Steam Community splits this
     * data over several XML documents if the group has lots of members.
     */
    protected function internalFetch() {
        if(empty($this->memberCount) || sizeof($this->members) == $this->memberCount) {
            $page = 0;
        } else {
            $page = 1;
        }

        do {
            $totalPages = $this->fetchPage(++$page);
        } while($page < $totalPages);

        $this->fetchTime = time();
    }

}

SteamGroup::initialize();
