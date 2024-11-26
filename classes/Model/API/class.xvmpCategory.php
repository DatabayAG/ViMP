<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpCategory
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpCategory extends xvmpObject
{
    public const DEFAULT_CACHE_TTL = 86400; // 1 day

    public static function getObjectAsArray($id): array
    {
        $key = self::class;
        $existing = xvmpCacheFactory::getInstance()->get($key);
        if ($existing && isset($existing[$id])) {
            xvmpCurlLog::getInstance()->write('CACHE: used cached: ' . $key . '-' . $id, xvmpCurlLog::DEBUG_LEVEL_2);
            return $existing[$id];
        }

        $response = xvmpRequest::getCategory($id)->getResponseArray()['category'];

        if ($existing) {
            $cache = $existing;
            $cache[] = $response;
        } else {
            $cache = array($response);
        }

        self::cache($key, $cache);

        return $response;
    }

    public static function getAllAsArray(): array
    {
        $key = self::class;
        $existing = xvmpCacheFactory::getInstance()->get($key);
        if ($existing && ($existing['loaded'] == 1)) {
            unset($existing['loaded']);
            xvmpCurlLog::getInstance()->write('CACHE: used cached: ' . $key, xvmpCurlLog::DEBUG_LEVEL_2);
            return $existing;
        }

        xvmpCurlLog::getInstance()->write('CACHE: cached not used: ' . $key, xvmpCurlLog::DEBUG_LEVEL_2);

        $response = xvmpRequest::getCategories()->getResponseArray()['categories']['category'];
        $response['loaded'] = 1;

        // response has the wrong keys -> format array
        $cache_array = [];
        foreach ($response as $k => $item) {
            $cache_array[($k == 'loaded' ? $k : $item['cid'])] = $item;
        }

        self::cache($key, $cache_array);

        unset($cache_array['loaded']);
        return $cache_array;
    }

    public static function cache($identifier, $object, $ttl = null) : void
    {
        parent::cache($identifier, $object, (int) ($ttl ?: xvmpConf::getConfig(xvmpConf::F_CACHE_TTL_CATEGORIES)));
    }

    protected int $cid;
    protected int $pid;
    protected ?int $parent;
    protected string $culture;
    protected string $name;
    protected ?string $description;
    protected string $categorytype;
    protected string $status;
    protected string $picture;
    protected int $weight;
    protected string $created_at;

    protected string $updated_at;

    public function getId(): int
    {
        return $this->cid;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function setCid(int $cid) : void
    {
        $this->cid = $cid;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid) : void
    {
        $this->pid = $pid;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function setParent(int $parent) : void
    {
        $this->parent = $parent;
    }

    public function getCulture(): string
    {
        return $this->culture;
    }

    public function setCulture(string $culture) : void
    {
        $this->culture = $culture;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameWithPath(): string
    {
        $path = array($this->getName());
        $category = $this;
        $already_handled = array($this->getId());
        while ($parent = $category->getParent()) {
            if (in_array($parent, $already_handled)) {
                break;
            }
            $category = xvmpCategory::find($parent);
            array_unshift($path, $category->getName());
            $already_handled[] = $parent;
        }
        return implode(' » ', $path);
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description) : void
    {
        $this->description = $description;
    }

    public function getCategorytype(): string
    {
        return $this->categorytype;
    }

    public function setCategorytype(string $categorytype) : void
    {
        $this->categorytype = $categorytype;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status) : void
    {
        $this->status = $status;
    }

    public function getPicture(): string
    {
        return $this->picture;
    }

    public function setPicture(string $picture) : void
    {
        $this->picture = $picture;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight) : void
    {
        $this->weight = $weight;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at) : void
    {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(string $updated_at) : void
    {
        $this->updated_at = $updated_at;
    }


}
