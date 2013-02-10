<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2013, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';

/**
 * This module implements methods to generate and access server information
 * from S2A_INFO_DETAILED and S2A_INFO2 response packets
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        S2A_INFO_DETAILED_Packet
 * @see        S2A_INFO2_Packet
 */
abstract class S2A_INFO_BasePacket extends SteamPacket {

    /**
     * @var array
     */
    protected $info = array();

    /**
     * Returns a generated array of server properties from the instance
     * variables of the packet object
     *
     * @return array The information provided by the server
     */
    public function getInfo() {
        return $this->info;
    }

}
