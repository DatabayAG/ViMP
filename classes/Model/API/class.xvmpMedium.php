<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpMedium
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpMedium extends xvmpObject
{
    public const PUBLISHED_PUBLIC = 'public';
    public const PUBLISHED_PRIVATE = 'private';
    public const PUBLISHED_HIDDEN = 'hidden';

    public const F_MID = 'mid';
    public const F_UID = 'uid';
    public const F_USERNAME = 'username';
    public const F_MEDIAKEY = 'mediakey';
    public const F_MEDIAPERMISSIONS = 'mediapermissions';
    public const F_MEDIATYPE = 'mediatype';
    public const F_MEDIASUBTYPE = 'mediasubtype';
    public const F_PUBLISHED = 'published';
    public const F_STATUS = 'status';
    public const F_FEATURED = 'featured';
    public const F_CULTURE = 'culture';
    public const F_PROPERTIES = 'properties';
    public const F_TITLE = 'title';
    public const F_DESCRIPTION = 'description';
    public const F_DURATION = 'duration';
    public const F_THUMBNAIL = 'thumbnail';
    public const F_EMBED_CODE = 'embed_code';
    public const F_MEDIUM = 'medium';
    public const F_SOURCE = 'source';
    public const F_META_TITLE = 'meta_title';
    public const F_META_DESCRIPTION = 'meta_description';
    public const F_META_KEYWORDS = 'meta_keywords';
    public const F_META_AUTHOR = 'meta_author';
    public const F_META_COPYRIGHT = 'meta_copyright';
    public const F_SUM_RATING = 'sum_rating';
    public const F_COUNT_VIEWS = 'count_views';
    public const F_COUNT_RATING = 'count_rating';
    public const F_COUNT_FAVORITES = 'count_favorites';
    public const F_COUNT_COMMENTS = 'count_comments';
    public const F_COUNT_FLAGS = 'count_flags';
    public const F_CREATED_AT = 'created_at';
    public const F_UPDATED_AT = 'updated_at';
    public const F_TAGS = 'tags';
    public const F_CATEGORIES = 'categories';
    public const F_SUBTITLES = 'subtitles';

    public static array $published_id_mapping = array(
        'public' => "0",
        'private' => "1",
        'hidden' => "2",
    );
    protected int $mid;
    protected int $uid;
    protected string $username;
    protected string $mediakey;
    protected array $mediapermissions;
    protected string $mediatype;
    protected string $mediasubtype;
    protected string $published;
    protected string $status;
    protected bool $featured;
    protected string $culture;
    protected ?array $properties = [];
    protected string $title;
    protected ?string $description;
    protected ?int $duration;
    protected ?string $duration_formatted;
    protected ?string $thumbnail;
    protected ?string $embed_code;
    protected string|array $medium;
    protected ?string $source;
    protected ?string $meta_title;
    protected ?string $meta_description;
    protected ?string $meta_keywords;
    protected ?string $meta_author;
    protected ?string $meta_copyright;
    protected int $sum_rating;
    protected int $count_views;
    protected int $count_rating;
    protected int $count_favorites;
    protected int $count_comments;
    protected int $count_flags;
    protected string $created_at;
    protected string $updated_at;
    protected array $categories;
    protected string $tags;
    protected bool $accept_comment;
    protected string $slug;
    protected int $count_likes;
    protected ?array $subtitles = [];
    protected bool $download_allowed = false;
    protected ?DateTime $startdate = null;
    protected ?DateTime $enddate = null;
    protected string $edited_at;

    /**
     * @param null  $ilObjUser
     * @param array $filter
     * @return array
     */
    public static function getUserMedia($ilObjUser = null, array $filter = array()) : array
    {
        if (!$ilObjUser) {
            global $DIC;
            $ilUser = $DIC['ilUser'];
            $ilObjUser = $ilUser;
        }

        $uid = xvmpUser::getOrCreateVimpUser($ilObjUser)['uid'];
        $response = xvmpRequest::getUserMedia($uid, $filter)->getResponseArray()['media']['medium'] ?? array();
        if (!$response) {
            return array();
        }

        if (isset($response['mid'])) {
            $response = array($response);
        }

        foreach ($response as $key => $medium) {
            if ($medium['mediatype'] != 'video') {
                unset($response[$key]);
            }
        }
        return $response;
    }

    /**
     * @return int
     */
    public function getUid() : int
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     */
    public function setUid(int $uid) : void
    {
        $this->uid = $uid;
    }

    /**
     * @param $obj_id
     * @return array
     * @throws xvmpException
     */
    public static function getAvailableForLP($obj_id) : array
    {
        $selected = self::getSelectedAsArray($obj_id);
        foreach ($selected as $key => $video) {
            if (self::isVimeoOrYoutube($video) || (isset($video['status']) && $video['status'] === 'deleted')) {
                unset($selected[$key]);
            }
        }
        return $selected;
    }

    /**
     * @param $obj_id
     * @return array
     * @throws xvmpException
     */
    public static function getSelectedAsArray($obj_id) : array
    {
        $selected = xvmpSelectedMedia::getSelected($obj_id);
        $videos = array();
        foreach ($selected as $rec) {
            try {
                $item = self::getObjectAsArray($rec->getMid());
            } catch (xvmpException $e) {
                if ($e->getCode() == 404) {
                    $deleted = new xvmpDeletedMedium();
                    $deleted->setMid($rec->getMid());
                    $item = $deleted->__toArray();
                } else {
                    throw $e;
                }
            }
            $item['visible'] = $rec->getVisible();
            $videos[] = $item;
        }
        return $videos;
    }

    /**
     * @param $id
     * @return array
     * @throws xvmpException
     */
    public static function getObjectAsArray($id) : array
    {
        global $DIC;
        $key = self::class . '-' . $id;
        $existing = xvmpCacheFactory::getInstance()->get($key, $DIC->refinery()->to()->string());
        if ($existing) {
            $existing = json_decode($existing, true);
            xvmpCurlLog::getInstance()->write('CACHE: used cached: ' . $key, xvmpCurlLog::DEBUG_LEVEL_2);
            return $existing;
        }

        xvmpCurlLog::getInstance()->write('CACHE: cached not used: ' . $key, xvmpCurlLog::DEBUG_LEVEL_2);

        $response = xvmpRequest::getMedium((int) $id)->getResponseArray();
        $response = $response['medium'];
        $response = self::formatResponse($response);

        if ($response['status'] == 'legal') { // do not cache transcoding videos, we need to fetch them again to check the status
            self::cache($key, $response);
        }
        return $response;
    }

    /**
     * @return array|string
     */
    public function getMedium() : array|string
    {
        return $this->medium;
    }

    /**
     * @param array|string $medium
     */
    public function setMedium(array|string $medium) : void
    {
        $this->medium = $medium;
    }

    /**
     * some attributes have to be formatted to fill the form correctly
     */
    public static function formatResponse($response)
    {
        $response['duration_formatted'] = gmdate("H:i:s", $response['duration'] ?? 0);
        $response['description'] = strip_tags(html_entity_decode((string) $response['description']));
        $response['title'] = (string) $response['title'];
        $response['slug'] = (string) $response['slug'];

        if (isset($response['mediapermissions']) && isset($response['mediapermissions']['rid']) && is_array($response['mediapermissions']['rid'])) {
            $response['mediapermissions'] = $response['mediapermissions']['rid'];
        }

        $date_fields = ['startdate', 'enddate'];
        foreach ($date_fields as $date_field) {
            if (isset($response[$date_field])) {
                try {
                    $response[$date_field] = new DateTime($response[$date_field]);
                } catch (Exception $e) {
                    xvmpCurlLog::getInstance()->writeWarning("couldn't parse date '$response[$date_field]' from field $date_field");
                }
            }
        }

        foreach (array(array('categories', 'category', 'cid'), array('tags', 'tag', 'tid')) as $labels) {
            $result = array();
            if (isset($response[$labels[0]][$labels[1]][$labels[2]])) {
                $response[$labels[0]][$labels[1]] = array($response[$labels[0]][$labels[1]]);
            }
            if (isset($response[$labels[0]]) && is_array($response[$labels[0]][$labels[1]])) {
                foreach ($response[$labels[0]][$labels[1]] as $item) {
                    $result[$item[$labels[2]]] = $item['name'];
                }
            }
            $response[$labels[0]] = $labels[0] == 'tags' ? implode(', ', $result) : $result;
        }
        return $response;
    }

    /**
     * @param       $identifier
     * @param       $object
     * @param null  $ttl
     */
    public static function cache($identifier, $object, $ttl = null) : void
    {
        parent::cache($identifier, $object, (int) ($ttl ?: xvmpConf::getConfig(xvmpConf::F_CACHE_TTL_VIDEOS)));
    }

    /**
     * @return int
     */
    public function getMid() : int
    {
        return $this->mid;
    }

    /**
     * @param int $mid
     */
    public function setMid(int $mid) : void
    {
        $this->mid = $mid;
    }

    /**
     * @param $video xvmpMedium|array
     * @return bool
     * @throws xvmpException
     */
    public static function isVimeoOrYoutube(xvmpMedium|array $video) : bool
    {
        if (is_array($video)) {
            return in_array($video['mediasubtype'] ?? array(), ['youtube', 'vimeo']);
        } elseif ($video instanceof xvmpMedium) {
            if ($video->getStatus() !== 'deleted') {
                return in_array($video->getMediasubtype(), ['youtube', 'vimeo']);
            }
            return false;
        } else {
            throw new xvmpException(xvmpException::INTERNAL_ERROR,
                '$video must be of type array or xvmpMedium: ' . print_r($video, true));
        }
    }

    /**
     * @return String
     */
    public function getStatus() : string
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
    public function getMediasubtype() : string
    {
        return $this->mediasubtype;
    }

    /**
     * @param String $mediasubtype
     */
    public function setMediasubtype(string $mediasubtype) : void
    {
        $this->mediasubtype = $mediasubtype;
    }

    /**
     * @param array $filter
     * @return array
     * @throws xvmpException
     */
    public static function getFilteredAsArray(array $filter) : array
    {
        if (!isset($filter['title'])) {
            $filter['title'] = '';
        }

        $filter['searchrange'] = 'video';

        try {
            $response = xvmpRequest::extendedSearch($filter)->getResponseArray();
        } catch (xvmpException $e) {    // api throws 404 exception if nothing is found
            if ($e->getCode() == 404) {
                return array();
            }
            throw $e;
        }

        if (isset($response['media']['medium']['mid'])) {
            return array(self::formatResponse($response['media']['medium']));
        }
        $return = array();
        if (isset($response['media']['medium'])) {
            foreach ($response['media']['medium'] as $medium) {
                $return[] = self::formatResponse($medium);
            }
        }
        return $return;
    }

    /**
     * @return mixed
     * @throws xvmpException
     */
    public static function getAllAsArray() : array
    {
        $response = xvmpRequest::getMedia()->getResponseArray();
        return $response['media']['medium'];
    }

    /**
     * @param array $video
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function update(array $video) : xvmpCurl
    {
        $response = xvmpRequest::editMedium((int) $video['mid'], $video);
        xvmpCacheFactory::getInstance()->delete(self::class . '-' . $video['mid']);
        return $response;
    }

    /**
     * @param $video
     * @param $obj_id
     * @param $add_automatically
     * @param $notification
     * @return mixed
     * @throws xvmpException
     */
    public static function upload($video, $obj_id, $add_automatically, $notification)
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $response = xvmpRequest::uploadMedium($video);
        $medium = $response->getResponseArray()['medium'];
        $references = ilObject::_getAllReferences($obj_id);
        $ref_id = array_shift($references);

        if ($add_automatically) {
            xvmpSelectedMedia::addVideo($medium['mid'], $obj_id, false);
        }

        $uploaded_media = new xvmpUploadedMedia();
        $uploaded_media->setMid($medium['mid']);
        $uploaded_media->setNotification($notification);
        $uploaded_media->setEmail($ilUser->getEmail());
        $uploaded_media->setUserId($ilUser->getId());
        $uploaded_media->setRefId($ref_id);
        $uploaded_media->create();

        return $medium;
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->getMid();
    }

    public static function deleteObject(int $mid) : void
    {
        try {
            xvmpCacheFactory::getInstance()->delete(self::class . '-' . $mid);
            xvmpRequest::deleteMedium($mid);
            xvmpSelectedMedia::deleteVideo($mid);
            if ($uploaded_media = xvmpUploadedMedia::find($mid)) {
                $uploaded_media->delete();
            }
        } catch (xvmpException $e) {
            if ($e->getCode() == 404) {
                xvmpCurlLog::getInstance()->writeWarning("couldn't delete video $mid, it was not found");
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param $id
     * @return xvmpObject
     * @throws Exception
     */
    public static function find($id) : xvmpObject
    {
        try {
            return parent::find($id);
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                $deleted = new xvmpDeletedMedium();
                $deleted->setMid((int) $id);
                return $deleted;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @return array lang_code => url
     */
    public function getSubtitles() : array
    {
        return $this->subtitles ?? [];
    }

    /**
     * @param array $subtitles
     */
    public function setSubtitles(array $subtitles) : void
    {
        $this->subtitles = $subtitles;
    }

    /**
     * @return array
     */
    public function getMediapermissions() : array
    {
        return $this->mediapermissions;
    }

    /**
     * @param array $mediapermissions
     */
    public function setMediapermissions(array $mediapermissions) : void
    {
        $this->mediapermissions = $mediapermissions;
    }

    /**
     * @return bool
     */
    public function isDownloadAllowed() : bool
    {
        return $this->download_allowed;
    }

    /**
     * @return DateTime|null
     */
    public function getStartdate() : ?DateTime /*: ?DateTime*/
    {
        return $this->startdate;
    }

    /**
     * @param DateTime $startdate
     */
    public function setStartdate(DateTime $startdate) : void
    {
        $this->startdate = $startdate;
    }

    /**
     * @return DateTime|null
     */
    public function getEnddate() : ?DateTime /*: ?DateTime*/
    {
        return $this->enddate;
    }

    /**
     * @param DateTime $enddate
     */
    public function setEnddate(DateTime $enddate) : void
    {
        $this->enddate = $enddate;
    }

    public function isAvailable() : bool
    {
        return (is_null($this->startdate) || time() > $this->startdate->getTimestamp())
            && (is_null($this->enddate) || time() > $this->enddate->getTimestamp());
    }

    /**
     * @param int $id
     */
    public function setId(int $id) : void
    {
        $this->setMid($id);
    }

    public function isCurrentUserOwner() : bool
    {
        global $DIC;
        $user = $DIC['ilUser'];
        $vimp_user = xvmpUser::getVimpUser($user);
        return ($vimp_user && ($vimp_user['uid'] == $this->getUid()));
    }

    /**
     * @return String
     */
    public function getUsername() : string
    {
        return $this->username;
    }

    /**
     * @param String $username
     */
    public function setUsername(string $username) : void
    {
        $this->username = $username;
    }

    /**
     * @return String
     */
    public function getMediakey() : string
    {
        return $this->mediakey;
    }

    /**
     * @param String $mediakey
     */
    public function setMediakey(string $mediakey) : void
    {
        $this->mediakey = $mediakey;
    }

    /**
     * @return String
     */
    public function getMediatype() : string
    {
        return $this->mediatype;
    }

    /**
     * @param String $mediatype
     */
    public function setMediatype(string $mediatype) : void
    {
        $this->mediatype = $mediatype;
    }

    /**
     * @return bool
     */
    public function isPublic() : bool
    {
        return $this->published == self::PUBLISHED_PUBLIC;
    }

    /**
     * @return String
     */
    public function getPublished() : string
    {
        return $this->published;
    }

    /**
     * @param String $published
     */
    public function setPublished(string $published) : void
    {
        $this->published = $published;
    }

    /**
     * @return mixed
     */
    public function getPublishedId()
    {
        return self::$published_id_mapping[$this->published];
    }

    /**
     * @return bool
     */
    public function isFeatured() : bool
    {
        return $this->featured;
    }

    /**
     * @param bool $featured
     */
    public function setFeatured(bool $featured) : void
    {
        $this->featured = $featured;
    }

    /**
     * @return String
     */
    public function getCulture() : string
    {
        return $this->culture;
    }

    /**
     * @param String $culture
     */
    public function setCulture(string $culture) : void
    {
        $this->culture = $culture;
    }

    /**
     * @return array
     */
    public function getProperties() : array
    {
        return $this->properties ?? [];
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties) : void
    {
        $this->properties = $properties;
    }

    /**
     * @return String
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * @param String $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @param int $max_length
     * @return String
     */
    public function getDescription(int $max_length = 0) : string
    {
        if ($max_length && mb_strlen($this->description) > $max_length) {
            return mb_substr($this->description, 0, $max_length) . '...';
        }
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
     * @return int
     */
    public function getDuration() : int
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration(int $duration) : void
    {
        $this->duration = $duration;
    }

    /**
     * @return string
     */
    public function getDurationFormatted() : string
    {
        return $this->duration_formatted;
    }

    /**
     * @param String $duration_formatted
     */
    public function setDurationFormatted(string $duration_formatted) : void
    {
        $this->duration_formatted = $duration_formatted;
    }

    /**
     * @param int $width
     * @param int $height
     * @return String
     */
    public function getThumbnail(int $width = 0, int $height = 0) : string
    {
        if ($width && $height) {
            return $this->thumbnail . "&size={$width}x{$height}";
        }
        return $this->thumbnail;
    }

    /**
     * @param String $thumbnail
     */
    public function setThumbnail(string $thumbnail) : void
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @param int $width
     * @param int $height
     * @return String
     */
    public function getEmbedCode(int $width = 0, int $height = 0) : string
    {
        if ($width || $height) {

            return '<div class="xvmp_embed_wrapper" style="width:' . $width . ';height:' . $height . ';">' . $this->embed_code . '</div>';
        }
        return str_replace('responsive=false', 'responsive=true', $this->embed_code);
    }

    /**
     * @param String $embed_code
     */
    public function setEmbedCode(string $embed_code) : void
    {
        $this->embed_code = $embed_code;
    }

    /**
     * @return String
     */
    public function getSource() : string
    {
        return $this->source;
    }

    /**
     * @param String $source
     */
    public function setSource(string $source) : void
    {
        $this->source = $source;
    }

    /**
     * @return String
     */
    public function getMetaTitle() : string
    {
        return $this->meta_title;
    }

    /**
     * @param String $meta_title
     */
    public function setMetaTitle(string $meta_title) : void
    {
        $this->meta_title = $meta_title;
    }

    /**
     * @return String
     */
    public function getMetaDescription() : string
    {
        return $this->meta_description;
    }

    /**
     * @param String $meta_description
     */
    public function setMetaDescription(string $meta_description) : void
    {
        $this->meta_description = $meta_description;
    }

    /**
     * @return String
     */
    public function getMetaKeywords() : string
    {
        return $this->meta_keywords;
    }

    /**
     * @param String $meta_keywords
     */
    public function setMetaKeywords(string $meta_keywords) : void
    {
        $this->meta_keywords = $meta_keywords;
    }

    /**
     * @return String
     */
    public function getMetaAuthor() : string
    {
        return $this->meta_author;
    }

    /**
     * @param String $meta_author
     */
    public function setMetaAuthor(string $meta_author) : void
    {
        $this->meta_author = $meta_author;
    }

    /**
     * @return String
     */
    public function getMetaCopyright() : string
    {
        return $this->meta_copyright;
    }

    /**
     * @param String $meta_copyright
     */
    public function setMetaCopyright(string $meta_copyright) : void
    {
        $this->meta_copyright = $meta_copyright;
    }

    /**
     * @return int
     */
    public function getSumRating() : int
    {
        return $this->sum_rating;
    }

    /**
     * @param int $sum_rating
     */
    public function setSumRating(int $sum_rating) : void
    {
        $this->sum_rating = $sum_rating;
    }

    /**
     * @return int
     */
    public function getCountViews() : int
    {
        return $this->count_views;
    }

    /**
     * @param int $count_views
     */
    public function setCountViews(int $count_views) : void
    {
        $this->count_views = $count_views;
    }

    /**
     * @return int
     */
    public function getCountRating() : int
    {
        return $this->count_rating;
    }

    /**
     * @param int $count_rating
     */
    public function setCountRating(int $count_rating) : void
    {
        $this->count_rating = $count_rating;
    }

    /**
     * @return int
     */
    public function getCountFavorites() : int
    {
        return $this->count_favorites;
    }

    /**
     * @param int $count_favorites
     */
    public function setCountFavorites(int $count_favorites) : void
    {
        $this->count_favorites = $count_favorites;
    }

    /**
     * @return int
     */
    public function getCountComments() : int
    {
        return $this->count_comments;
    }

    /**
     * @param int $count_comments
     */
    public function setCountComments(int $count_comments) : void
    {
        $this->count_comments = $count_comments;
    }

    /**
     * @return int
     */
    public function getCountFlags() : int
    {
        return $this->count_flags;
    }

    /**
     * @param int $count_flags
     */
    public function setCountFlags(int $count_flags) : void
    {
        $this->count_flags = $count_flags;
    }

    /**
     * @param string $format
     * @return String
     */
    public function getCreatedAt(string $format = '') : string
    {
        if ($format) {
            $timestamp = strtotime($this->created_at);
            return date($format, $timestamp);
        }
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
    public function getUpdatedAt() : string
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

    /**
     * @return array
     */
    public function getCategories() : array
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     */
    public function setCategories(array $categories) : void
    {
        $this->categories = $categories;
    }

    /**
     * @return string
     */
    public function getTags() : string
    {
        return $this->tags;
    }

    /**
     * @param string $tags
     */
    public function setTags(string $tags) : void
    {
        $this->tags = $tags;
    }

    /**
     * @return bool
     */
    public function isTranscoded() : bool
    {
        return $this->getStatus() === 'legal';
    }
}
