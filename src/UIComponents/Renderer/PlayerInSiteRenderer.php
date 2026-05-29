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
    public const TEMPLATE_PATH = 'tpl.player_in_site.html';
    public const TEMPLATE_PATH_UNAVAILABLE =  'tpl.video_not_available.html';
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
        $tpl = new ilTemplate(self::TEMPLATE_PATH, true, true, $this->plugin->getDirectory());
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
            // Availability row with label and value
            $tpl->setCurrentBlock('info_label');
            $tpl->setVariable('INFO_LABEL', $this->plugin->txt('available'));
            $tpl->parseCurrentBlock();

            $tpl->setCurrentBlock('info_paragraph');
            $tpl->setVariable('INFO', $this->metadata_parser->parseAvailability(
                $playerContainerDTO->getMediumMetadata()->getAvailabilityStart(),
                $playerContainerDTO->getMediumMetadata()->getAvailabilityEnd(),
                false
            ));
            $tpl->parseCurrentBlock();

            $tpl->setCurrentBlock('info_row');
            $tpl->parseCurrentBlock();
        }

        if (!$deleted) {
            foreach ($playerContainerDTO->getMediumMetadata()->getMediumAttributes() as $mediumAttribute) {
                if ($mediumAttribute->getTitle()) {
                    $tpl->setCurrentBlock('info_label');
                    $tpl->setVariable('INFO_LABEL', $mediumAttribute->getTitle());
                    $tpl->parseCurrentBlock();
                }
                $tpl->setCurrentBlock('info_paragraph');
                $tpl->setVariable('INFO', $mediumAttribute->getValue());
                $tpl->parseCurrentBlock();

                $tpl->setCurrentBlock('info_row');
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
        $tpl = new ilTemplate(self::TEMPLATE_PATH_UNAVAILABLE, true, true, $this->plugin->getDirectory());
        $tpl->setVariable('THUMBNAIL', $playerContainerDTO->getMediumMetadata()->getThumbnailUrl());
        return $tpl->get();
    }

}
