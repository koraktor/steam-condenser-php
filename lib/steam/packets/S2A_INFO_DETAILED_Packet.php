<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2013, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/packets/S2A_INFO_BasePacket.php';

/**
 * This class represents a S2A_INFO_DETAILED response packet sent by a GoldSrc
 * server
 *
 * @author     Sebastian Staudt
 * @deprecated Only outdated GoldSrc servers (before 10/24/2008) use this
 *             format. Newer ones use the same format as Source servers now
 *             (see {@link S2A_INFO2_Packet}).
 * @package    steam-condenser
 * @subpackage packets
 * @see GameServer::updateServerInfo()
 */
class S2A_INFO_DETAILED_Packet extends S2A_INFO_BasePacket {

    /**
     * Creates a new S2A_INFO_DETAILED response object based on the given data
     *
     * @param string $data The raw packet data replied from the server
     */
    public function __construct($data) {
        parent::__construct(SteamPacket::S2A_INFO_DETAILED_HEADER, $data);

        $this->info['serverIp'] = $this->contentData->getString();
        $this->info['serverName'] = $this->contentData->getString();
        $this->info['mapName'] = $this->contentData->getString();
        $this->info['gameDir'] = $this->contentData->getString();
        $this->info['gameDescription'] = $this->contentData->getString();
        $this->info['numberOfPlayers'] = $this->contentData->getByte();
        $this->info['maxPlayers'] = $this->contentData->getByte();
        $this->info['networkVersion'] = $this->contentData->getByte();
        $this->info['dedicated'] = $this->contentData->getByte();
        $this->info['operatingSystem'] = $this->contentData->getByte();
        $this->info['passwordProtected'] = $this->contentData->getByte() == 1;
        $this->info['isMod'] = $this->contentData->getByte() == 1;

        if($this->info['isMod']) {
            $this->info['modInfo']['urlInfo'] = $this->contentData->getString();
            $this->info['modInfo']['urlDl'] = $this->contentData->getString();
            $this->contentData->getByte();
            if($this->contentData->remaining() == 12) {
                $this->info['modInfo']['modVersion'] = $this->contentData->getLong();
                $this->info['modInfo']['modSize'] = $this->contentData->getLong();
                $this->info['modInfo']['svOnly'] = ($this->contentData->getByte() == 1);
                $this->info['modInfo']['clDll'] = ($this->contentData->getByte() == 1);
                $this->info['secure'] = $this->contentData->getByte() == 1;
                $this->info['numberOfBots'] = $this->contentData->getByte();
            }
        } else {
            $this->info['secure'] = $this->contentData->getByte() == 1;
            $this->info['numberOfBots'] = $this->contentData->getByte();
        }
    }

}
