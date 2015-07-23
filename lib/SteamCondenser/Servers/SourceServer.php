<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers;

use SteamCondenser\Exceptions\RCONBanException;
use SteamCondenser\Exceptions\RCONNoAuthException;
use SteamCondenser\Exceptions\SteamCondenserException;
use SteamCondenser\Exceptions\TimeoutException;

/**
 * This class represents a Source game server and can be used to query
 * information about and remotely execute commands via RCON on the server
 *
 * A Source game server is an instance of the Source Dedicated Server (SrcDS)
 * running games using Valve's Source engine, like Counter-Strike: Source,
 * Team Fortress 2 or Left4Dead.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage servers
 * @see        GoldSrcServer
 */
class SourceServer extends GameServer {

    /**
     * @var bool Whether the RCON connection is already authenticated
     */
    protected $rconAuthenticated;

    /**
     * @var int The request ID used for RCON request
     */
    protected $rconRequestId;

    /**
     * @var Sockets\RCONSocket The TCP socket to use for RCON communication
     */
    protected $rconSocket;

    /**
     * Disconnects the TCP-based channel used for RCON commands
     *
     * @see RCONSocket::close()
     */
    public function disconnect() {
        $this->rconSocket->close();
    }

    /**
     * Returns a master server instance for the default master server for
     * Source games
     *
     * @return MasterServer The Source master server
     */
    public static function getMaster() {
        return new MasterServer(MasterServer::SOURCE_MASTER_SERVER);
    }

    /**
     * Returns a random 16-bit integer used to identify RCON communication
     * packets
     *
     * @return int The request ID for RCON communication
     */
    protected function generateRconRequestId() {
        return rand(0, pow(2, 16));
    }

    /**
     * Initializes the sockets to communicate with the Source server
     *
     * @see RCONSocket
     * @see SourceSocket
     */
    public function initSocket() {
        $this->rconSocket = new Sockets\RCONSocket($this->ipAddress, $this->port);
        $this->socket = new Sockets\SourceSocket($this->ipAddress, $this->port);
    }

    /**
     * Authenticates the connection for RCON communication with the server
     *
     * @param string $password The RCON password of the server
     * @return bool whether authentication was successful
     * @see rconExec()
     * @throws RCONBanException if banned by the server
     * @throws SteamCondenserException if a problem occurs while parsing the
     *         reply
     * @throws TimeoutException if the request times out
     */
    public function rconAuth($password) {
        $this->rconRequestId = $this->generateRconRequestId();

        $this->rconSocket->send(new Packets\RCON\RCONAuthRequest($this->rconRequestId, $password));

        $reply = $this->rconSocket->getReply();
        if ($reply == null) {
            throw new RCONBanException();
        }
        $reply = $this->rconSocket->getReply();
        $this->rconAuthenticated = $reply->getRequestId() == $this->rconRequestId;

        return $this->rconAuthenticated;
    }

    /**
     * Remotely executes a command on the server via RCON
     *
     * @param string $command The command to execute on the server via RCON
     * @return string The output of the executed command
     * @see rconAuth()
     * @throws RCONNoAuthException if not authenticated with the server
     * @throws SteamCondenserException if a problem occurs while parsing the
     *         reply
     * @throws TimeoutException if the request times out
     */
    public function rconExec($command) {
        if(!$this->rconAuthenticated) {
            throw new RCONNoAuthException();
        }

        $this->rconSocket->send(new Packets\RCON\RCONExecRequest($this->rconRequestId, $command));

        $isMulti = false;
        $response = [];
        do {
            $responsePacket = $this->rconSocket->getReply();

            if ($responsePacket == null ||
                    $responsePacket instanceof Packets\RCON\RCONAuthResponse) {
                $this->rconAuthenticated = false;
                throw new RCONNoAuthException();
            }

            if (!$isMulti && strlen($responsePacket->getResponse()) > 0) {
                $isMulti = true;
                $this->rconSocket->send(new Packets\RCON\RCONTerminator($this->rconRequestId));
            }

            $response[] = $responsePacket->getResponse();
        } while($isMulti && !(empty($response[sizeof($response) - 2]) && empty($response[sizeof($response) - 1])));

        return trim(join('', $response));
    }
}
