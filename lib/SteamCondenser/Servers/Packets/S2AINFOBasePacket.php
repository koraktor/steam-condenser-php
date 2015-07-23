<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Packets;

/**
 * This module implements methods to generate and access server information
 * from S2A_INFO_DETAILED and S2A_INFO2 response packets
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        S2AINFODETAILEDPacket
 * @see        S2AINFO2Packet
 */
abstract class S2AINFOBasePacket extends SteamPacket {

    /**
     * @var array
     */
    protected $info = [];

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
