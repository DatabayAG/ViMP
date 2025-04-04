<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Cache\Container\BaseRequest;

/**
 * Class xvmpCacheFactory
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpCacheFactory
{
    private static  $cache_instance = null;

    public static function getInstance()
    {
        global $DIC;

        if (self::$cache_instance === null) {
            // 5.2 and 5.3 have the same cache methods
            // add switch statement if needed in further versions
            self::$cache_instance = $DIC->globalCache()->get(new xvmpCache());
          #  self::$cache_instance->init();
        }

        return self::$cache_instance;

    }
}
