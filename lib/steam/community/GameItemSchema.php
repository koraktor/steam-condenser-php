<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/WebApi.php';

/**
 * Provides item definitions and related data that specify the items of a game
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameItemSchema {

    /**
     * @var array
     */
    public static $cache = array();

    /**
     * @var int
     */
    private $appId;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $effects;

    /**
     * @var int
     */
    private $fetchDate;

    /**
     * @var array
     */
    private $itemLevels;

    /**
     * @var array
     */
    private $itemNames;

    /**
     * @var array
     */
    private $itemSets;

    /**
     * @var array
     */
    private $items;

    /**
     * @var string
     */
    private $language;

    /**
     * @var array
     */
    private $origins;

    /**
     * @var array
     */
    private $qualities;

    /**
     * Clears the item schema cache
     */
    public static function clearCache() {
        self::$cache = array();
    }

    /**
     * Creates a new item schema for the game with the given application ID and
     * with descriptions in the given language
     *
     * @param int $appId The application ID of the game
     * @param string $language The language of description strings
     * @param bool $fetch if <var>true</var> the schemas's data is fetched
     *        after creation
     * @param bool $bypassCache if <var>true</var> the schemas's data is
     *        fetched again even if it has been cached already
     * @return GameInventory The item schema for the given game and language
     */
    public static function create($appId, $language, $fetch = true, $bypassCache = false) {
        if (GameItemSchema::isCached($appId, $language) && !$bypassCache) {
            $itemSchema = self::$cache[$appId][$language];
            if ($fetch && !$itemSchema->isFetched()) {
                $itemSchema->fetch();
            }
            return $itemSchema;
        } else {
            return new GameItemSchema($appId, $language, $fetch);
        }
    }

    /**
     * Returns whether the item schema for the given application ID and
     * language is already cached
     *
     * @param int $appId The application ID of the game
     * @param string $language The language of the item schema
     * @return bool <var>true</var> if the object with the given ID is already
     *         cached
     */
    public static function isCached($appId, $language) {
        return array_key_exists($appId, self::$cache) &&
               array_key_exists($language, self::$cache[$appId]);
    }

    /**
     * Creates a new item schema for the game with the given application ID and
     * with descriptions in the given language
     *
     * @param int $appId The application ID of the game
     * @param string $language The language of description strings
     * @param bool $fetch if <var>true</var> the schemas's data is fetched
     *        after creation
     */
    protected function __construct($appId, $language, $fetch) {
        $this->appId    = $appId;
        $this->language = $language;

        if ($fetch) {
            $this->fetch();
        }
    }

    /**
     * Updates the item definitions of this schema using the Steam Web API
     *
     * @throws WebApiException if the item schema cannot be fetched
     */
    public function fetch() {
        $params = array('language' => $this->language);
        $data = WebApi::getJSONData("IEconItems_{$this->appId}", 'GetSchema', 1, $params);

        $this->attributes = array();
        foreach ($data->attributes as $attribute) {
            $this->attributes[$attribute->defindex] = $attribute;
            $this->attributes[$attribute->name] = $attribute;
        }

        $this->effects = array();
        foreach ($data->attribute_controlled_attached_particles as $effect) {
            $this->effects[$effect->id] = $effect;
        }

        $this->items = array();
        $this->itemNames = array();
        foreach ($data->items as $item) {
            $this->items[$item->defindex] = $item;
            $this->itemNames[$item->name] = $item->defindex;
        }

        if (!empty($data->levels)) {
            $this->itemLevels = array();
            foreach ($data->item_levels as $itemLevelType) {
                $itemLevels = array();
                foreach ($itemLevelType->levels as $itemLevel) {
                    $itemLevels[$itemLevel->level] = $itemLevel->name;
                }
                $this->itemLevels[$itemLevelType->name] = $itemLevels;
            }
        }

        $this->itemSets = array();
        foreach ($data->item_sets as $itemSet) {
            $this->itemSets[$itemSet->item_set] = $itemSet;
        }

        $this->origins = array();
        foreach ($data->originNames as $origin) {
            $this->origins[$origin->origin] = $origin->name;
        }

        $this->qualities = array();
        $index = -1;
        foreach ($data->qualities as $key => $value) {
            $index ++;
            if (property_exists($data->qualityNames, $key)) {
                $qualityName = $data->qualityNames->$key;
            }
            if (empty($qualityName)) {
                $qualityName = ucwords($key);
            }
            $this->qualities[$index] = $qualityName;
        }

        $this->cache();

        $this->fetchDate = time();
    }

    /**
     * Returns whether the data for this item schema has already been fetched
     *
     * @return bool <var>true</var> if this item schema's data is available
     */
    public function isFetched() {
        return $this->fetchDate != null;
    }

    /**
     * Returns the application ID of the game this item schema belongs to
     *
     * @return int The application ID of the game
     */
    public function getAppId() {
        return $this->appId;
    }

    /**
     * The attributes defined for this game's items
     *
     * @return array This item schema's attributes
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * The effects defined for this game's items
     *
     * @return array This item schema's effects
     */
    public function getEffects() {
        return $this->effects;
    }

    /**
     * The levels defined for this game's items
     *
     * @return array This item schema's item levels
     */
    public function getItemLevels() {
        return $this->itemLevels;
    }

    /**
     * A mapping from the item name to the item's defindex
     *
     * @return array The item name mapping
     */
    public function getItemNames() {
        return $this->itemNames;
    }

    /**
     * The item sets defined for this game's items
     *
     * @return array This item schema's item sets
     */
    public function getItemSets() {
        return $this->itemSets;
    }

    /**
     * The items defined for this game
     *
     * @return array The items in this schema
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * The language of this item schema
     *
     * @return string The language of this item schema
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * The item origins defined for this mµµ game's items
     *
     * @return array This item schema's origins
     */
    public function getOrigins() {
        return $this->origins;
    }

    /**
     * The item qualities defined for this game's items
     *
     * @return array This item schema's qualities
     */
    public function getQualities() {
        return $this->qualities;
    }

    /**
     * Saves this item schema in the cache
     *
     * @return bool <var>false</var> if this item schema is already cached
     */
    private function cache() {
        if (array_key_exists($this->appId, self::$cache) &&
            array_key_exists($this->language, self::$cache[$this->appId])) {
            return false;
        }

        self::$cache[$this->appId][$this->language] = $this;

        return true;
    }

}
