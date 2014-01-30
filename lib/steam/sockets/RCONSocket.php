<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2013, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'TCPSocket.php';
require_once STEAM_CONDENSER_PATH . 'exceptions/RCONBanException.php';
require_once STEAM_CONDENSER_PATH . 'exceptions/RCONNoAuthException.php';
require_once STEAM_CONDENSER_PATH . 'exceptions/PacketFormatException.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONPacket.php';
require_once STEAM_CONDENSER_PATH . 'steam/packets/rcon/RCONPacketFactory.php';
require_once STEAM_CONDENSER_PATH . 'steam/sockets/SteamSocket.php';

/**
 * This class represents a socket used for RCON communication with game servers
 * based on the Source engine (e.g. Team Fortress 2, Counter-Strike: Source)
 *
 * The Source engine uses a stateful TCP connection for RCON communication and
 * uses an additional socket of this type to handle RCON requests.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage sockets
 */
class RCONSocket extends SteamSocket {

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var int
     */
    private $portNumber;

    /**
     * Creates a new TCP socket to communicate with the server on the given IP
     * address and port
     *
     * @param string $ipAddress Either the IP address or the DNS name of the
     *        server
     * @param int $portNumber The port the server is listening on
     */
    public function __construct($ipAddress, $portNumber) {
        $this->buffer = ByteBuffer::allocate(1400);
        $this->ipAddress = $ipAddress;
        $this->portNumber = $portNumber;
    }

    /**
     * Closes the underlying TCP socket if it exists
     *
     * @see SteamSocket::close()
     */
    public function close() {
        if(!empty($this->socket)) {
            parent::close();
        }
    }

    /**
     * Sends the given RCON packet to the server
     *
     * @param SteamPacket $dataPacket The RCON packet to send to the server
     */
    public function send(SteamPacket $dataPacket) {
        if(empty($this->socket) || !$this->socket->isOpen()) {
            $this->socket = new TCPSocket();
            $this->socket->connect($this->ipAddress, $this->portNumber, SteamSocket::$timeout);
        }

        parent::send($dataPacket);
    }

    /**
     * Reads a packet from the socket
     *
     * The Source RCON protocol allows packets of an arbitrary sice transmitted
     * using multiple TCP packets. The data is received in chunks and
     * concatenated into a single response packet.
     *
     * @return SteamPacket The packet replied from the server or
     *         <var>null</var> if the connection has been closed by the server
     */
    public function getReply() {
        try {
            if ($this->receivePacket(4) == 0) {
                $this->socket->close();
                return null;
            }
        } catch (ConnectionResetException $e) {
            $this->socket->close();
            return null;
        }

        $packetSize     = $this->buffer->getLong();
        $remainingBytes = $packetSize;

        $packetData = '';
        do {
            $receivedBytes = $this->receivePacket($remainingBytes);
            $remainingBytes -= $receivedBytes;
            $packetData .= $this->buffer->get();
        } while($remainingBytes > 0);

        $packet = RCONPacketFactory::getPacketFromData($packetData);

        trigger_error('Received packet of type ' . get_class($packet));

        return $packet;
    }
}
