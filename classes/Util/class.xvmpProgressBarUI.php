<?php

declare(strict_types=1);

use ILIAS\DI\Container;

/**
 * Class xvmpProgressBarUI
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpProgressBarUI
{
    private static int $version = 1;
    /**
     * @var ilPlugin
     */
    private ilPlugin $plugin;
    /**
     * @var int
     */
    protected int $mid;
    /**
     * @var ilTemplate
     */
    protected ilTemplate $tpl;
    /**
     * @var Container
     */
    protected Container $dic;

    protected static bool $js_loaded = false;

    /**
     * xvmpProgressBarUI constructor.
     * @param int $mid
     * @param ilPlugin $plugin
     * @param Container $dic
     * @throws ilCtrlException
     */
    public function __construct(int $mid, ilPlugin $plugin, Container $dic)
    {
        $this->dic = $dic;
        $this->mid = $mid;
        $this->tpl = $plugin->getTemplate('default/tpl.progress_bar.html');
        $this->plugin = $plugin;
        $this->addJS();
    }

    /**
     * @throws ilCtrlException
     */
    protected function addJS() : void
    {
        if (!self::$js_loaded) {
            $this->dic->ui()->mainTemplate()->addJavaScript(
                $this->plugin->getDirectory() . '/templates/js/xvmp_progress_bar.min.js?v=' . self::$version
            );
            $this->dic->ui()->mainTemplate()->addOnLoadCode('VimpProgressBar.lng.transcoded = "' .
                $this->plugin->txt('status_legal') . '";');
            self::$js_loaded = true;
        }
        $this->dic->ctrl()->setParameterByClass(ilObjViMPGUI::class, ilObjViMPGUI::GET_VIDEO_ID, $this->mid);
        $url = $this->dic->ctrl()->getLinkTargetByClass(ilObjViMPGUI::class, ilObjViMPGUI::CMD_TRANSCODING_PROGRESS);
        $this->dic->ui()->mainTemplate()->addOnLoadCode('VimpProgressBar.init(' . $this->mid . ', "' . $url . '");');
    }

    /**
     * @return string
     * @throws ilTemplateException
     */
    public function getHTML(): string
    {
        $this->tpl->setVariable('TEXT_TRANSCODING', $this->plugin->txt('transcoding'));
        $this->tpl->setVariable('MID', $this->mid);
        try {
            $progress = xvmpRequest::getTranscodingProgress($this->mid, 1);
        } catch (xvmpException $e) {
            xvmpCurlLog::getInstance()->logError((string) $e->getCode(), $e->getMessage());
            $progress = '...';
        }
        $this->tpl->setVariable('PROGRESS', $progress);

        return $this->tpl->get();
    }
}
