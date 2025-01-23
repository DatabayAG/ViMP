<?php

declare(strict_types=1);

class xvmpFileInputGUI extends ilFileInputGUI
{
    protected string $download_url;

    public function setDownloadUrl(string $download_url) : void
    {
        $this->download_url = $download_url;
    }
    public function render(string $a_mode = ""): string
    {
        $lng = $this->lng;

        $quota_exceeded = $quota_legend = false;

        $f_tpl = new ilTemplate("tpl.prop_file.html", true, true, "Services/Form");


        // show filename selection if enabled
        if ($this->isFileNameSelectionEnabled()) {
            $f_tpl->setCurrentBlock('filename');
            $f_tpl->setVariable('POST_FILENAME', $this->getFileNamePostVar());
            $f_tpl->setVariable('VAL_FILENAME', $this->getFilename());
            $f_tpl->setVariable('FILENAME_ID', $this->getFieldId());
            $f_tpl->setVariable('TXT_FILENAME_HINT', $lng->txt('if_no_title_then_filename'));
            $f_tpl->parseCurrentBlock();
        } else {
            if (trim($this->getValue()) != "") {
                if (!$this->getDisabled() && $this->getALlowDeletion()) {
                    $f_tpl->setCurrentBlock("delete_bl");
                    $f_tpl->setVariable("POST_VAR_D", $this->getPostVar());
                    $f_tpl->setVariable(
                        "TXT_DELETE_EXISTING",
                        $lng->txt("delete_existing_file")
                    );
                    $f_tpl->parseCurrentBlock();
                }

                $f_tpl->setCurrentBlock('prop_file_propval');
                //$f_tpl->setVariable('FILE_VAL', $this->getValue());
                /** BEGIN PATCH */
                //                $f_tpl->setVariable('FILE_VAL', $this->getValue());
                try {
                    $value = $this->download_url ?
                        '<a href="data:text/vtt;base64,'
                        . base64_encode(xvmpRequest::get($this->download_url)->getResponseBody())
                        . '" target="blank" download="' . $this->getValue() . '">' . $this->getValue() . '</a>' :
                        $this->getValue();
                } catch (xvmpException $e) {
                    xvmpCurlLog::getInstance()->writeWarning('could not download subtitle file from '
                        . $this->download_url . ', message: ' . $e->getMessage());
                    $value = $this->getValue();
                }
                $f_tpl->setVariable('FILE_VAL', $value);
                /** END PATCH */
                $f_tpl->parseCurrentBlock();
            }
        }

        if ($a_mode != "toolbar") {
            if (!$quota_exceeded) {
                $this->outputSuffixes($f_tpl);

                $f_tpl->setCurrentBlock("max_size");
                $f_tpl->setVariable("TXT_MAX_SIZE", $lng->txt("file_notice") . " " .
                    $this->getMaxFileSizeString());
                $f_tpl->parseCurrentBlock();

                if ($quota_legend) {
                    $f_tpl->setVariable("TXT_MAX_SIZE", true);
                    $f_tpl->parseCurrentBlock();
                }
            } else {
                $f_tpl->setCurrentBlock("max_size");
                $f_tpl->setVariable("TXT_MAX_SIZE", $quota_exceeded);
                $f_tpl->parseCurrentBlock();
            }
        } elseif ($quota_exceeded) {
            return $quota_exceeded;
        }

        $pending = $this->getPending();
        if ($pending) {
            $f_tpl->setCurrentBlock("pending");
            $f_tpl->setVariable("TXT_PENDING", $lng->txt("file_upload_pending") .
                ": " . htmlentities($pending));
            $f_tpl->parseCurrentBlock();
        }

        if ($this->getDisabled() || $quota_exceeded) {
            $f_tpl->setVariable(
                "DISABLED",
                " disabled=\"disabled\""
            );
        }

        $f_tpl->setVariable('MAX_SIZE_WARNING', $this->lng->txt('form_msg_file_size_exceeds'));
        $f_tpl->setVariable('MAX_SIZE', $this->upload_limit->getPhpUploadLimitInBytes());
        $f_tpl->setVariable("POST_VAR", $this->getPostVar());
        $f_tpl->setVariable("ID", $this->getFieldId());
        $f_tpl->setVariable("SIZE", $this->getSize());
        $f_tpl->setVariable("LABEL_SELECTED_FILES_INPUT", $this->lng->txt('selected_files'));


        /* experimental: bootstrap'ed file upload */
        $f_tpl->setVariable("TXT_BROWSE", $lng->txt("select_file"));


        return $f_tpl->get();
    }
    /**
     * Render html
     * @throws ilTemplateException
     */
}
