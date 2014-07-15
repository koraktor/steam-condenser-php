<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2014, Sebastian Staudt
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
        if (!$xml = @file_get_contents($url)) {
            preg_match('/^.* (\d{3}) (.*)$/', $http_response_header[0], $http_status);
            $errorMessage = "Failed to retrieve XML data because of an HTTP error: {$http_status[1]} (status code: {$http_status[0]})";
            throw new SteamCondenserException($errorMessage, 0);
        }

        try {
            return @new SimpleXMLElement($xml);
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
