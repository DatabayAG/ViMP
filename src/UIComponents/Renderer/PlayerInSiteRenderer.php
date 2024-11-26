<?php

declare(strict_types=1);

namespace srag\Plugins\ViMP\UIComponents\Renderer;

use ilViMPPlugin;
use ILIAS\DI\Container;
use ilTemplateException;
use Throwable;
use xvmpException;
use srag\Plugins\ViMP\UIComponents\PlayerModal\PlayerContainerDTO;
use ILIAS\UI\Component\Component;
use ilTemplate;
use srag\Plugins\ViMP\Content\MediumMetadataParser;

/**
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class PlayerInSiteRenderer
{
    public const TEMPLATE_PATH = __DIR__ . '/../../../templates/default/tpl.player_in_site.html';
    public const TEMPLATE_PATH_UNAVAILABLE = __DIR__ . '/../../../templates/default/tpl.video_not_available.html';
    public const DATE_FORMAT = 'd.m.Y';

    /**
     * @var ilViMPPlugin
     */
    private ilViMPPlugin $plugin;
    /**
     * @var MediumMetadataParser
     */
    private MediumMetadataParser $metadata_parser;

    /**
     * @var Container
     */
    protected Container $dic;

    /**
     * @param MediumMetadataParser $metadata_parser
     * @param Container            $dic
     * @param ilViMPPlugin         $plugin
     */
    public function __construct(MediumMetadataParser $metadata_parser, Container $dic, ilViMPPlugin $plugin)
    {
        $this->dic = $dic;
        $this->plugin = $plugin;
        $this->metadata_parser = $metadata_parser;
    }

    /**
     * @throws ilTemplateException
     * @throws xvmpException|Throwable
     */
    public function render(PlayerContainerDTO $playerContainerDTO, bool $deleted): string
    {
        $tpl = new ilTemplate(self::TEMPLATE_PATH, true, true);
        $tpl->setVariable('VIDEO_PLAYER', $playerContainerDTO->getMediumMetadata()->isAvailable() && !$deleted && !$playerContainerDTO->getMediumMetadata()->isTranscoding() ?
            $playerContainerDTO->getVideoPlayer()->getHTML()
            : $this->renderUnavailablePlayer($playerContainerDTO));
        $tpl->setVariable('TITLE', $playerContainerDTO->getMediumMetadata()->getTitle());
        $tpl->setVariable('DESCRIPTION', nl2br($playerContainerDTO->getMediumMetadata()->getDescription(), false));

        if (!$playerContainerDTO->getMediumMetadata()->isAvailable() || $deleted) {
            $tpl->setCurrentBlock('info_message');
            $tpl->setVariable('INFO_MESSAGE', $this->plugin->txt('info_not_available'));
            $tpl->parseCurrentBlock();
        } elseif ($playerContainerDTO->getMediumMetadata()->isTranscoding()) {
            $tpl->setCurrentBlock('info_message');
            $tpl->setVariable('INFO_MESSAGE', $this->plugin->txt('info_transcoding_full'));
            $tpl->parseCurrentBlock();
        } elseif ($playerContainerDTO->getMediumMetadata()->hasAvailability()) {
            $tpl->setCurrentBlock('medium_info');
            $tpl->setVariable('VALUE', $this->plugin->txt('available') . ': ' .
                $this->metadata_parser->parseAvailability(
                    $playerContainerDTO->getMediumMetadata()->getAvailabilityStart(),
                    $playerContainerDTO->getMediumMetadata()->getAvailabilityEnd(),
                    true
                ));
            $tpl->parseCurrentBlock();
        }

        if (!$deleted) {
            foreach ($playerContainerDTO->getMediumMetadata()->getMediumAttributes() as $mediumAttribute) {
                $tpl->setCurrentBlock('medium_info');
                $tpl->setVariable('VALUE', $mediumAttribute->getTitle() ?
                    $mediumAttribute->getTitle() . ': ' . $mediumAttribute->getValue() :
                    $mediumAttribute->getValue());
                $tpl->parseCurrentBlock();
            }

            if ($playerContainerDTO->getMediumMetadata()->isAvailable()) {
                foreach ($playerContainerDTO->getButtons() as $button) {
                    $tpl->setCurrentBlock('button');
                    $tpl->setVariable('BUTTON', $this->renderComponent($button, false));
                    $tpl->parseCurrentBlock();
                }
            }
        }

        return $tpl->get();
    }

    /**
     * @param Component|Component[] $component
     * @param bool $async
     * @return string
     */
    protected function renderComponent($component, bool $async): string
    {
        return $async ? $this->dic->ui()->renderer()->renderAsync($component)
            : $this->dic->ui()->renderer()->render($component);
    }

    /**
     * @throws ilTemplateException
     */
    private function renderUnavailablePlayer(PlayerContainerDTO $playerContainerDTO): string
    {
        $tpl = new ilTemplate(self::TEMPLATE_PATH_UNAVAILABLE, true, true);
        $tpl->setVariable('THUMBNAIL', $playerContainerDTO->getMediumMetadata()->getThumbnailUrl());
        return $tpl->get();
    }

}
