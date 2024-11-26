<?php /** @noinspection PhpStrictTypeCheckingInspection */

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpException
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpException extends Exception
{
    public const API_CALL_UNSUPPORTED = 10;
    public const NO_USER_MAPPING = 20;
    public const USER_CREATION_FAILED = 30;
    public const INTERNAL_ERROR = 40;
    public const API_CALL_STATUS_500 = 500;
    public const API_CALL_STATUS_403 = 403;
    public const API_CALL_STATUS_404 = 404;
    public const API_CALL_BAD_CREDENTIALS = 401;

    /**
     * @var array
     */
    protected static array $messages = array(
        self::API_CALL_UNSUPPORTED => 'This Api-Call is not supported',
        self::API_CALL_STATUS_500 => 'An error occurred while communicating with the ViMP-Server',
        self::API_CALL_STATUS_403 => 'Access denied',
        self::API_CALL_STATUS_404 => 'Not Found',
        self::NO_USER_MAPPING => 'Your ILIAS account cannot communicate with the ViMP-Server. Please contact your system administrator.',
        self::USER_CREATION_FAILED => 'There was an error while creating a ViMP user for your ILIAS account. Please contact your system administrator.',
        self::API_CALL_BAD_CREDENTIALS => 'An error occurred while communicating with the ViMP-Server (bad credentials).',
        self::INTERNAL_ERROR => 'An Internal Error occured.',

    );


    /**
     * @param string $code
     * @param string $additional_message
     */
    public function __construct(string $code, $additional_message = '')
    {
        $message = ilViMPPlugin::getInstance()->txt("exception_message");
        if ($additional_message) {
            $message .= ' (Code ' . $code . '): "' . $additional_message . '"';
        }
        parent::__construct($message, $code);
    }

}
