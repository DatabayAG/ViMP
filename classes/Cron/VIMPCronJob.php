<?php

namespace Cron;

use ilCronJob;
use ilCronJobResult;
use ilLogger;
use ilTestCronInterfacePlugin;
use ILIAS\Cron\Schedule\CronJobScheduleType;
use srag\Plugins\ViMP\Cron\ViMPJob;
use ilViMPPlugin;

class VIMPCronJob extends ilCronJob
{

    private ilViMPPlugin $plugin;
    private $logger;

    public function __construct(ilViMPPlugin $plugin, $logger)
    {
        $this->plugin = $plugin;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getId() : string
    {
        return 'vimpcronjob';
    }

    /**
     * @inheritDoc
     */
    public function hasAutoActivation() : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasFlexibleSchedule() : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScheduleType() : \ILIAS\Cron\Schedule\CronJobScheduleType
    {
        return \ILIAS\Cron\Schedule\CronJobScheduleType::SCHEDULE_TYPE_DAILY;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScheduleValue() : int
    {
        return 1;
    }

    /**
     * @return bool
     */
    public function isManuallyExecutable() : bool
    {
        return defined('DEVMODE') && (bool) DEVMODE;
    }

    /**
     * @inheritDoc
     */
    public function run() : ilCronJobResult
    {
        return (new ViMPJob())->run();
    }

    public function getTitle(): string
    {
        return $this->plugin->txt("cron_title");
    }

    public function getDescription(): string
    {
        return $this->plugin->txt("cron_description");
    }

}
