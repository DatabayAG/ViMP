<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpConf
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpConf extends ActiveRecord
{
    public const DB_TABLE_NAME = 'xvmp_config';

    public const CONFIG_VERSION = 1;

    public const F_CONFIG_VERSION = 'config_version';

    public const F_OBJECT_TITLE = 'object_title';
    public const F_API_KEY = 'api_key';
    public const F_API_USER = 'api_user';
    public const F_API_PASSWORD = 'api_password';
    public const F_API_URL = 'api_url';
    public const F_DISABLE_VERIFY_PEER = 'disable_verify_peer';
    public const F_USER_MAPPING_EXTERNAL = 'user_mapping_ext';
    public const F_USER_MAPPING_LOCAL = 'user_mapping_local';

    public const F_MAPPING_PRIORITY = 'mapping_priority';
    public const PRIORITIZE_EMAIL = 0;
    public const PRIORITIZE_MAPPING = 1;

    public const F_ALLOW_PUBLIC = 'allow_public';
    public const F_ALLOW_PUBLIC_UPLOAD = 'allow_public_upload';
    public const F_DEFAULT_PUBLICATION = 'default_publication';
    public const F_MEDIA_PERMISSIONS = 'media_permissions';
    public const F_MEDIA_PERMISSIONS_SELECTION = 'media_permissions_selection';
    public const F_MEDIA_PERMISSIONS_PRESELECTED = 'media_permissions_preselected';
    public const F_NOTIFICATION_SUBJECT_SUCCESSFULL = 'notification_subject';
    public const F_NOTIFICATION_BODY_SUCCESSFULL = 'notification_body';
    public const F_NOTIFICATION_SUBJECT_FAILED = 'notification_subject_failed';
    public const F_NOTIFICATION_BODY_FAILED = 'notification_body_failed';
    public const F_CACHE_TTL_VIDEOS = 'cache_ttl_videos';
    public const F_CACHE_TTL_USERS = 'cache_ttl_users';
    public const F_CACHE_TTL_CATEGORIES = 'cache_ttl_categories';
    public const F_CACHE_TTL_TOKEN = 'cache_ttl_token';
    public const F_CACHE_TTL_CONFIG = 'cache_ttl_config';
    public const F_FILTER_FIELDS = 'filter_fields';
    public const F_FILTER_FIELD_ID = 'filter_id';
    public const F_FILTER_FIELD_TITLE = 'filter_title';
    public const F_FORM_FIELDS = 'form_fields';
    public const F_FORM_FIELD_ID = 'field_id';
    public const F_FORM_FIELD_TITLE = 'field_title';
    public const F_FORM_FIELD_REQUIRED = 'required';
    public const F_FORM_FIELD_FILL_USER_DATA = 'fill_user_data';
    public const F_FORM_FIELD_SHOW_IN_PLAYER = 'show_in_player';
    public const F_FORM_FIELD_TYPE = 'field_type';
    public const F_FORM_FIELD_TYPE_TEXT = 0;

    public const F_FORM_FIELD_TYPE_CHECKBOX = 1;
    public const F_UPLOAD_LIMIT = 'upload_limit';
    public const F_TOKEN = 'token';

    public const F_EMBED_PLAYER = 'embed_player';
    public const F_DOWNLOAD_BUTTON = 'download_button';
    public const F_STREAMING_BUTTON = 'streaming_button';
    public const F_VIEWS = 'views';
    public const MEDIA_PERMISSION_OFF = 0;
    public const MEDIA_PERMISSION_ON = 1;
    public const MEDIA_PERMISSION_SELECTION = 2;
    /**
     * @var array
     */
    protected static array $cache = array();
    /**
     * @var array
     */
    protected static array $cache_loaded = array();

    /**
     * @var ?string
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           250
     */
    protected ?string $name;
    /**
     * @var ?string
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           4000
     */
    protected ?string $value = null;

    public function __construct($primary_key = 0)
    {
        parent::__construct($primary_key);
    }

    public static function returnDbTableName() : string
    {
        return self::DB_TABLE_NAME;
    }

    public static function getConfig($name) : mixed
    {
        if (!isset(self::$cache_loaded[$name])) {
            try {
                $obj = new self($name);
            } catch (Exception $e) {
                $obj = new self();
                $obj->setName($name);
            }

            if ($obj->getValue()) {
                self::$cache[$name] = json_decode($obj->getValue(), true);
                self::$cache_loaded[$name] = true;
            }
        }

        return self::$cache[$name] ?? null;
    }

    /**
     * @return ?string
     */
    public function getValue() : ?string
    {
        return $this->value;
    }

    public function setValue(string $value) : void
    {
        $this->value = $value;
    }

    public static function set($name, $value) : void
    {
        try {
            $obj = new self($name);
        } catch (Exception $e) {
            $obj = new self();
            $obj->setName($name);
        }
        $obj->setValue(json_encode($value));
        $obj->store();
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }
}
