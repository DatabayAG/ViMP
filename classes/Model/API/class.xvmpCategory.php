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


    public static function cache($identifier, $object, $ttl = null)
    {
        parent::cache($identifier, $object, (int) ($ttl ? $ttl : xvmpConf::getConfig(xvmpConf::F_CACHE_TTL_CATEGORIES)));
    }


    /**
     * @var int
     */
    protected int $cid;
    /**
     * @var int
     */
    protected int $pid;
    /**
     * @var int
     */
    protected ?int $parent;
    /**
     * @var String
     */
    protected string $culture;
    /**
     * @var String
     */
    protected string $name;
    /**
     * @var String
     */
    protected ?string $description;
    /**
     * @var String
     */
    protected string $categorytype;
    /**
     * @var String
     */
    protected string $status;
    /**
     * @var String
     */
    protected string $picture;
    /**
     * @var int
     */
    protected int $weight;
    /**
     * @var String
     */
    protected string $created_at;
    /**
     * @var String
     */
    protected string $updated_at;


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->cid;
    }

    /**
     * @return int
     */
    public function getCid(): int
    {
        return $this->cid;
    }


    /**
     * @param int $cid
     */
    public function setCid(int $cid)
    {
        $this->cid = $cid;
    }


    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }


    /**
     * @param int $pid
     */
    public function setPid(int $pid)
    {
        $this->pid = $pid;
    }


    /**
     * @return int
     */
    public function getParent(): ?int
    {
        return $this->parent;
    }


    /**
     * @param int $parent
     */
    public function setParent(int $parent)
    {
        $this->parent = $parent;
    }


    /**
     * @return String
     */
    public function getCulture(): string
    {
        return $this->culture;
    }


    /**
     * @param String $culture
     */
    public function setCulture(string $culture)
    {
        $this->culture = $culture;
    }


    /**
     * @return String
     */
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


    /**
     * @param String $name
     */
    public function setName(string $name)
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
    public function setDescription(string $description)
    {
        $this->description = $description;
    }


    /**
     * @return String
     */
    public function getCategorytype(): string
    {
        return $this->categorytype;
    }


    /**
     * @param String $categorytype
     */
    public function setCategorytype(string $categorytype)
    {
        $this->categorytype = $categorytype;
    }


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
    public function setStatus(string $status)
    {
        $this->status = $status;
    }


    /**
     * @return String
     */
    public function getPicture(): string
    {
        return $this->picture;
    }


    /**
     * @param String $picture
     */
    public function setPicture(string $picture)
    {
        $this->picture = $picture;
    }


    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }


    /**
     * @param int $weight
     */
    public function setWeight(int $weight)
    {
        $this->weight = $weight;
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
    public function setCreatedAt(string $created_at)
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
    public function setUpdatedAt(string $updated_at)
    {
        $this->updated_at = $updated_at;
    }


}
