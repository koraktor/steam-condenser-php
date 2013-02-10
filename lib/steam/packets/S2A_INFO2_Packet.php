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
 * This class represents a S2A_INFO_DETAILED response packet sent by a Source
 * or GoldSrc server
 *
 * Out-of-date (before 10/24/2008) GoldSrc servers use an older format (see
 * {@link S2A_INFO_DETAILED_Packet}).
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage packets
 * @see        GameServer::updateServerInfo()
 */
class S2A_INFO2_Packet extends S2A_INFO_BasePacket {

    const EDF_GAME_ID     = 0x01;
    const EDF_GAME_PORT   = 0x80;
    const EDF_SERVER_ID   = 0x10;
    const EDF_SERVER_TAGS = 0x20;
    const EDF_SOURCE_TV   = 0x40;

    /**
     * Creates a new S2A_INFO2 response object based on the given data
     *
     * @param string $data The raw packet data replied from the server
     */
    public function __construct($data) {
        parent::__construct(SteamPacket::S2A_INFO2_HEADER, $data);

        $this->info['networkVersion'] = $this->contentData->getByte();
        $this->info['serverName'] = $this->contentData->getString();
        $this->info['mapName'] = $this->contentData->getString();
        $this->info['gameDir'] = $this->contentData->getString();
        $this->info['gameDesc'] = $this->contentData->getString();
        $this->info['appId'] = $this->contentData->getShort();
        $this->info['numberOfPlayers'] = $this->contentData->getByte();
        $this->info['maxPlayers'] = $this->contentData->getByte();
        $this->info['botNumber'] = $this->contentData->getByte();
        $this->info['dedicated'] = chr($this->contentData->getByte());
        $this->info['operatingSystem'] = chr($this->contentData->getByte());
        $this->info['passwordProtected'] = $this->contentData->getByte() == 1;
        $this->info['secureServer'] = $this->contentData->getByte() == 1;
        $this->info['gameVersion'] = $this->contentData->getString();

        if($this->contentData->remaining() > 0) {
            $extraDataFlag = $this->contentData->getByte();

            if ($extraDataFlag & self::EDF_GAME_PORT) {
                $this->info['serverPort'] = $this->contentData->getShort();
            }

            if ($extraDataFlag & self::EDF_SERVER_ID) {
                $this->info['serverId'] = $this->contentData->getUnsignedLong() | ($this->contentData->getUnsignedLong() << 32);
            }

            if ($extraDataFlag & self::EDF_SOURCE_TV) {
                $this->info['tvPort'] = $this->contentData->getShort();
                $this->info['tvName'] = $this->contentData->getString();
            }

            if ($extraDataFlag & self::EDF_SERVER_TAGS) {
                $this->info['serverTags'] = $this->contentData->getString();
            }

            if ($extraDataFlag & self::EDF_GAME_ID) {
                $this->info['gameId'] = $this->contentData->getUnsignedLong() | ($this->contentData->getUnsignedLong() << 32);
            }
        }
    }

}
