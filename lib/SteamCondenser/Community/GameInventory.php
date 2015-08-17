<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

use SteamCondenser\Community\DotA2\DotA2BetaInventory;
use SteamCondenser\Community\DotA2\DotA2Inventory;
use SteamCondenser\Community\Portal2\Portal2Inventory;
use SteamCondenser\Community\TF2\TF2BetaInventory;
use SteamCondenser\Community\TF2\TF2Inventory;

/**
 * Provides basic functionality to represent an inventory of player in a game
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameInventory {

    use Cacheable {
        Cacheable::create as createCacheable;
    }

    const ITEM_CLASS = 'GameItem';

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

        switch ($appId) {
            case DotA2BetaInventory::APP_ID:
                $inventoryClass = 'Dota2\\Dota2BetaInventory';
                break;
            case DotA2Inventory::APP_ID:
                $inventoryClass = 'Dota2\\Dota2Inventory';
                break;
            case Portal2Inventory::APP_ID:
                $inventoryClass = 'Portal2\\Portal2Inventory';
                break;
            case TF2BetaInventory::APP_ID:
                $inventoryClass = 'TF2\\TF2BetaInventory';
                break;
            case TF2Inventory::APP_ID:
                $inventoryClass = 'TF2\\TF2Inventory';
                break;
            default:
                $inventoryClass = 'GameInventory';
        }

        self::overwriteClass("\\SteamCondenser\\Community\\$inventoryClass");

        return self::createCacheable($appId, $steamId64, $fetchNow, $bypassCache);
    }

    public static function initialize() {
        self::cacheableWithIds(['appId', 'steamId64']);
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
     * @throws \SteamCondenser\Exceptions\WebApiException on Web API errors
     */
    protected function __construct($appId, $steamId64) {
        $this->appId = $appId;
        $this->steamId64 = $steamId64;
        $this->user = SteamId::create($steamId64, false);
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
     * @return GameItemSchema The item schema for the game this inventory
     *         belongs to
     * @throws \SteamCondenser\Exceptions\WebApiException on Web API errors
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
     * Updates the contents of the backpack using Steam Web API
     */
    protected function internalFetch() {
        $params = ['SteamID' => $this->steamId64];
        $result = WebApi::getJSONData("IEconItems_{$this->getAppId()}", 'GetPlayerItems', 1, $params);

        $this->items = [];
        $this->preliminaryItems = [];
        foreach ($result->items as $itemData) {
            if ($itemData != null) {
                $inventoryClass = get_called_class();
                $itemClass = $inventoryClass::ITEM_CLASS;
                $item = new $itemClass($this, $itemData);
                if ($item->isPreliminary()) {
                    $this->preliminaryItems[] = $item;
                } else {
                    $this->items[$item->getBackpackPosition() - 1] = $item;
                }
            }
        }
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

GameInventory::initialize();
