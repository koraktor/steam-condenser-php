<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community\GameStatsDatum;

use SteamCondenser\Community\GameStatsDatum;
use SteamCondenser\Community\SteamId;

class Instance {

    /**
     * @var GameStatsDatum
     */
    protected $datum;

    /**
     * @var SteamId
     */
    protected $user;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param GameStatsDatum $datum
     * @param SteamId $user
     * @param mixed $value
     */
    public function __construct(GameStatsDatum $datum, SteamId $user, $value) {
        $this->datum = $datum;
        $this->user = $user;
        $this->value = $value;
    }

    /**
     * @return GameStatsDatum
     */
    public function getDatum() {
        return $this->datum;
    }

    /**
     * @return SteamId
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    public function __toString() {
        return sprintf("%s{%s (%s)}", get_class(), $this->datum->getApiName(), $this->value);
    }

}
