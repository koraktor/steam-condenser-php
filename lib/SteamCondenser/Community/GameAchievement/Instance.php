<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community\GameAchievement;

use SteamCondenser\Community\GameAchievement;
use SteamCondenser\Community\SteamId;

class Instance {

    /**
     * @var GameAchievement
     */
    protected $achievement;

    /**
     * @var bool
     */
    protected $unlocked;

    /**
     * @var SteamId
     */
    protected $user;

    /**
     * @param GameAchievement $achievement
     * @param SteamId $user
     * @param bool $unlocked
     */
    public function __construct(GameAchievement $achievement, SteamId $user, $unlocked) {
        $this->achievement = $achievement;
        $this->unlocked = $unlocked;
        $this->user = $user;
    }

    /**
     * @return GameAchievement
     */
    public function getAchievement() {
        return $this->achievement;
    }

    /**
     * @return SteamId
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function isUnlocked() {
        return $this->unlocked;
    }

    public function __toString() {
        return sprintf("%s{%s unlocked=%s}", get_class(), $this->achievement->getApiName(), $this->unlocked);
    }
}
