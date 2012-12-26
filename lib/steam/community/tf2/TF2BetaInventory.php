<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011-2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Inventory.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Item.php';

/**
 * Represents the inventory (aka. Backpack) of a player of the public Team
 * Fortress 2 beta
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class TF2BetaInventory extends TF2Inventory {

    const APP_ID = 520;

    /**
     * This checks the cache for an existing inventory. If it exists it is
     * returned. Otherwise a new inventory is created.
     *
     * @param string $steamId The 64bit Steam ID or vanity URL of the user
     * @param bool $fetchNow Whether the data should be fetched now
     * @param bool $bypassCache Whether the cache should be bypassed
     * @return TF2BetaInventory The inventory created from the given options
     */
    public static function createInventory($steamId, $fetchNow = true, $bypassCache = false) {
        return parent::create(self::APP_ID, $steamId, $fetchNow, $bypassCache);
    }

}
