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

/**
 * This trait implements caching functionality to be used in any object class
 * having one or more unique object identifier (i.e. ID) and using a
 * <var>fetch()</var> method to fetch data, e.g. using a HTTP download.
 *
 * @package SteamCondenser\Community
 */
trait Cacheable {

    /**
     * Contains the current cache for the cacheable class
     *
     * @var array
     */
    public static $cache = [];

    /**
     * Contains the list of IDs used for caching objects
     *
     * @var array
     */
    protected static $cacheIds;

    /**
     * @var string
     */
    protected static $className;

    /**
     * @var int
     */
    protected $fetchTime;

    /**
     * Defines the instance variables which should be used to index the cached
     * objects
     *
     * The parameters provided will be used as the symbolic names of the
     * instance variables representing a unique identifier for this object
     * class
     */
    protected static function cacheableWithIds() {
        self::$cacheIds = func_get_args();
    }

    /**
     * Clears the object cache for the class this method is called on
     */
    public static function clearCache() {
        self::$cache = [];
    }

    /**
     * Creates a new instance of the cacheable object or returns an already
     * cached one
     *
     * The parameters of this method are derived from the constructor of the
     * cacheable class. Additionally, the following parameters are dynamically
     * added:
     *
     * @param bool $fetch If <var>true</var> the object’s <var>fetch()</var>
     *        method will be called
     * @param bool $bypassCache If <var>true</var> the cache will not be hit
     * @return mixed A new object or a matching cached one
     */
    public static function create() {
        $args = func_get_args();
        $className = empty(self::$className) ? get_class() : self::$className;
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        $arity = $constructor->getNumberOfParameters();

        if (sizeof($args) < $arity) {
            array_fill(0, $arity, null);
        }
        $bypassCache = (sizeof($args) > $arity + 1) ? array_pop($args) : false;
        $fetch = (sizeof($args) > $arity) ? array_pop($args) : true;

        $object = $class->newInstanceWithoutConstructor();
        $constructor->setAccessible(true);
        $constructor->invokeArgs($object, $args);
        $cachedObject = $object->cachedInstance();
        if ($cachedObject != null && !$bypassCache) {
            $object = $cachedObject;
        }

        if ($fetch && ($bypassCache || !$object->isFetched())) {
            $object->fetch();
            $object->cache();
        }

        return $object;
    }

    /**
     * If available, returns the cached instance for the object it is called on
     *
     * This may be used to either replace an initial object with a completely
     * cached instance of the same ID or to compare a modified object with the
     * copy that was cached before.
     *
     * @see cacheIds()
     */
    protected function cachedInstance() {
        $findInstance = function($id, $cache) use (&$findInstance) {
            self::selectIds($id, $ids);

            if (array_key_exists($id, $cache)) {
                return (empty($ids)) ?
                        $cache[$id] : $findInstance($id, $cache[$id]);
            }

            return null;
        };

        foreach ($this->cacheIds() as $id) {
            $instance = $findInstance($id, self::$cache);
            if ($instance != null) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * Returns whether an object with the given ID is already cached
     *
     * @param mixed $id The ID of the desired object
     * @return bool <var>true</var> if the object with the given ID is already
     *         cached
     */
    public static function isCached($id) {
        $findId = function($id, $cache) use (&$findId) {
            self::selectIds($id, $ids);

            if (array_key_exists($id, $cache)) {
                return (is_array($ids)) ? $findId($id, $cache[$id]) : true;
            }

            return false;
        };

        return $findId($id, self::$cache);
    }

    protected static function overwriteClass($className) {
        self::$className = $className;
    }

    protected static function selectIds(&$id, &$ids) {
        if (is_array($id)) {
            $ids = $id;
            $id = array_shift($ids);
        } else {
            $ids = null;
        }

        if (is_string($id)) {
            $id = strtolower($id);
        }
    }

    /**
     * Saves this object in the cache
     *
     * This will use the ID attributes selected for caching
     */
    protected function cache() {
        $cacheInstance = function($id, &$cache) use (&$cacheInstance) {
            self::selectIds($id, $ids);

            if (empty($ids)) {
                $cache[$id] = $this;
            } else {
                $cacheInstance($ids, $cache[$id]);
            }
        };

        foreach ($this->cacheIds() as $cacheId) {
            $cacheInstance($cacheId, self::$cache);
        }
    }

    /**
     * Returns a complete list of all values for the cache IDs of the cacheable
     * object
     *
     * @return array The values for the cache IDs
     */
    protected function cacheIds() {
        $values = function($id) use (&$values) {
            return is_array($id) ? array_map($values, $id) : $this->{$id};
        };

        return array_map($values, self::$cacheIds);
    }

    /**
     * Fetches the object from some data source
     *
     * @see internalFetch()
     */
    public function fetch() {
        $this->internalFetch();
        $this->fetchTime = time();
    }

    /**
     * Returns the timestamp the object's data has been fetched the last time
     *
     * @return int The timestamp the object has been updated the last time
     */
    public function getFetchTime() {
        return $this->fetchTime;
    }

    /**
     * Returns whether the data for this object has already been fetched
     *
     * @return bool <var>true</var> if this object's data is available
     */
    public function isFetched() {
        return $this->fetchTime != null;
    }

    /**
     * Fetches the object from some data source
     *
     * This method should be overridden in cacheable object classes and should
     * implement the logic to retrieve the object's data. Updating the time is
     * handled dynamically and does not need to be
     * implemented separately.
     *
     * @see fetch()
     */
    abstract protected function internalFetch();

}
