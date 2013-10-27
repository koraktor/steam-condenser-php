<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2013, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/SteamCondenserException.php';

/**
 * This class is used to streamline access to XML-based data resources
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class XMLData {

    /**
     * Loads XML data from the given URL and returns it parsed into a
     * <var>SimpleXMLElement</var>
     *
     * @param string $url The URL to load the data from
     * @return SimpleXMLElement The parsed XML data
     * @throws SteamCondenserException if the data cannot be parsed
     */
    protected function getData($url) {
        try {
            return @new SimpleXMLElement($url, 0, true);
        } catch (Exception $e) {
            $errorMessage = "XML could not be parsed: " . $e->getMessage();
            if ((float) phpversion() < 5.3) {
                throw new SteamCondenserException($errorMessage, 0);
            } else {
                throw new SteamCondenserException($errorMessage, 0, $e);
            }
        }
    }

}
