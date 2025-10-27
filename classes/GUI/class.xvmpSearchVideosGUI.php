<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpSearchVideosGUI
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @ilCtrl_isCalledBy xvmpSearchVideosGUI: ilObjViMPGUI
 */
class xvmpSearchVideosGUI extends xvmpVideosGUI
{
    public const SUBTAB_ACTIVE = xvmpVideosGUI::SUBTAB_SEARCH;

    public const TABLE_CLASS = 'xvmpSearchVideosTableGUI';

    public function executeCommand() : void
    {
        if (!ilObjViMPAccess::hasWriteAccess()) {
            xvmpCurlLog::getInstance()->write('Access denied: User has no write access. (xvmpSearchVideosGUI)');
            $this->accessDenied();
        }

        parent::executeCommand();
    }

}
