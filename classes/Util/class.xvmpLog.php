<?php

declare(strict_types=1);

/**
 * Class xvmpLog
 * @author     Theodor Truffer <tt@studer-raimann.ch>
 * @deprecated use xvmpCurlLog
 */
class xvmpLog extends ilLog
{
    public const LOG_TITLE = 'vimp.log';

    protected static xvmpLog $instance;

    public static function getFullPath() : string
    {
        $log = self::getInstance();

        return $log->getLogDir() . '/' . $log->getLogFile();
    }

    public static function getInstance() : xvmpLog
    {
        if (!isset(self::$instance)) {
            if (ILIAS_LOG_DIR === "php:/" && ILIAS_LOG_FILE === "stdout") {
                // Fix Docker-ILIAS log
                self::$instance = new self(ILIAS_LOG_DIR, ILIAS_LOG_FILE);
            } else {
                self::$instance = new self(ILIAS_LOG_DIR, self::LOG_TITLE);
            }
        }

        return self::$instance;
    }

    public function getLogDir() : string
    {
        return ILIAS_LOG_DIR;
    }

    public function getLogFile() : string
    {
        if (ILIAS_LOG_DIR === "php:/" && ILIAS_LOG_FILE === "stdout") {
            // Fix Docker-ILIAS log
            return ILIAS_LOG_FILE;
        } else {
            return self::LOG_TITLE;
        }
    }

    public function writeTrace() : void
    {
        try {
            throw new Exception();
        } catch (Exception $e) {
            parent::write($e->getTraceAsString());
        }
    }

}
