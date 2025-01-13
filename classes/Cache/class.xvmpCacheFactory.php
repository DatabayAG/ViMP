<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpCacheFactory
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpCacheFactory
{
    private static ?xvmpCache $cache_instance = null;

    /**
     * Generates a new instance of the service.
     * @return xvmpCache|null
     */
    public static function getInstance() : ?xvmpCache
    {

        if (self::$cache_instance === null) {
            // 5.2 and 5.3 have the same cache methods
            // add switch statement if needed in further versions
            self::$cache_instance = xvmpCache::getInstance('');
            self::$cache_instance->init();
        }

        return self::$cache_instance;

    }
}
