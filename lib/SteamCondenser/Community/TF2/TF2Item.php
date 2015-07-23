<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2010-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community\TF2;

use SteamCondenser\Community\GameItem;

/**
 * Represents a Team Fortress 2 item
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
class TF2Item extends GameItem {

    /**
     * @var array The names of the Team Fortress 2 classes
     */
    private static $CLASSES = ['scout', 'sniper', 'soldier', 'demoman',
                                    'medic', 'heavy', 'pyro', 'spy'];

    /**
     * @var array
     */
    private $equipped;

    /**
     * Creates a new instance of a TF2Item with the given data
     *
     * @param TF2Inventory $inventory The inventory this item is contained in
     * @param \stdClass $itemData The data specifying this item
     * @throws \SteamCondenser\Exceptions\WebApiException on Web API errors
     */
    public function __construct(TF2Inventory $inventory, $itemData) {
        parent::__construct($inventory, $itemData);

        $this->equipped = [];
        foreach(self::$CLASSES as $classId => $className) {
            $this->equipped[$className] = ($itemData->inventory & (1 << 16 + $classId) != 0);
        }
    }

    /**
     * Returns the class symbols for each class this player has equipped this
     * item
     *
     * @return array The names of the classes this player has equipped this
     *         item
     */
    public function getClassesEquipped() {
        $classesEquipped = [];
        foreach($this->equipped as $classId => $equipped) {
            if($equipped) {
                $classesEquipped[] = $classId;
            }
        }

        return $classesEquipped;
    }

    /**
     * Returns whether this item is equipped by this player at all
     *
     * @return bool <var>true</var> if the player has equipped this item at all
     */
    public function isEquipped() {
        return in_array(true, $this->equipped);
    }

}
