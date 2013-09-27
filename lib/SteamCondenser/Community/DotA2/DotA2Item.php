<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012-2014, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community\DotA2;

use SteamCondenser\Community\GameItem;

/**
 * Represents a DotA 2 item
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class DotA2Item extends GameItem {

    /**
     * @var bool
     */
    private $equipped;

    /**
     * Creates a new instance of a DotA2Item with the given data
     *
     * @param DotA2Inventory $inventory The inventory this item is contained
     *        in
     * @param \stdClass $itemData The data specifying this item
     * @throws \SteamCondenser\Exceptions\WebApiException on Web API errors
     */
    public function __construct(DotA2Inventory $inventory, $itemData) {
        parent::__construct($inventory, $itemData);

        $this->equipped = property_exists($itemData, 'equipped') && sizeof($itemData->equipped) > 0;
    }

    /**
     * Returns whether this item is equipped by this player at all
     *
     * @return bool Whether this item is equipped by this player at all
     */
    public function isEquipped() {
        return $this->equipped;
    }

}
