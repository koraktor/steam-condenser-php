<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

/**
 * Provides item definitions and related data that specify the items of a game
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameItemSchema {

    use Cacheable;

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

    public static function initialize() {
        self::cacheableWithIds(['appId', 'language']);
    }

    /**
     * Creates a new item schema for the game with the given application ID and
     * with descriptions in the given language
     *
     * @param int $appId The application ID of the game
     * @param string $language The language of description strings
     */
    protected function __construct($appId, $language) {
        $this->appId    = $appId;
        $this->language = $language;
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
     * Updates the item definitions of this schema using the Steam Web API
     *
     * @throws \SteamCondenser\Exceptions\WebApiException if the item schema
     *         cannot be fetched
     */
    public function internalFetch() {
        $params = ['language' => $this->language];
        $data = WebApi::getJSONData("IEconItems_{$this->appId}", 'GetSchema', 1, $params);

        $this->attributes = [];
        foreach ($data->attributes as $attribute) {
            $this->attributes[$attribute->defindex] = $attribute;
            $this->attributes[$attribute->name] = $attribute;
        }

        $this->effects = [];
        foreach ($data->attribute_controlled_attached_particles as $effect) {
            $this->effects[$effect->id] = $effect;
        }

        $this->items = [];
        $this->itemNames = [];
        foreach ($data->items as $item) {
            $this->items[$item->defindex] = $item;
            $this->itemNames[$item->name] = $item->defindex;
        }

        if (!empty($data->levels)) {
            $this->itemLevels = [];
            foreach ($data->item_levels as $itemLevelType) {
                $itemLevels = [];
                foreach ($itemLevelType->levels as $itemLevel) {
                    $itemLevels[$itemLevel->level] = $itemLevel->name;
                }
                $this->itemLevels[$itemLevelType->name] = $itemLevels;
            }
        }

        $this->itemSets = [];
        foreach ($data->item_sets as $itemSet) {
            $this->itemSets[$itemSet->item_set] = $itemSet;
        }

        $this->origins = [];
        foreach ($data->originNames as $origin) {
            $this->origins[$origin->origin] = $origin->name;
        }

        $this->qualities = [];
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
    }

}

GameItemSchema::initialize();
