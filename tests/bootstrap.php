<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011-2014, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

error_reporting(E_ALL & ~E_USER_NOTICE | E_STRICT);

require_once dirname(__FILE__) . "/../vendor/autoload.php";

function getFixture($fileName) {
    return file_get_contents(dirname(__FILE__) . "/fixtures/$fileName");
}
