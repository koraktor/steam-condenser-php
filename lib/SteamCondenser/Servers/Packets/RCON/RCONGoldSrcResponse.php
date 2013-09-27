<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2014, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Packets\RCON;

use SteamCondenser\Servers\Packets\SteamPacket;

/**
 * This packet class represents a RCON response packet sent by a GoldSrc server
 *
 * It is used to transport the output of a command from the server to the
 * client which requested the command execution.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage rcon-packets
 * @see GoldSrcServer::rconExec()
 */
class RCONGoldSrcResponse extends SteamPacket
{

    /**
     * Creates a RCON command response for the given command output
     *
     * @param string $commandResponse The output of the command executed on the
     *        server
     */
    public function __construct($commandResponse) {
        parent::__construct(SteamPacket::RCON_GOLDSRC_RESPONSE_HEADER, $commandResponse);
    }

    /**
     * Returns the output of the command execution
     *
     * @return string The output of the command
     */
    public function getResponse() {
        return substr($this->contentData->_array(), 0, -2);
    }
}
