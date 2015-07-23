<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Packets;

use SteamCondenser\Exceptions\PacketFormatException;
use SteamCondenser\Servers\SteamPlayer;

/**
 * This class represents a S2A_PLAYER response sent by a game server
 *
 * It is used to transfer a list of players currently playing on the server.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        GameServer::updatePlayerInfo()
 */
class S2APLAYERPacket extends SteamPacket {

    /**
     * @var array
     */
    private $playerHash;

    /**
     * Creates a new S2A_PLAYER response object based on the given data
     *
     * @param string $contentData The raw packet data sent by the server
     * @throws PacketFormatException if the packet data is missing
     */
    public function __construct($contentData) {
        if (empty($contentData)) {
            throw new PacketFormatException('Wrong formatted S2A_PLAYER packet.');
        }
        parent::__construct(SteamPacket::S2A_PLAYER_HEADER, $contentData);

        $this->contentData->getByte();

        $this->playerHash = [];
        while($this->contentData->remaining() > 0) {
            $playerData = [$this->contentData->getByte(), $this->contentData->getString(), $this->contentData->getLong(), $this->contentData->getFloat()];
            $this->playerHash[$playerData[1]] = new SteamPlayer($playerData[0], $playerData[1], $playerData[2], $playerData[3]);
        }
    }

    /**
     * Returns the list of active players provided by the server
     *
     * @return array All active players on the server
     */
    public function getPlayerHash() {
        return $this->playerHash;
    }
}
