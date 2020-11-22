<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2020, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacket.php';

/**
 * This is used as a wrapper to create padding of request packets to a minimum
 * size of 1200 bytes. This was introduced in November 2020 as a
 * counter-measure to DoS attacks on game servers.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 */
abstract class QueryPacket extends SteamPacket {

    // The minimum package size as defined by Valve
    const STEAM_GAMESERVER_MIN_CONNECTIONLESS_PACKET_SIZE = 1200;

    /**
     * Creates a new query packet including data padding
     *
     * @param string $data The data of the original query
     */
    public function __construct($header, $data = null) {
        parent::__construct($header, str_pad($data, self::STEAM_GAMESERVER_MIN_CONNECTIONLESS_PACKET_SIZE, "\0"));
    }
}
