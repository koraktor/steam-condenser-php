<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Sockets;

use SteamCondenser\Exceptions\PacketFormatException;
use SteamCondenser\Servers\Packets\SteamPacketFactory;

/**
 * This class represents a socket used to communicate with master servers
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage sockets
 */
class MasterServerSocket extends SteamSocket {

    /**
     * Creates a new UDP socket to communicate with the server on the given IP
     * address and port
     *
     * @param string $ipAddress Either the IP address or the DNS name of the
     *        server
     * @param int $portNumber The port the server is listening on
     */
    public function __construct($ipAddress, $portNumber = 27015) {
        parent::__construct($ipAddress, $portNumber);
    }

    /**
     * Reads a single packet from the socket
     *
     * @return SteamPacket The packet replied from the server
     * @throws PacketFormatException if the packet has the wrong format
     */
    public function getReply() {
        $this->receivePacket(1500);

        if($this->buffer->getLong() != -1) {
            throw new PacketFormatException("Master query response has wrong packet header.");
        }

        $packet = SteamPacketFactory::getPacketFromData($this->buffer->get());

        $this->logger->debug("Received reply of type \"" . get_class($packet) . "\"");

        return $packet;
    }

}
