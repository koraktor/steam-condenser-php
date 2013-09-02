<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2013, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/A2M_GET_SERVERS_BATCH2_Packet.php';
require_once STEAM_CONDENSER_PATH . 'steam/servers/Server.php';
require_once STEAM_CONDENSER_PATH . 'steam/sockets/MasterServerSocket.php';

/**
 * This class represents a Steam master server and can be used to get game
 * servers which are publicly available
 *
 * An intance of this class can be used much like Steam's server browser to get
 * a list of available game servers, including filters to narrow down the
 * search results.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage servers
 */
class MasterServer extends Server {

    /**
     * @var Monolog\Logger The Monolog logger for this class
     */
    private static $log;

    /**
     * @var string The master server address to query for GoldSrc game servers
     */
    const GOLDSRC_MASTER_SERVER = 'hl1master.steampowered.com:27011';

    /**
     * @var string The master server address to query for GoldSrc game servers
     */
    const SOURCE_MASTER_SERVER = 'hl2master.steampowered.com:27011';

    /**
     * @var int The region code for the US east coast
     */
    const REGION_US_EAST_COAST = 0x00;

    /**
     * @var int The region code for the US west coast
     */
    const REGION_US_WEST_COAST = 0x01;

    /**
     * @var int The region code for South America
     */
    const REGION_SOUTH_AMERICA = 0x02;

    /**
     * @var int The region code for Europe
     */
    const REGION_EUROPE = 0x03;

    /**
     * @var int The region code for Asia
     */
    const REGION_ASIA = 0x04;

    /**
     * @var int The region code for Australia
     */
    const REGION_AUSTRALIA = 0x05;

    /**
     * @var int The region code for the Middle East
     */
    const REGION_MIDDLE_EAST = 0x06;

    /**
     * @var int The region code for Africa
     */
    const REGION_AFRICA = 0x07;

    /**
     * @var int The region code for the whole world
     */
    const REGION_ALL = 0xFF;

    /**
     * @var int
     */
    private static $retries = 3;

    /**
     * @var MasterServerSocket
     */
    protected $socket;

    /**
     * Creates a new master server instance with the given address and port
     *
     * @param string $address Either an IP address, a DNS name or one of them
     *        combined with the port number. If a port number is given, e.g.
     *        'server.example.com:27016' it will override the second argument.
     * @param int $port The port the server is listening on
     * @throws SteamCondenserException if an host name cannot be resolved
     */
    public function __construct($address, $port = null) {
        parent::__construct($address, $port);

        if (!isset(self::$log)) {
            self::$log = new \Monolog\Logger('MasterServer');
        }
    }

    /**
     * Sets the number of consecutive requests that may fail, before getting
     * the server list is cancelled (default: 3)
     *
     * @param int $retries The number of allowed retries
     */
    public static function setRetries($retries) {
        self::$retries = $retries;
    }

    /**
     * Returns a list of game server matching the given region and filters
     *
     * Filtering:
     * Instead of filtering the results sent by the master server locally, you
     * should at least use the following filters to narrow down the results
     * sent by the master server.
     *
     * <b>Note:</b> Receiving all servers from the master server is taking
     * quite some time.
     *
     * Available filters:
     *
     * <ul>
     * <li><var>\type\d</var>: Request only dedicated servers
     * <li><var>\secure\1</var>: Request only secure servers
     * <li><var>\gamedir\[mod]</var>: Request only servers of a specific mod
     * <li><var>\map\[mapname]</var>: Request only servers running a specific
     *     map
     * <li><var>\linux\1</var>: Request only linux servers
     * <li><var>\emtpy\1</var>: Request only **non**-empty servers
     * <li><var>\full\1</var>: Request only servers **not** full
     * <li><var>\proxy\1</var>: Request only spectator proxy servers
     * </ul>
     *
     * @param int $regionCode The region code to specify a location of the
     *        game servers
     * @param string $filter The filters that game servers should match
     * @param bool $force Return a list of servers even if an error occured
     *        while fetching them from the master server
     * @return array A list of game servers matching the given
     *         region and filters
     * @see setTimeout()
     * @see A2M_GET_SERVERS_BATCH2_Packet
     * @throws SteamCondenserException if a problem occurs while parsing the
     *         reply
     * @throws TimeoutException if too many timeouts occur while querying the
     *         master server
     */
    public function getServers($regionCode = MasterServer::REGION_ALL , $filter = '', $force = false) {
        $failCount   = 0;
        $finished    = false;
        $portNumber  = 0;
        $hostName    = '0.0.0.0';
        $serverArray = array();

        while(true) {
            $failCount = 0;
            try {
                do {
                    $this->socket->send(new A2M_GET_SERVERS_BATCH2_Packet($regionCode, "$hostName:$portNumber", $filter));
                    try {
                        $serverStringArray = $this->socket->getReply()->getServers();

                        foreach($serverStringArray as $serverString) {
                            $serverString = explode(':', $serverString);
                            $hostName = $serverString[0];
                            $portNumber = $serverString[1];

                            if($hostName != '0.0.0.0' && $portNumber != 0) {
                                $serverArray[] = array($hostName, $portNumber);
                            } else {
                                $finished = true;
                            }
                        }
                        $failCount = 0;
                    } catch(TimeoutException $e) {
                        $failCount ++;
                        if($failCount == self::$retries) {
                            throw $e;
                        }
                        self::$log->addInfo("Request to master server {$this->ipAddress} timed out, retrying...");
                    }
                } while(!$finished);
                break;
            } catch(TimeoutException $e) {
                if ($force) {
                    break;
                } else if($this->rotateIp()) {
                    throw $e;
                }
                self::$log->addInfo("Request to master server failed, retrying {$this->ipAddress}...");
            }
        }

        return $serverArray;
    }

    /**
     * Initializes the socket to communicate with the master server
     *
     * @see MasterServerSocket
     */
    public function initSocket() {
        $this->socket = new MasterServerSocket($this->ipAddress, $this->port);
    }

}
