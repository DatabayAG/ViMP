<?php

declare(strict_types=1);

namespace srag\Plugins\ViMP\UIComponents\Renderer;

use ilTemplate;
use ilTemplateException;
use srag\Plugins\ViMP\Content\MediumMetadataDTO;
use ilViMPPlugin;
use ILIAS\DI\Container;
use xvmpMedium;
use srag\Plugins\ViMP\Content\MediumMetadataParser;

/**
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
abstract class ContentElementRenderer
{
    public const TEMPLATE = 'tpl.content_element.html';
    public const DATE_FORMAT = 'd.m.Y';
    public const DESCRIPTION_LENGTH = 30;
    /**
     * @var MediumMetadataParser
     */
    protected MediumMetadataParser $metadata_parser;
    /**
     * @var ilViMPPlugin
     */
    protected ilViMPPlugin $plugin;

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
     */
    protected function buildInnerTemplate(MediumMetadataDTO $mediumMetadataDTO): ilTemplate
    {
        $tpl = $this->getInnerTemplate();

        if ($mediumMetadataDTO->isAvailable()) {
            $tpl->touchBlock('play_overlay');
            if ($mediumMetadataDTO->hasAvailability()) { // is available but has availability
                $this->fillAvailabilityInfo($mediumMetadataDTO, $tpl);
            }
        } else {
            $this->fillAvailabilityOverlay($mediumMetadataDTO, $tpl);
        }

        $tpl->setVariable('MID', $mediumMetadataDTO->getMid());
        $tpl->setVariable('THUMBNAIL', $mediumMetadataDTO->getThumbnailUrl());
        $tpl->parseCurrentBlock();

        if (!$mediumMetadataDTO->isAvailable()) {
            $tpl->setCurrentBlock('info_message');
            $tpl->setVariable('INFO_MESSAGE', $this->plugin->txt('info_not_available'));
            $tpl->parseCurrentBlock();
        } elseif ($mediumMetadataDTO->isTranscoding()) {
            $tpl->setCurrentBlock('info_message');
            $tpl->setVariable('INFO_MESSAGE', $this->plugin->txt('info_transcoding_short'));
            $tpl->parseCurrentBlock();
        }

        $tpl->setVariable('TITLE', $mediumMetadataDTO->getTitle());
        $tpl->setVariable('DESCRIPTION', nl2br(strip_tags($mediumMetadataDTO->getDescription(static::DESCRIPTION_LENGTH)), false));
        $tpl->setVariable('LABEL_TITLE', $this->plugin->txt(xvmpMedium::F_TITLE));
        $tpl->setVariable('LABEL_DESCRIPTION', $this->plugin->txt(xvmpMedium::F_DESCRIPTION));

        $this->fillMediumInfos($mediumMetadataDTO, $tpl);
        return $tpl;
    }

    /**
     * @param MediumMetadataDTO $mediumMetadataDTO
     * @param ilTemplate $tpl
     * @throws ilTemplateException
     */
    protected function fillMediumInfos(MediumMetadataDTO $mediumMetadataDTO, ilTemplate $tpl) : void
    {
        foreach ($mediumMetadataDTO->getMediumAttributes() as $mediumAttribute) {
            $tpl->setCurrentBlock('info_paragraph');
            $tpl->setVariable('INFO', $mediumAttribute->getTitle() ?
                $mediumAttribute->getTitle() . ': ' . $mediumAttribute->getValue() :
                $mediumAttribute->getValue());
            $tpl->parseCurrentBlock();
        }
    }

    /**
     * @throws ilTemplateException
     */
    protected function fillAvailabilityInfo(MediumMetadataDTO $mediumMetadataDTO, ilTemplate $tpl) : void
    {
        $tpl->setCurrentBlock('info_paragraph');
        $tpl->setVariable('INFO', $this->metadata_parser->parseAvailability(
            $mediumMetadataDTO->getAvailabilityStart(),
            $mediumMetadataDTO->getAvailabilityEnd(),
            false
        ));
        $tpl->parseCurrentBlock();
    }

    /**
     * @throws ilTemplateException
     */
    protected function fillAvailabilityOverlay(MediumMetadataDTO $mediumMetadataDTO, ilTemplate $tpl) : void
    {
        $tpl->setCurrentBlock('not_available_overlay');
        $tpl->setVariable('AVAILABILITY', $this->metadata_parser->parseAvailability(
            $mediumMetadataDTO->getAvailabilityStart(),
            $mediumMetadataDTO->getAvailabilityEnd(),
            false
        ));
        $tpl->parseCurrentBlock();
    }

    /**
     * @throws ilTemplateException
     */
    public function render(MediumMetadataDTO $mediumMetadataDTO): string
    {
        $tpl = $this->buildTemplate($mediumMetadataDTO);
        return $tpl->get();
    }

    protected function getContainerTemplate(): ilTemplate
    {
        return new ilTemplate(self::TEMPLATE, true, true, $this->plugin->getDirectory());
    }

    abstract protected function buildTemplate(MediumMetadataDTO $mediumMetadataDTO): ilTemplate;

    abstract protected function getInnerTemplate(): ilTemplate;

}
