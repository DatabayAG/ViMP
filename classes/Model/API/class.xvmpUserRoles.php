<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpUserRoles
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpUserRoles extends xvmpObject
{
    public const ROLE_ANONYMOUS = 0;
    public const ROLE_ADMINISTRATOR = 1;

    /**
     * @inheritdoc
     * @throws xvmpException
     */
    public static function find($id): xvmpObject
    {
        return self::getAllAsArray()[$id];
    }

    /**
     * @inheritdoc
     * @throws xvmpException
     */
    public static function getAllAsArray(): array
    {
        $existing = xvmpCacheFactory::getInstance()->get(self::class);
        if ($existing) {
            xvmpCurlLog::getInstance()->write('CACHE: used cached: ' . self::class, xvmpCurlLog::DEBUG_LEVEL_2);
            return $existing;
        }

        xvmpCurlLog::getInstance()->write('CACHE: cache not used: ' . self::class, xvmpCurlLog::DEBUG_LEVEL_2);

        $response = xvmpRequest::getUserRoles()->getResponseArray();
        $user_roles = $response['roles']['role'];

        // response has the wrong keys -> format array
        $cache_array = [];
        foreach ($user_roles as $item) {
            $cache_array[$item['id']] = $item;
        }

        self::cache(self::class, $cache_array);
        return $cache_array;
    }

    /**
     * @return bool
     */
    public function isInvisibleDefault(): bool
    {
        return $this->getField('default') && !$this->getField('visible');
    }

    /**
     * @var int
     */
    protected int $id;
    /**
     * @var String
     */
    protected string $status;
    /**
     * @var String
     */
    protected string $name;
    /**
     * @var ?String
     */
    protected ?string $description;
    /**
     * @var bool
     */
    protected bool $visible;
    /**
     * @var bool
     */
    protected bool $default;
    /**
     * @var String
     */
    protected string $created_at;
    /**
     * @var String
     */
    protected string $updated_at;


    /**
     * @return String
     */
    public function getStatus(): string
    {
        return $this->status;
    }


    /**
     * @param String $status
     */
    public function setStatus(string $status) : void
    {
        $this->status = $status;
    }


    /**
     * @return String
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @param String $name
     */
    public function setName(string $name) : void
    {
        $this->name = $name;
    }


    /**
     * @return String
     */
    public function getDescription(): string
    {
        return $this->description;
    }


    /**
     * @param String $description
     */
    public function setDescription(string $description) : void
    {
        $this->description = $description;
    }


    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }


    /**
     * @param bool $visible
     */
    public function setVisible(bool $visible) : void
    {
        $this->visible = $visible;
    }


    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }


    /**
     * @param bool $default
     */
    public function setDefault(bool $default) : void
    {
        $this->default = $default;
    }


    /**
     * @return String
     */
    public function getCreatedAt(): string
    {
        return $this->created_at;
    }


    /**
     * @param String $created_at
     */
    public function setCreatedAt(string $created_at) : void
    {
        $this->created_at = $created_at;
    }


    /**
     * @return String
     */
    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }


    /**
     * @param String $updated_at
     */
    public function setUpdatedAt(string $updated_at) : void
    {
        $this->updated_at = $updated_at;
    }

}
