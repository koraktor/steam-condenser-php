<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2014, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Packets;

/**
 * This packet class represents a A2S_SERVERQUERY_GETCHALLENGE request send to
 * a game server
 *
 * It is used to retrieve a challenge number from the game server, which helps
 * to identify the requesting client.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        GameServer::updateChallengeNumber()
 */
class A2SSERVERQUERYGETCHALLENGEPacket extends SteamPacket {

    /**
     * Creates a new A2S_SERVERQUERY_GETCHALLENGE request object
     */
    public function __construct() {
        parent::__construct(SteamPacket::A2S_SERVERQUERY_GETCHALLENGE_HEADER);
    }
}
