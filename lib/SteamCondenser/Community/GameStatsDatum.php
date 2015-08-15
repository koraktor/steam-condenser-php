<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

use SteamCondenser\Community\GameStatsDatum\Instance;

class GameStatsDatum {

    /**
     * @var string
     */
    protected $apiName;

    /**
     * @var mixed
     */
    protected $defaultValue;

    /**
     * @var GameStatsSchema
     */
    protected $schema;

    /**
     * @param GameStatsSchema $schema
     * @param \stdClass $data
     * */
    public function __construct(GameStatsSchema $schema, \stdClass $data) {
        $this->apiName = $data->name;
        $this->defaultValue = $data->defaultvalue;
        $this->schema = $schema;
    }

    public function __toString() {
        return sprintf("%s{%s (%s)}", get_class(), $this->apiName, $this->defaultValue);
    }

    public function getInstance($user, $value) {
        return new Instance($this, $user, $value);
    }

    public function getName($language = null) {
        $language = $language ?: GameStatsSchema::getDefaultLanguage();
        $this->schema->getDatumNames($language)[$this->apiName];
    }



}

