<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2014, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'exceptions/SocketException.php';

/**
 * This exception class is used to indicate a connection reset by the server
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage exceptions
 */
class ConnectionResetException extends SocketException {

    /**
     * Create a new instance
     */
    public function __construct() {
        parent::__construct("Connection reset by peer");
    }

}
