<?php

declare(strict_types=1);

namespace srag\Plugins\ViMP\UIComponents\Renderer;

use ILIAS\DI\Container;
use ILIAS\UI\Component\Component;
use ilTemplate;
use Throwable;
use xvmpException;
use ilTemplateException;
use xvmpConf;
use ilViMPPlugin;
use srag\Plugins\ViMP\UIComponents\PlayerModal\PlayerContainerDTO;
use srag\Plugins\ViMP\Content\MediumMetadataParser;

/**
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class PlayerModalRenderer
{
    public const TEMPLATE_PATH = 'tpl.player_modal.html';
    public const TEMPLATE_PATH_UNAVAILABLE = 'tpl.video_not_available.html';
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
    public function render(PlayerContainerDTO $playerContainerDTO, bool $async, bool $show_unavailable = false): string
    {
        $tpl = new ilTemplate(self::TEMPLATE_PATH, true, true, $this->plugin->getDirectory());
        $is_available = $playerContainerDTO->getMediumMetadata()->isAvailable() | $show_unavailable;
        $tpl->setVariable('VIDEO_PLAYER', $is_available ?
            $playerContainerDTO->getVideoPlayer()->getHTML()
            : $this->renderUnavailablePlayer($playerContainerDTO));

        $this->renderInfoMessage($playerContainerDTO, $tpl, $show_unavailable);

        if ($playerContainerDTO->getMediumMetadata()->hasAvailability()) {
            // label
            $tpl->setCurrentBlock('info_label');
            $tpl->setVariable('INFO_LABEL', $this->plugin->txt('available'));
            $tpl->parseCurrentBlock();
            // value
            $tpl->setCurrentBlock('info_paragraph');
            $tpl->setVariable('INFO', $this->metadata_parser->parseAvailability(
                $playerContainerDTO->getMediumMetadata()->getAvailabilityStart(),
                $playerContainerDTO->getMediumMetadata()->getAvailabilityEnd(),
                false
            ));
            $tpl->parseCurrentBlock();
            // wrap row
            $tpl->setCurrentBlock('info_row');
            $tpl->parseCurrentBlock();
        }

        foreach ($playerContainerDTO->getMediumMetadata()->getMediumAttributes() as $mediumAttribute) {
            if ($mediumAttribute->getTitle()) {
                $tpl->setCurrentBlock('info_label');
                $tpl->setVariable('INFO_LABEL', $mediumAttribute->getTitle());
                $tpl->parseCurrentBlock();
            }
            $tpl->setCurrentBlock('info_paragraph');
            $tpl->setVariable('INFO', $mediumAttribute->getValue());
            $tpl->parseCurrentBlock();
            // wrap row
            $tpl->setCurrentBlock('info_row');
            $tpl->parseCurrentBlock();
        }

        // description paragraph
        $tpl->setVariable('DESCRIPTION', nl2br($playerContainerDTO->getMediumMetadata()->getDescription(), false));

        foreach ($playerContainerDTO->getButtons() as $button) {
            $tpl->setCurrentBlock('button');
            $tpl->setVariable('BUTTON', $this->renderComponents($button, $async));
            $tpl->parseCurrentBlock();
        }
        return $tpl->get();
    }

    /**
     * @param PlayerContainerDTO $playerContainerDTO
     * @param ilTemplate $tpl
     * @param bool $available
     * @throws ilTemplateException
     */
    protected function renderInfoMessage(PlayerContainerDTO $playerContainerDTO, ilTemplate $tpl, bool $available) : void
    {
        if ($available === false) {
            $tpl->setCurrentBlock('info_message');
            $tpl->setVariable('INFO_MESSAGE', $this->plugin->txt('info_not_available'));
            $tpl->parseCurrentBlock();
        } elseif ($playerContainerDTO->getMediumMetadata()->isTranscoding()) {
            $msg = xvmpConf::getConfig(xvmpConf::F_EMBED_PLAYER) ? $this->plugin->txt('info_transcoding_full')
                : $this->plugin->txt('info_transcoding_possible_full');
            $tpl->setCurrentBlock('info_message');
            $tpl->setVariable('INFO_MESSAGE', $msg);
            $tpl->parseCurrentBlock();
        }
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

    /**
     * @param Component|Component[] $component
     * @param bool $async
     * @return string
     */
    protected function renderComponents($component, bool $async): string
    {
        return $async ? $this->dic->ui()->renderer()->renderAsync($component)
            : $this->dic->ui()->renderer()->render($component);
    }

}
