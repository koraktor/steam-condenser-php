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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use SteamCondenser\ByteBuffer;
use SteamCondenser\UDPSocket;
use SteamCondenser\Exceptions\ConnectionResetException;
use SteamCondenser\Exceptions\SocketException;
use SteamCondenser\Exceptions\TimeoutException;
use SteamCondenser\Servers\Packets\SteamPacket;

/**
 * This abstract class implements common functionality for sockets used to
 * connect to game and master servers
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage sockets
 */
abstract class SteamSocket implements LoggerAwareInterface {

    /**
     * @var int The default socket timeout
     */
    protected static $timeout = 1000;

    /**
     * @var ByteBuffer
     */
    protected $buffer;

    /**
     * @var LoggerInterface The logger for this instance
     */
    protected $logger;

    /**
     * @var UDPSocket
     */
    protected $socket;

    /**
     * Sets the timeout for socket operations
     *
     * Any request that takes longer than this time will cause a {@link
     * TimeoutException}.
     *
     * @param int $timeout The amount of milliseconds before a request times
     *        out
     */
    public static function setTimeout($timeout) {
        self::$timeout = $timeout;
    }

    /**
     * Creates a new UDP socket to communicate with the server on the given IP
     * address and port
     *
     * @param string $ipAddress Either the IP address or the DNS name of the
     *        server
     * @param int $portNumber The port the server is listening on
     */
    public function __construct($ipAddress, $portNumber = 27015) {
        $this->logger = \SteamCondenser\getLogger(get_class($this));

        $this->socket = new UDPSocket();
        $this->socket->connect($ipAddress, $portNumber, 0);
    }

    /**
     * Closes this socket
     *
     * @see #close()
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Closes the underlying socket
     *
     * @see UDPSocket::close()
     */
    public function close() {
        if(!empty($this->socket) && $this->socket->isOpen()) {
            $this->socket->close();
        }
    }

    /**
     * Subclasses have to implement this method for their individual packet
     * formats
     *
     * @return SteamPacket The packet replied from the server
     */
    abstract public function getReply();

    /**
     * Reads the given amount of data from the socket and wraps it into the
     * buffer
     *
     * @param int $bufferLength The data length to read from the socket
     * @throws SocketException if an error occurs while reading data
     * @throws TimeoutException if no packet is received on time
     * @return int The number of bytes that have been read from the socket
     * @see ByteBuffer
     */
    public function receivePacket($bufferLength = 0) {
        if(!$this->socket->select(self::$timeout)) {
            throw new TimeoutException();
        }

        if($bufferLength == 0) {
            $this->buffer->clear();
        } else {
            $this->buffer = ByteBuffer::allocate($bufferLength);
        }

        try {
            $data = $this->socket->recv($this->buffer->remaining());
            $this->buffer->put($data);
        } catch (ConnectionResetException $e) {
            $this->socket->close();
            throw $e;
        }

        $bytesRead = $this->buffer->position();
        $this->buffer->rewind();
        $this->buffer->limit($bytesRead);

        return $bytesRead;
    }

    /**
     * Sends the given packet to the server
     *
     * This converts the packet into a byte stream first before writing it to
     * the socket.
     *
     * @param SteamPacket $dataPacket The packet to send to the server
     * @see SteamPacket::__toString()
     */
    public function send(SteamPacket $dataPacket) {
        $this->logger->debug("Sending packet of type \"" . get_class($dataPacket) . "\"...");

        $this->socket->send($dataPacket->__toString());
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger): void {
        $this->logger = $logger;
    }

}
