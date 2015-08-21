<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use SteamCondenser\Exceptions\WebApiException;

/**
 * This adds support for Steam Web API to classes needing this functionality.
 * The Web API requires you to register a domain with your Steam account to
 * acquire an API key. See http://steamcommunity.com/dev for further details.
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class WebApi implements LoggerAwareInterface {

    /**
     * @var LoggerInterface The logger for this class
     */
    private $logger;

    /**
     * @var string
     */
    private static $apiKey = null;

    /**
     * @var WebApi
     */
    protected static $instance = null;

    /**
     * @var bool
     */
    protected static $secure = true;

    /**
     * Returns the Steam Web API key
     *
     * @return string The Steam Web API key
     */
    public static function getApiKey() {
        return self::$apiKey;
    }

    /**
     * Returns a raw list of interfaces and their methods that are available in
     * Steam's Web API
     *
     * This can be used for reference when accessing interfaces and methods
     * that have not yet been implemented by Steam Condenser.
     *
     * @return array The list of interfaces and methods
     */
    public static function getInterfaces() {
        $data = self::getJSONObject('ISteamWebAPIUtil', 'GetSupportedAPIList');
        return $data->apilist->interfaces;
    }

    /**
     * Fetches JSON data from Steam Web API using the specified interface,
     * method and version. Additional parameters are supplied via HTTP GET.
     *
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return string Data is returned as a JSON-encoded string.
     */
    public static function getJSON($interface, $method, $version = 1, $params = null) {
        return self::load('json', $interface, $method, $version, $params);
    }

    /**
     * Fetches JSON data from Steam Web API using the specified interface,
     * method and version. Additional parameters are supplied via HTTP GET.
     *
     * This will also check for some common error codes that are provided by
     * some Web API interface methods.
     *
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return \stdClass Data is returned as a json_decoded object
     * @throws WebApiException In case of any request failure
     */
    public static function getJSONData($interface, $method, $version = 1, $params = null) {
        $result = self::getJSONObject($interface, $method, $version, $params)->result;

        if($result->status != 1) {
            throw new WebApiException(WebApiException::STATUS_BAD, $result->status, $result->statusDetail);
        }

        return $result;
    }

    /**
     * Fetches JSON data from Steam Web API using the specified interface,
     * method and version. Additional parameters are supplied via HTTP GET.
     *
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return \stdClass Data is returned as a json_decoded object
     * @throws WebApiException In case of any request failure
     */
    public static function getJSONObject($interface, $method, $version = 1, $params = null) {
        return json_decode(self::getJSON($interface, $method, $version, $params));
    }

    /**
     * Fetches data from Steam Web API using the specified interface, method
     * and version. Additional parameters are supplied via HTTP GET. Data is
     * returned as a String in the given format.
     *
     * @param string $format The format to load from the API ('json', 'vdf', or
     *        'xml')
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return string Data is returned as a String in the given format (which
     *                may be 'json', 'vdf' or 'xml').
     */
    public static function load($format, $interface, $method, $version = 1, $params = null) {
        return self::instance()->_load($format, $interface, $method, $version, $params);
    }

    /**
     * Sets whether HTTPS should be used for the communication with the Web API
     *
     * @param bool $secure Whether to use HTTPS
     */
    public static function setSecure($secure) {
        self::$secure = $secure;
    }

    /**
     * Returns a singleton instance of an internal <var>WebApi</var> object
     *
     * @return WebApi The internal <var>WebApi</var> instance
     */
    private static function instance() {
        if (self::$instance == null) {
            self::$instance = new WebApi();
            self::$instance->setLogger(\SteamCondenser\getLogger(get_class()));
        }

        return self::$instance;
    }

    /**
     * Sets the Steam Web API key
     *
     * @param string $apiKey The 128bit API key that has to be requested from
     *                      http://steamcommunity.com/dev
     * @throws WebApiException if the given API key is not a valid 128bit
     *         hexadecimal string
     */
    public static function setApiKey($apiKey) {
        if($apiKey != null && !preg_match('/^[0-9A-F]{32}$/', $apiKey)) {
            throw new WebApiException(WebApiException::INVALID_KEY);
        }

        self::$apiKey = $apiKey;
    }

    /**
     * Private constructor to prevent direct usage of <var>WebApi</var>
     * instances
     */
    private function __construct() {}

    /**
     * Fetches data from Steam Web API using the specified interface, method
     * and version. Additional parameters are supplied via HTTP GET. Data is
     * returned as a String in the given format.
     *
     * @param string $format The format to load from the API ('json', 'vdf', or
     *        'xml')
     * @param string $interface The Web API interface to call, e.g. ISteamUser
     * @param string $method The Web API method to call, e.g.
     *        GetPlayerSummaries
     * @param int $version The API method version to use
     * @param array $params Additional parameters to supply via HTTP GET
     * @return string Data is returned as a String in the given format (which
     *                may be 'json', 'vdf' or 'xml').
     */
    protected function _load($format, $interface, $method, $version = 1, $params = null) {
        $protocol = (self::$secure) ? 'https' : 'http';
        $url = "$protocol://api.steampowered.com/$interface/$method/v$version/";

        $params['format'] = $format;
        if (self::$apiKey != null) {
            $params['key'] = self::$apiKey;
        }

        if($params != null && !empty($params)) {
            $url .= '?';
            $url_params = [];
            foreach($params as $k => $v) {
                $url_params[] = "$k=$v";
            }
            $url .= join('&', $url_params);
        }

        return $this->request($url);
    }

    /**
     * Fetches data from Steam Web API using the specified URL
     *
     * @param string $url The URL to load
     * @return string The data returned by the Web API
     * @throws WebApiException if the request failed
     */
    protected function request($url) {
        $this->logger->debug("Querying Steam Web API: " . str_replace(self::$apiKey, 'SECRET', $url));

        $data = file_get_contents($url);

        if(empty($data)) {
            preg_match('/^.* (\d{3}) (.*)$/', $http_response_header[0], $http_status);

            if($http_status[1] == 401) {
                throw new WebApiException(WebApiException::UNAUTHORIZED);
            }

            throw new WebApiException(WebApiException::HTTP_ERROR, $http_status[1], $http_status[2]);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

}
