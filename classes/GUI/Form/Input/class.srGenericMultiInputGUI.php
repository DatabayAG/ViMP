<?php

declare(strict_types=1);

/**
 * Class srGenericMultiInputGUI
 * @author Michael Herren <mh@studer-raimann.ch>
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class srGenericMultiInputGUI extends ilFormPropertyGUI
{
    public const HOOK_IS_LINE_REMOVABLE = "hook_is_line_removable";
    public const HOOK_IS_INPUT_DISABLED = "hook_is_disabled";
    public const HOOK_BEFORE_INPUT_RENDER = "hook_before_render";
    /**
     * @var array
     */
    protected array $cust_attr = array();
    /**
     * @var
     */
    protected $value;
    /**
     * @var array
     */
    protected array $inputs = array();
    /**
     * @var array
     */
    protected array $input_options = array();
    /**
     * @var array
     */
    protected array $hooks = array();
    /**
     * @var array
     */
    protected array $line_values = array();
    /**
     * @var string
     */
    protected string $template_dir = '';
    /**
     * @var array
     */
    protected array $post_var_cache = array();
    /**
     * @var bool
     */
    protected bool $show_label = false;
    /**
     * @var int
     */
    protected int $limit = 0;
    /**
     * @var bool
     */
    protected bool $allow_empty_fields = false;

    /**
     * Constructor
     * @param string $a_title   Title
     * @param string $a_postvar Post Variable
     */
    public function __construct(string $a_title = "", string $a_postvar = "")
    {
        parent::__construct($a_title, $a_postvar);
        $this->setType("line_select");
        $this->setMulti(true);
    }

    public function setMulti(
        bool $a_multi,
        bool $a_sortable = false,
        bool $a_addremove = true
    ) : void {
        $this->multi = $a_multi;
        $this->multi_sortable = $a_sortable;
    }

    /**
     * @return int
     */
    public function getLimit() : int
    {
        return $this->limit;
    }

    /**
     * set a limit of possible lines, 0 = no limit
     * @param mixed $limit
     */
    public function setLimit($limit) : void
    {
        $this->limit = $limit;
    }

    /**
     * @return boolean
     */
    public function isAllowEmptyFields() : bool
    {
        return $this->allow_empty_fields;
    }

    /**
     * @param boolean $allow_empty_fields
     */
    public function setAllowEmptyFields(bool $allow_empty_fields) : void
    {
        $this->allow_empty_fields = $allow_empty_fields;
    }

    /**
     * @param       $key
     * @param array $options
     */
    public function addHook($key, array $options) : void
    {
        $this->hooks[$key] = $options;
    }

    /**
     * @param $key
     * @return bool
     */
    public function removeHook($key) : bool
    {
        if (isset($this->hooks[$key])) {
            unset($this->hooks[$key]);

            return true;
        }

        return false;
    }

    public function addInput(ilFormPropertyGUI $input, array $options = []) : void
    {
        $input->setRequired(!$this->allow_empty_fields);
        $this->inputs[$input->getPostVar()] = $input;
        $this->input_options[$input->getPostVar()] = $options;
    }

    /**
     * Get Options.
     * @return    array    Options. Array ("value" => "option_text")
     */
    public function getInputs() : array
    {
        return $this->inputs;
    }

    /**
     * Set value by array
     * @throws ilDateTimeException
     */
    public function setValueByArray(array $a_values) : void
    {
        $data = $a_values[$this->getPostVar()] ?? [];
        if ($this->getMulti()) {
            $this->line_values = $data;
        } else {
            $this->setValue($data);
        }
    }

    /**
     * Check input, strip slashes etc. set alert, if input is not ok.
     * @return    boolean        Input ok, true/false
     * @throws ilDateTimeException
     */
    public function checkInput() : bool
    {
        global $lng;

        $valid = true;

        $value = $this->arrayArray($this->getPostVar());
        // escape data
        $out_array = [];
        foreach ($value as $item_num => $item) {
            foreach ($this->inputs as $input_key => $input) {
                if (isset($item[$input_key])) {
                    if ($input instanceof ilDateTimeInputGUI) {
                        $out = (is_string($item[$input_key])) ? ilUtil::stripSlashes($item[$input_key]) : $item[$input_key];
                        if (ilCalendarUtil::parseIncomingDate($out)) {
                            $out_array[$item_num][$input_key] = $out;
                        } else {
                            $valid = false;
                            $this->setAlert($this->lng->txt("form_msg_wrong_date"));
                            $out_array[$item_num][$input_key] = null;
                        }
                    }
                }
            }
        }

        $this->setValue($out_array);

        if ($this->getRequired() && !trim(implode("", $this->getValue()))) {
            $this->setAlert($lng->txt("msg_input_is_required"));
            $valid = false;
        }

        return $valid;
    }

    /**
     * Get Value.
     */
    public function getValue() : array
    {
        $out = [];
        foreach ($this->inputs as $key => $item) {
            $out[$key] = $item->getValue();
        }

        return $out;
    }

    /**
     * Set Value.
     * @throws ilDateTimeException
     */
    public function setValue(array $value) : void
    {
        $this->value = $value;

        foreach ($this->inputs as $key => $item) {
            if ($item instanceof ilCheckboxInputGUI) {
                $item->setChecked((bool) ($value[$key] ?? false));
            } else {
                if ($item instanceof ilDateTimeInputGUI) {
                    if (ilCalendarUtil::parseIncomingDate($value[$key])) {
                        $item->setDate(new ilDate($value[$key], IL_CAL_DATE));
                    } else {
                        $item->setDate();
                    }
                } else {
                    if (isset($value[$key])) {
                        $item->setValue($value[$key]);
                    }
                }
            }
        }
    }

    /**
     * Insert property html     *
     * @throws ilTemplateException
     * @throws ilException
     */
    public function insert(ilTemplate $a_tpl) : void
    {
        global $DIC;
        $tpl = $DIC['tpl'];

        $output = "";
        //		$tpl->addCss($this->getTemplateDir() . '/templates/css/multi_line_input.css');
        $output .= $this->render(0, true);

        if ($this->getMulti() && is_array($this->line_values) && count($this->line_values) > 0) {
            foreach ($this->line_values as $i => $data) {
                $object = $this;
                $object->setValue($data);
                $output .= $object->render($i);
            }
        } else {
            $output .= $this->render(1, true);
        }

        if ($this->getMulti()) {
            $output = '<div id="' . $this->getFieldId() . '" class="multi_line_input">' . $output . '</div>';
            $tpl->addJavascript(ilViMPPlugin::getInstance()->getAssetURL('/js/generic_multi_line_input.js'));
            $id = $this->getFieldId();
            $element_config = json_encode($this->input_options);
            $options = json_encode(['limit' => $this->limit,
                                    'sortable' => $this->multi_sortable,
                                    'locale' => $DIC->language()->getLangKey()
            ]);
            $tpl->addOnLoadCode("il.srGenericMultiInput.genericMultiLineInit('$id',$element_config,$options);");
        }

        $a_tpl->setCurrentBlock("prop_generic");
        $a_tpl->setVariable("PROP_GENERIC", $output);
        $a_tpl->parseCurrentBlock();
    }

    /**
     * Render item
     * @param int  $iterator_id
     * @param bool $clean_render
     * @return string
     * @throws ilException
     * @throws ilTemplateException
     */
    public function render(int $iterator_id = 0, bool $clean_render = false) : string
    {
        $tpl = new ilTemplate("tpl.prop_generic_multi_line.html", true, true,
            'Customizing/global/plugins/Services/Repository/RepositoryObject/ViMP');

        $class = 'multi_input_line';

        $this->addCustomAttribute('class', $class, true);
        foreach ($this->getCustomAttributes() as $key => $value) {
            $tpl->setCurrentBlock('cust_attr');
            $tpl->setVariable('CUSTOM_ATTR_KEY', $key);
            $tpl->setVariable('CUSTOM_ATTR_VALUE', $value);
            $tpl->parseCurrentBlock();
        }

        $inputs = $this->inputs;

        foreach ($inputs as $key => $input) {
            $input = clone $input;
            if (!method_exists($input, 'render')) {
                throw new ilException("Method " . get_class($input)
                    . "::render() does not exists! You cannot use this input-type in ilMultiLineInputGUI");
            }

            $is_disabled_hook = $this->getHook(self::HOOK_IS_INPUT_DISABLED);
            if ($is_disabled_hook !== false && !$clean_render) {
                $input->setDisabled($is_disabled_hook($this->getValue()));
            }

            if ($this->getDisabled()) {
                $input->setDisabled(true);
            }

            if ($iterator_id == 0 && !isset($this->post_var_cache[$key])) {
                $this->post_var_cache[$key] = $input->getPostVar();
            } else {
                // Reset post var
                $input->setPostVar($this->post_var_cache[$key]);
            }

            $post_var = $this->createInputPostVar($iterator_id, $input);
            $input->setPostVar($post_var);

            $before_render_hook = $this->getHook(self::HOOK_BEFORE_INPUT_RENDER);
            if ($before_render_hook !== false && !$clean_render) {
                $input = $before_render_hook($this->getValue(), $key, $input);
            }

            //var_dump($input);

            if ($this->isShowLabel()) {
                $tpl->setCurrentBlock('input_label');
                $tpl->setVariable('LABEL', $input->getTitle());
            } else {
                $tpl->setCurrentBlock('input');
            }
            $tpl->setVariable('CONTENT', $input->render());
            $tpl->parseCurrentBlock();
        }

        if ($this->getMulti() && !$this->getDisabled()) {
            $tpl->setVariable('IMAGE_MINUS', ilGlyphGUI::get(ilGlyphGUI::REMOVE));

            $show_remove = true;
            $is_removeable_hook = $this->getHook(self::HOOK_IS_LINE_REMOVABLE);
            if ($is_removeable_hook !== false && !$clean_render) {
                $show_remove = $is_removeable_hook($this->getValue());
            }

            $image_minus = ($show_remove) ? ilGlyphGUI::get(ilGlyphGUI::REMOVE) : '<span class="glyphicon glyphicon-minus hide"></span>';
            $tpl->setCurrentBlock('multi_icons');
            $tpl->setVariable('IMAGE_PLUS', ilGlyphGUI::get(ilGlyphGUI::ADD));
            $tpl->setVariable('IMAGE_MINUS', $image_minus);
            if ($this->multi_sortable) {
                $tpl->setVariable('IMAGE_UP', ilGlyphGUI::get(ilGlyphGUI::UP));
                $tpl->setVariable('IMAGE_DOWN', ilGlyphGUI::get(ilGlyphGUI::DOWN));
            }
            $tpl->parseCurrentBlock();
        }

        return $tpl->get();
    }

    /**
     * @param            $key
     * @param            $value
     * @param bool       $override
     */
    public function addCustomAttribute($key, $value, bool $override = false) : void
    {
        if (isset($this->cust_attr[$key]) && !$override) {
            $this->cust_attr[$key] .= ' ' . $value;
        } else {
            $this->cust_attr[$key] = $value;
        }
    }

    /**
     * @return array
     */
    public function getCustomAttributes() : array
    {
        return $this->cust_attr;
    }

    public function getHook($key) : bool|string
    {
        if (isset($this->hooks[$key])) {
            return $this->hooks[$key];
        }

        return false;
    }

    /**
     * @param                   $iterator_id
     * @param ilFormPropertyGUI $input
     * @return string
     */
    protected function createInputPostVar($iterator_id, ilFormPropertyGUI $input) : string
    {
        if ($this->getMulti()) {
            return $this->getPostVar() . '[' . $iterator_id . '][' . $input->getPostVar() . ']';
        } else {
            return $this->getPostVar() . '[' . $input->getPostVar() . ']';
        }
    }

    public function isShowLabel() : bool
    {
        return $this->show_label;
    }

    public function setShowLabel(bool $show_label) : void
    {
        $this->show_label = $show_label;
    }

    /**
     * Get HTML for table filter
     * @throws ilException
     */
    public function getTableFilterHTML() : string
    {
        return $this->render();
    }

    /**
     * Get HTML for toolbar
     * @throws ilException
     */
    public function getToolbarHTML() : string
    {
        return $this->render();
    }

    public function getSubItems() : array
    {
        return [];
    }
}
