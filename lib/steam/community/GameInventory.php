<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011-2013, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/GameItem.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/GameItemSchema.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/SteamId.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/WebApi.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/dota2/DotA2BetaInventory.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/dota2/DotA2Inventory.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/portal2/Portal2Inventory.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2BetaInventory.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Inventory.php';

/**
 * Provides basic functionality to represent an inventory of player in a game
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameInventory {

    const ITEM_CLASS = 'GameItem';

    /**
     * @var array
     */
    public static $cache = array();

    /**
     * @var string
     */
    public static $schemaLanguage = 'en';

    /**
     * @var int
     */
    protected $appId;

    /**
     * @var int
     */
    protected $fetchDate;

    /**
     * @var GameItemSchema
     */
    protected $itemSchema;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $preliminaryItems;

    /**
     * @var string
     */
    protected $steamId64;

    /**
     * @var SteamId
     */
    protected $user;

    /**
     * Clears the inventory cache
     */
    public static function clearCache() {
        self::$cache = array();
    }

    /**
     * This checks the cache for an existing inventory. If it exists it is
     * returned. Otherwise a new inventory is created.
     *
     * @param int $appId The application ID of the game
     * @param string $steamId The 64bit Steam ID or the vanity URL of the user
     * @param bool $fetchNow Whether the data should be fetched now
     * @param bool $bypassCache Whether the cache should be bypassed
     * @return GameInventory
     */
    public static function create($appId, $steamId, $fetchNow = true, $bypassCache = false) {
        if (is_numeric($steamId)) {
            $steamId64 = $steamId;
        } else {
            $steamId64 = SteamId::resolveVanityUrl($steamId);
        }

        if (self::isCached($appId, $steamId64) && !$bypassCache) {
            $inventory = self::$cache[$appId][$steamId64];
            if ($fetchNow && !$inventory->isFetched()) {
                $inventory->fetch();
            }
            return $inventory;
        } else {
            switch ($appId) {
                case Dota2BetaInventory::APP_ID:
                    $inventoryClass = 'Dota2BetaInventory';
                    break;
                case Dota2Inventory::APP_ID:
                    $inventoryClass = 'Dota2Inventory';
                    break;
                case Portal2Inventory::APP_ID:
                    $inventoryClass = 'Portal2Inventory';
                    break;
                case TF2BetaInventory::APP_ID:
                    $inventoryClass = 'TF2BetaInventory';
                    break;
                case TF2Inventory::APP_ID:
                    $inventoryClass = 'TF2Inventory';
                    break;
                default:
                    $inventoryClass = 'GameInventory';
            }

            return new $inventoryClass($appId, $steamId64, $fetchNow);
        }
    }

    /**
     * Returns whether the requested inventory is already cached
     *
     * @param int $appId The application ID of the game
     * @param string $steamId64 The 64bit Steam ID of the user
     * @return bool <var>true</var> if the inventory of the given user for the
     *         given game is already cached
     */
    public static function isCached($appId, $steamId64) {
        return array_key_exists($appId, self::$cache) &&
               array_key_exists($steamId64, self::$cache[$appId]);
    }

    /**
     * Sets the language the schema should be fetched in (default is:
     * <var>'en'</var>)
     *
     * @param string $language The language code for the language item
     *        descriptions should be fetched in
     */
    public static function setSchemaLanguage($language) {
        self::$schemaLanguage = $language;
    }

    /**
     * Creates a new inventory object for the given user. This calls
     * <var>fetch()</var> to update the data and create the GameItem instances
     * contained in this player's inventory
     *
     * @param int $appId The application ID of the game
     * @param string $steamId64 The 64bit Steam ID of the user
     * @param bool $fetchNow Whether the data should be fetched now
     * @throws WebApiException on Web API errors
     */
    protected function __construct($appId, $steamId64, $fetchNow = true) {
        $this->appId = $appId;
        $this->steamId64 = $steamId64;
        $this->user = SteamId::create($steamId64, false);

        if ($fetchNow) {
            $this->fetch();
        }

        $this->cache();

        array_keys(self::$cache);
        array_keys(self::$cache[$appId]);
    }

    /**
     * Saves this inventory in the cache
     *
     * @return bool <var>false</var> if this inventory is already cached
     */
    public function cache() {
        if (array_key_exists($this->appId, self::$cache) &&
            array_key_exists($this->steamId64, self::$cache[$this->appId])) {
            return false;
        }

        self::$cache[$this->appId][$this->steamId64] = $this;

        return true;
    }

    /**
     * Updates the contents of the backpack using Steam Web API
     */
    public function fetch() {
        $params = array('SteamID' => $this->steamId64);
        $result = WebApi::getJSONData("IEconItems_{$this->getAppId()}", 'GetPlayerItems', 1, $params);

        $this->items = array();
        $this->preliminaryItems = array();
        foreach ($result->items as $itemData) {
            if ($itemData != null) {
                $inventoryClass = new ReflectionClass(get_class($this));
                $itemClass = $inventoryClass->getConstant('ITEM_CLASS');
                $item = new $itemClass($this, $itemData);
                if ($item->isPreliminary()) {
                    $this->preliminaryItems[] = $item;
                } else {
                    $this->items[$item->getBackpackPosition() - 1] = $item;
                }
            }
        }

        $this->fetchDate = time();
    }

    /**
     * Returns the application ID of the game this inventory belongs to
     *
     * @return int The application ID of the game this inventory belongs to
     */
    public function getAppId() {
        return $this->appId;
    }

    /**
     * Returns the item at the given position in the backpack. The positions
     * range from 1 to 100 instead of the usual array indices (0 to 99).
     *
     * @param int $index The position of the item in the backpack
     * @return GameItem The item at the given position
     */
    public function getItem($index) {
        return $this->items[$index - 1];
    }

    /**
     * Returns the item schema
     *
     * The item schema is fetched first if not done already
     *
     * @return GameItemSchema The item schema for the game this inventory belongs to
     * @throws WebApiException on Web API errors
     */
    public function getItemSchema() {
        if ($this->itemSchema == null) {
            $this->itemSchema = GameItemSchema::create($this->appId, self::$schemaLanguage);
        }

        return $this->itemSchema;
    }

    /**
     * Returns an array of all items in this players inventory.
     *
     * @return array All items in the backpack
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * Returns an array of all items that this player just found or traded
     *
     * @return array All preliminary items of the inventory
     */
    public function  getPreliminaryItems() {
        return $this->preliminaryItems;
    }

    /**
     * Returns the Steam ID of the player owning this inventory
     *
     * @return SteamId The Steam ID of the owner of this inventory
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Returns the 64bit SteamID of the player owning this inventory
     *
     * @return string The 64bit SteamID
     */
    public function getSteamId64() {
        return $this->steamId64;
    }

    /**
     * Returns whether the items contained in this inventory have been already
     * fetched
     *
     * @return bool Whether the contents backpack have been fetched
     */
    public function isFetched() {
        return !empty($this->fetchDate);
    }

    /**
     * Returns the number of items in the user's backpack
     *
     * @return int The number of items in the backpack
     */
    public function size() {
        return sizeof($this->items);
    }

}
