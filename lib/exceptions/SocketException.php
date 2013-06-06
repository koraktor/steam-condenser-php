<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2013, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/SteamCondenserException.php';

/**
 * This exception class is used to indicate errors while commmunicating through
 * UDP or TCP sockets
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage exceptions
 */
class SocketException extends SteamCondenserException {

    /**
     * @var int
     */
    protected $errorCode;

    /**
     * Create a new instance with an error code provided by the sockets
     * extension or with the given message.
     *
     * @param int|string $errorCode The error code or message
     * @see socket_lasterror()
     * @see socket_strerror()
     */
    public function __construct($errorCode) {
        if (is_string($errorCode)) {
            $errorMessage = $errorCode;
            $errorCode = null;
        } else {
            $this->errorCode = $errorCode;
            $errorMessage = socket_strerror($errorCode) . " (Code: $errorCode)";
        }

        parent::__construct($errorMessage, $errorCode);
    }

}
