<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2014, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Exceptions;

/**
 * This exception class indicates that the IP address your accessing the game
 * server from has been banned by the server
 *
 * You or the server operator will have to unban your IP address on the server.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage exceptions
 * @see GameServer::rconAuth()
 */
class RCONBanException extends SteamCondenserException {

    /**
     * Creates a new <var>RCONBanException</var> instance
     */
    public function __construct() {
        parent::__construct('You have been banned from this server.');
    }
}
