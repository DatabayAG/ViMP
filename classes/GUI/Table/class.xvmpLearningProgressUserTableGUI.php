<?php

class xvmpLearningProgressUserTableGUI extends ilTrObjectUsersPropsTableGUI
{
    private ?ActiveRecord $xvmp_settings;

    public function __construct($a_parent_obj, $a_parent_cmd, $a_obj_id, $a_ref_id, $a_print_view = false)
    {
        $this->xvmp_settings = xvmpSettings::find($a_obj_id);
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_obj_id, $a_ref_id, true);
        $this->setPrintMode($a_print_view);
        $this->setRowTemplate('tpl.object_users_props_row.html', $this->parent_obj->plugin->getDirectory());
        if (!$a_print_view) {
            $this->addColumn($this->lng->txt('actions'), '');
        }
    }

    protected function parseTitle($a_obj_id, $action, $a_user_id = false)
    {
        global $DIC;

        $user = '';
        if ($a_user_id) {
            if ($a_user_id != $DIC->user()->getId()) {
                $a_user = ilObjectFactory::getInstanceByObjId($a_user_id);
            } else {
                $a_user = $DIC->user();
            }
            $user .= ', ' . $a_user->getFullName();
        }

        $this->setTitle($this->lng->txt($action) . ': ' . $DIC['ilObjDataCache']->lookupTitle($a_obj_id) . $user);
        $olp = ilObjectLP::getInstance($a_obj_id);
        $this->setDescription($this->lng->txt('trac_mode') . ': ' . $olp->getModeText($this->xvmp_settings->getLpMode()));
    }

    protected function isPercentageAvailable($a_obj_id) : bool
    {
        if ($this->isLearningProgressDeactivated()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function searchFilterListener($a_ref_id, $a_data) : bool
    {
        $status = parent::searchFilterListener($a_ref_id, $a_data);

        if (
            $status &&
            $this->isLearningProgressDeactivated()
        ) {
            $status = false;
        }

        return $status;
    }

    /**
     * @inheritDoc
     */
    protected function getSelectableUserColumns($a_in_course = false, $a_in_group = false) : array
    {
        $columns = parent::getSelectableUserColumns($a_in_course, $a_in_group);

        if ($this->isLearningProgressDeactivated()) {
            unset($columns['status']);
            unset($columns['status_changed']);
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public function getSelectableColumns() : array
    {
        $columns = parent::getSelectableColumns();

        if ($this->isLearningProgressDeactivated()) {
            unset($columns['status']);
            unset($columns['status_changed']);
        }

        return $columns;
    }

    /**
     * @return bool
     */
    protected function isLearningProgressDeactivated() : bool
    {
        return ! $this->xvmp_settings->getLpActive();
    }

    /**
     * @inheritDoc
     */
    public function initFilter($a_split_learning_resources = false, $a_include_no_status_filter = true) : void
    {
        $this->filter = [];
    }

    /**
     * @inheritDoc
     */
    protected function fillRow($data) : void
    {
        global $DIC;
        foreach ($this->getSelectedColumns() as $column) {
            if ($column === 'status' && (int) $data[$column] !== ilLPStatus::LP_STATUS_COMPLETED_NUM) {
                $timing = $this->showTimingsWarning($this->ref_id, $data['usr_id']);
                if ($timing) {
                    if ($timing !== true) {
                        $timing = ': ' . ilDatePresentation::formatDate(new ilDate($timing, IL_CAL_UNIX));
                    } else {
                        $timing = '';
                    }
                    $this->tpl->setCurrentBlock('warning_img');
                    $this->tpl->setVariable('WARNING_IMG', ilUtil::getImagePath('time_warn.svg'));
                    $this->tpl->setVariable('WARNING_ALT', $this->lng->txt('trac_time_passed') . $timing);
                    $this->tpl->parseCurrentBlock();
                }
            }

            // #7694
            if ($column === 'login' && !$data['active']) {
                $this->tpl->setCurrentBlock('inactive_bl');
                $this->tpl->setVariable('TXT_INACTIVE', $this->lng->txt('inactive'));
                $this->tpl->parseCurrentBlock();
            }

            $this->tpl->setCurrentBlock('user_field');
            $val = $this->parseValue($column, $data[$column], 'user');
            $this->tpl->setVariable('VAL_UF', $val);
            $this->tpl->parseCurrentBlock();
        }

        $DIC->ctrl()->setParameter($this->getParentObject(), 'user_id', $data['usr_id']);
        if (!$this->getPrintMode()) {
            $this->tpl->setCurrentBlock('item_command');
            $this->tpl->setVariable(
                'HREF_COMMAND',
                $DIC->ctrl()->getLinkTarget($this->getParentObject(), 'editUser')
            );
            $this->tpl->setVariable('TXT_COMMAND', $this->lng->txt('edit'));
            $this->tpl->parseCurrentBlock();
        }
        $DIC->ctrl()->setParameter($this->getParentObject(), 'user_id', '');
    }
}


