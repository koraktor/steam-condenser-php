<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2015, Sebastian Staudt
 *
 * @author  Sebastian Staudt
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package steam-condenser
 */

namespace SteamCondenser;

use Monolog\Logger;

const VERSION = '1.3.9';

/**
 * Returns a Monolog logger with the given name
 *
 * @param string $name The name for the logger
 * @return Logger The requested Monolog logger
 */
function getLogger($name) {
    return new Logger($name);
}
