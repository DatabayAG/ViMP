<?php

declare(strict_types=1);

use ILIAS\Refinery\Transformation;
use ILIAS\Refinery\Factory;
use ILIAS\Cache\Container\BaseRequest;

/**
 * Class xvmpCache
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 * @version 1.0.0
 */
class xvmpCache extends ILIAS\Cache\Container\BaseRequest
{
    const CACHE_KEY_VIMP = 'VIMP';
    private Factory $refinery;
    private static $instance = null;

    public function __construct()
    {
        global $DIC;
        $this->refinery = $DIC->refinery();
        parent::__construct(self::CACHE_KEY_VIMP);
    }

    public static function getInstance()
    {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getContainer() {
        return $this->container;
    }

    public function getContainerKey() : string
    {
        return self::CACHE_KEY_VIMP;
    }

    public function isForced() : bool
    {
        return true;
    }

}
