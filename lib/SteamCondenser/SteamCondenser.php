<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2014, Sebastian Staudt
 *
 * @author  Sebastian Staudt
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package steam-condenser
 */

namespace SteamCondenser;

use \Monolog\Logger;
use \Monolog\Handler\ErrorLogHandler;

class SteamCondenser {

    const VERSION = '1.3.10';

    const LEVEL_DEBUG       = Logger::DEBUG;
    const LEVEL_INFO        = Logger::INFO;
    const LEVEL_NOTICE      = Logger::NOTICE;
    const LEVEL_WARNING     = Logger::WARNING;
    const LEVEL_ERROR       = Logger::ERROR;
    const LEVEL_CRITICAL    = Logger::CRITICAL;
    const LEVEL_ALERT       = Logger::ALERT;
    const LEVEL_EMERGENCY   = Logger::EMERGENCY;

    /**
     * @var \Monolog\Logger
     */
    protected $log = null;

    /**
     * @param int $loglevel
     */
    protected function __construct($loglevel = self::LEVEL_DEBUG)
    {
        $this->log = new Logger('SteamCondenser');
        $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $loglevel);
        $this->log->pushHandler($handler);
    }

    /**
     * @return \Monolog\Logger
     */
    protected function log()
    {
        return $this->log;
    }
}
