<?php

declare(strict_types=1);

class xvmpFileInputGUI extends ilFileInputGUI
{
    protected string $download_url;

    public function setDownloadUrl(string $download_url)
    {
        $this->download_url = $download_url;
    }

    /**
     * Render html
     * @throws ilTemplateException
     */
    public function render($a_mode = ""): string
    {
        $lng = $this->lng;

        $f_tpl = new ilTemplate("tpl.prop_file.html", true, true, "Services/Form");


        // show filename selection if enabled
        if ($this->isFileNameSelectionEnabled()) {
            $f_tpl->setCurrentBlock('filename');
            $f_tpl->setVariable('POST_FILENAME', $this->getFileNamePostVar());
            $f_tpl->setVariable('VAL_FILENAME', $this->getFilename());
            $f_tpl->setVariable('FILENAME_ID', $this->getFieldId());
            $f_tpl->setVAriable('TXT_FILENAME_HINT', $lng->txt('if_no_title_then_filename'));
            $f_tpl->parseCurrentBlock();
        } else {
            if (trim((string) $this->getValue())) {
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


        $pending = $this->getPending();
        if ($pending) {
            $f_tpl->setCurrentBlock("pending");
            $f_tpl->setVariable("TXT_PENDING", $lng->txt("file_upload_pending") .
                ": " . $pending);
            $f_tpl->parseCurrentBlock();
        }

        if ($this->getDisabled()) {
            $f_tpl->setVariable(
                "DISABLED",
                " disabled=\"disabled\""
            );
        }

        $f_tpl->setVariable("POST_VAR", $this->getPostVar());
        $f_tpl->setVariable("ID", $this->getFieldId());
        $f_tpl->setVariable("SIZE", $this->getSize());


        /* experimental: bootstrap'ed file upload */
        $f_tpl->setVariable("TXT_BROWSE", $lng->txt("select_file"));


        return $f_tpl->get();
    }
}
