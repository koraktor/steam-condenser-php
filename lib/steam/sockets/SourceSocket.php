<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2013, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/SteamPacketFactory.php';
require_once STEAM_CONDENSER_PATH . 'steam/sockets/SteamSocket.php';

/**
 * This class represents a socket used to communicate with game servers based
 * on the Source engine (e.g. Team Fortress 2, Counter-Strike: Source)
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage sockets
 */
class SourceSocket extends SteamSocket {

    /**
     * @var Monolog\Logger The Monolog logger for this class
     */
    private static $log;

    /**
     * Creates a new UDP socket to communicate with the Source server on the
     * given IP address and port
     *
     * @param string $ipAddress Either the IP address or the DNS name of the
     *        server
     * @param int $portNumber The port the server is listening on
     */
    public function __construct($ipAddress, $portNumber = 27015) {
        parent::__construct($ipAddress, $portNumber);

        if (!isset(self::$log)) {
            self::$log = new \Monolog\Logger('SourceSocket');
        }
    }

    /**
     * Reads a packet from the socket
     *
     * The Source query protocol specifies a maximum packet size of 1,400
     * bytes. Bigger packets will be split over several UDP packets. This
     * method reassembles split packets into single packet objects.
     * Additionally Source may compress big packets using bzip2. Those packets
     * will be compressed.
     *
     * @return SteamPacket The packet replied from the server
     */
    public function getReply() {
        $this->receivePacket(1400);
        $isCompressed = false;

        if($this->buffer->getLong() == -2) {
            do {
                $requestId = $this->buffer->getLong();
                $isCompressed = (($requestId & 0x80000000) != 0);
                $packetCount = $this->buffer->getByte();
                $packetNumber = $this->buffer->getByte() + 1;

                if($isCompressed) {
                    $splitSize = $this->buffer->getLong();
                    $packetChecksum = $this->buffer->getUnsignedLong();
                } else {
                    $splitSize = $this->buffer->getShort();
                }

                $splitPackets[$packetNumber] = $this->buffer->get();

                self::$log->addDebug("Received packet $packetNumber of $packetCount for request #$requestId");

                if(sizeof($splitPackets) < $packetCount) {
                    try {
                        $bytesRead = $this->receivePacket();
                    } catch(TimeoutException $e) {
                        $bytesRead = 0;
                    }
                } else {
                    $bytesRead = 0;
                }
            } while($bytesRead > 0 && $this->buffer->getLong() == -2);

            if($isCompressed) {
                $packet = SteamPacketFactory::reassemblePacket($splitPackets, true, $packetChecksum);
            } else {
                $packet = SteamPacketFactory::reassemblePacket($splitPackets);
            }
        } else {
            $packet = SteamPacketFactory::getPacketFromData($this->buffer->get());
        }

        if($isCompressed) {
            self::$log->addDebug("Received compressed reply of type \"" . get_class($packet) . "\"");
        } else {
            self::$log->addDebug("Received reply of type \"" . get_class($packet) . "\"");
        }

        return $packet;
    }
}
