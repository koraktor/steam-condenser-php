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

/**
 * Provides basic functionality to represent an item in a game
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class GameItem {

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var int
     */
    private $backpackPosition;

    /**
     * @var int
     */
    private $count;

    /**
     * @var bool
     */
    private $craftable;

    /**
     * @var int
     */
    private $defindex;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $itemClass;

    /**
     * @var int
     */
    private $level;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var int
     */
    private $originalId;

    /**
     * @var bool
     */
    private $preliminary;

    /**
     * @var string
     */
    private $quality;

    /**
     * @var bool
     */
    private $tradeable;

    /**
     * @var string
     */
    private $type;

    /**
     * Creates a new instance of a GameItem with the given data
     *
     * @param GameInventory $inventory The inventory this item is contained in
     * @param stdClass $itemData The data specifying this item
     * @throws WebApiException on Web API errors
     */
    public function __construct(GameInventory $inventory, $itemData) {
        $this->inventory = $inventory;

        $this->defindex         = $itemData->defindex;
        $this->backpackPosition = $itemData->inventory & 0xffff;
        $this->count            = $itemData->quantity;
        $this->id               = $itemData->id;
        $this->itemClass        = $this->getSchemaData()->item_class;
        $this->level            = $itemData->level;
        $this->name             = $this->getSchemaData()->item_name;
        $origins = $this->inventory->getItemSchema()->getOrigins();
        $this->originalId       = $itemData->original_id;
        $this->preliminary      = ($itemData->inventory & 0x40000000) != 0;
        $qualities = $this->inventory->getItemSchema()->getQualities();
        $this->quality          = $qualities[$itemData->quality];
        $this->type             = $this->getSchemaData()->item_type_name;

        if (property_exists($itemData, 'flag_cannot_craft')) {
            $this->craftable = !!$itemData->flag_cannot_craft;
        }
        if (!empty($this->getSchemaData()->item_set)) {
            $itemSets = $this->inventory->getItemSchema()->getItemSets();
            $this->itemSet = $itemSets[$this->getSchemaData()->item_set];
        }
        if (property_exists($itemData, 'origin')) {
            $this->origin = $origins[$itemData->origin];
        }
        if (property_exists($itemData, 'flag_cannot_trade')) {
            $this->tradeable = !!$itemData->flag_cannot_trade;
        }

        $attributesData = array();
        if (property_exists($this->getSchemaData(), 'attributes')) {
            $attributesData = (array) $this->getSchemaData()->attributes;
        }
        if (!empty($itemData->attributes)) {
            $attributesData = array_merge_recursive($attributesData, (array) $itemData->attributes);
        }

        $this->attributes = array();
        foreach ($attributesData as $attributeData) {
            $attributeKey = property_exists($attributeData, 'defindex') ?
                $attributeData->defindex : $attributeData->name;

            if ($attributeKey != null) {
                $schemaAttributesData = $inventory->getItemSchema()->getAttributes();
                $schemaAttributeData = $schemaAttributesData[$attributeKey];
                $this->attributes[] = (object) array_merge_recursive((array) $attributeData, (array) $schemaAttributeData);
            }
        }
    }

    /**
     * Return the attributes of this item
     *
     * @return array The attributes of this item
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * Returns the position of this item in the player's inventory
     *
     * @return int The position of this item in the player's inventory
     */
    public function getBackpackPosition() {
        return $this->backpackPosition;
    }

    /**
     * Returns the number of items the player owns of this item
     *
     * @return int The quanitity of this item
     */
    public function getCount() {
        return $this->count;
    }

    /**
     * Returns the index where the item is defined in the schema
     *
     * @return int The schema index of this item
     */
    public function getDefIndex() {
        return $this->defindex;
    }

    /**
     * Returns the ID of this item
     *
     * @return int The ID of this item
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the class of this item
     *
     * @return string The class of this item
     */
    public function getItemClass() {
        return $this->itemClass;
    }

    /**
     * Returns the level of this item
     *
     * @return int The level of this item
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * Returns the level of this item
     *
     * @return string The level of this item
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the original ID of this item
     *
     * @return int The original ID of this item
     */
    public function  getOriginalId() {
        return $this->originalId;
    }

    /**
     * Returns the quality of this item
     *
     * @return string The quality of this item
     */
    public function getQuality() {
        return $this->quality;
    }

    /**
     * Returns the data for this item that's defined in the item schema
     *
     * @return array The schema data for this item
     * @throws SteamCondenserException if the item schema cannot be loaded
     */
    public function  getSchemaData() {
        $schemaItems = $this->inventory->getItemSchema()->getItems();
        return $schemaItems[$this->defindex];
    }

    /**
     * Returns the type of this item
     *
     * @return string The type of this item
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns whether this item is craftable
     *
     * @return bool <var>true</var> if this item is craftable
     */
    public function isCraftable() {
        return $this->craftable;
    }

    /**
     * Returns whether this item is preliminary
     *
     * Preliminary means that this item was just found or traded and has not
     * yet been added to the inventory
     *
     * @return bool <var>true</var> if this item is preliminary
     */
    public function isPreliminary() {
        return $this->preliminary;
    }

    /**
     * Returns whether this item is tradeable
     *
     * @return bool <var>true</var> if this item is tradeable
     */
    public function isTradeable() {
        return $this->tradeable;
    }

}
