<?php

declare(strict_types=1);

/**
 * Class ilMultiSelectSearchInputGUI
 * @author: Oskar Truffer <ot@studer-raimann.ch>
 * @author: Martin Studer <ms@studer-raimann.ch>
 */
class ilMultiSelectSearchInputGUI extends ilMultiSelectInputGUI
{
    protected int $width;
    protected int $height;
    protected string $css_class = '';
    protected int $minimum_input_length = 0;
    protected string $ajax_link = '';
    protected ilTemplate $input_template;

    public function __construct($title, $post_var)
    {
        global $DIC;
        $tpl = $DIC['tpl'];
        $ilUser = $DIC['ilUser'];
        $lng = $DIC['lng'];

        if (!str_ends_with($post_var, "[]")) {
            $post_var = $post_var . "[]";
        }
        parent::__construct($title, $post_var);

        $this->lng = $lng;
        $tpl->addJavaScript("./public/Customizing/global/plugins/Services/Repository/RepositoryObject/ViMP/templates/default/form/select2/select2.min.js");
        //$tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/ViMP/templates/default/form/select2/select2_locale_".$ilUser->getCurrentLanguage().".js");
        $tpl->addCss("./public/Customizing/global/plugins/Services/Repository/RepositoryObject/ViMP/templates/default/form/select2/select2.css");
        $this->setInputTemplate(new ilTemplate("tpl.multiple_select.html", true, true,
            "./public/Customizing/global/plugins/Services/Repository/RepositoryObject/ViMP"));
        $this->setWidth(308);
        $this->setHeight(100);
    }

    /**
     * Check input, strip slashes etc. set alert, if input is not ok.
     * @return    boolean        Input ok, true/false
     */
    public function checkInput() : bool
    {
        global $DIC;
        $lng = $DIC['lng'];

        if ($this->getRequired() && count($this->getValue()) == 0) {
            $this->setAlert($lng->txt("msg_input_is_required"));

            return false;
        }
        return true;
    }

    public function getSubItems() : array
    {
        return array();
    }

    /**
     * @throws ilTemplateException
     */
    public function render() : string
    {
        $tpl = $this->getInputTemplate();
        $values = $this->getValue();
        $options = $this->getOptions();

        $postvar = $this->getPostVar();
        /*if(substr($postvar, -3) == "[]]")
        {
            $postvar = substr($postvar, 0, -3)."]";
        }*/

        $tpl->setVariable("POST_VAR", $postvar);

        //Multiselect Bugfix
        //$id = substr($this->getPostVar(), 0, -2);
        $tpl->setVariable("ID", $this->getFieldId());
        //$tpl->setVariable("ID", $this->getPostVar());

        $tpl->setVariable("WIDTH", $this->getWidth());
        $tpl->setVariable("HEIGHT", $this->getHeight());
        $tpl->setVariable("PLACEHOLDER");
        $tpl->setVariable("MINIMUM_INPUT_LENGTH", $this->getMinimumInputLength());
        $tpl->setVariable("Class", $this->getCssClass());

        if (isset($this->ajax_link)) {
            $tpl->setVariable("AJAX_LINK", $this->getAjaxLink());
        }

        if ($this->getDisabled()) {
            $tpl->setVariable("ALL_DISABLED", "disabled=\"disabled\"");
        }

        if ($options) {
            foreach ($options as $option_value => $option_text) {
                $tpl->setCurrentBlock("item");
                if ($this->getDisabled()) {
                    $tpl->setVariable(
                        "DISABLED",
                        " disabled=\"disabled\""
                    );
                }
                if (in_array($option_value, $values)) {
                    $tpl->setVariable(
                        "SELECTED",
                        "selected"
                    );
                }

                $tpl->setVariable("VAL", ilLegacyFormElementsUtil::prepareFormOutput($option_value));
                $tpl->setVariable("TEXT", $option_text);
                $tpl->parseCurrentBlock();
            }
        }
        return $tpl->get();
    }

    /**
     * @return ilTemplate
     */
    public function getInputTemplate() : ilTemplate
    {
        return $this->input_template;
    }

    /**
     * @param ilTemplate $input_template
     */
    public function setInputTemplate(ilTemplate $input_template) : void
    {
        $this->input_template = $input_template;
    }

    /**
     * @return int
     */
    public function getWidth() : int
    {
        return $this->width;
    }

    /**
     * @param int $a_width
     * @deprecated setting inline style items from the controller is bad practice. please use the setClass together with an appropriate css class.
     */
    public function setWidth(int $a_width) : void
    {
        $this->width = $a_width;
    }

    public function getHeight() : int
    {
        return $this->height;
    }

    /**
     * @param int $a_height
     * @deprecated setting inline style items from the controller is bad practice. please use the setClass together with an appropriate css class.
     */
    public function setHeight(int $a_height) : void
    {
        $this->height = $a_height;
    }

    /**
     * @return int
     */
    public function getMinimumInputLength() : int
    {
        return $this->minimum_input_length;
    }

    /**
     * @param int $minimum_input_length
     */
    public function setMinimumInputLength(int $minimum_input_length) : void
    {
        $this->minimum_input_length = $minimum_input_length;
    }

    public function getCssClass() : ?string
    {
        return $this->css_class;
    }

    /**
     * @param string $css_class
     */
    public function setCssClass(string $css_class) : void
    {
        $this->css_class = $css_class;
    }

    /**
     * @return string
     */
    public function getAjaxLink() : string
    {
        return $this->ajax_link;
    }

    /**
     * @param string $ajax_link setting the ajax link will lead to ignoration of the "setOptions" function as the link given will be used to get the
     */
    public function setAjaxLink(string $ajax_link) : void
    {
        $this->ajax_link = $ajax_link;
    }

    /**
     * @param srDefaultAccessChecker $access_checker
     */
    public function setAccessChecker($access_checker) : void
    {
        $this->access_checker = $access_checker;
    }

    /**
     * @return srDefaultAccessChecker
     */
    public function getAccessChecker()
    {
        return $this->access_checker;
    }

    public function setValueByArray(array $a_values) : void
    {
        $val = isset($a_values[$this->searchPostVar()]) ? $a_values[$this->searchPostVar()] : array();
        if (!is_array($val)) {
            $val = explode(",", $val);
        }

        $this->setValue($val);
    }

    /**
     * This implementation might sound silly. But the multiple select input used parses the post vars differently if you use ajax. thus we have to do this stupid "trick". Shame on select2 project ;)
     * @return string the real postvar.
     */
    protected function searchPostVar() : string
    {
        $postVar = $this->getPostVar();
        if (str_ends_with($postVar, "[]")) {
            $postVar = substr($postVar, 0, -2);
            return $postVar;
        }

        return $postVar;
    }
}
