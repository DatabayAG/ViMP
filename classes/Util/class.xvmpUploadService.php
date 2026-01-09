<?php

declare(strict_types=1);

use ILIAS\Filesystem\Filesystems;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;
use ILIAS\Filesystem\Exception\IOException;
use ILIAS\FileUpload\Exception\IllegalStateException;

/**
 * Class xvmpUploadService
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpUploadService
{
    private ilViMPPlugin $pl;
    /**
     * @var Filesystems
     */
    protected Filesystems $file_system;
    /**
     * @var FileUpload
     */
    protected FileUpload $file_upload;
    /**
     * @var string[]
     */
    protected array $temp_directories = [];

    /**
     * xvmpUploadService constructor.
     * @param Filesystems $file_system
     * @param FileUpload  $file_upload
     */
    public function __construct(Filesystems $file_system, FileUpload $file_upload)
    {
        $this->pl = ilViMPPlugin::getInstance();
        $this->file_system = $file_system;
        $this->file_upload = $file_upload;
    }

    /**
     * moves file upload to web dir and returns WAC-signed url
     * @param string $tmp_name
     * @param string $tmp_id
     * @return string name (could have been changed by the upload processor)
     * @throws IOException
     * @throws IllegalStateException
     */
    public function moveUploadToWebDir(string $tmp_name, string $tmp_id) : string
    {
        $vimp = '/vimp/';
        $path = ilFileUtils::getWebspaceDir() . $vimp;
        $abs_dir = $path . $tmp_id;
        $rel_dir = $vimp . $tmp_id;
        $this->createDirIfNotExists($rel_dir);
        if (!$this->file_upload->hasBeenProcessed()) {
            $this->file_upload->process();
        }
        $uploadResult = $this->file_upload->getResults()[$tmp_name];
        $this->file_upload->moveOneFileTo(
            $uploadResult,
            $rel_dir,
            Location::WEB,
            $uploadResult->getName()
        );
        return ilFileUtils::getValidFilename($uploadResult->getName());
    }

    /**
     * @param string $dir
     * @throws IOException
     */
    protected function createDirIfNotExists(string $dir) : void
    {
        if (!$this->file_system->web()->hasDir($dir)) {
            $this->file_system->web()->createDir($dir);
        }
    }

    /**
     * @param string $tmp_name
     * @param string $tmp_id
     * @return string
     * @throws ilWACException
     */
    public function getSignedUrl(string $tmp_name, string $tmp_id) : string
    {
        $vimp_path = ILIAS_ABSOLUTE_PATH . '/public/' . ilFileUtils::getWebspaceDir() . '/vimp/';
        $dir = $vimp_path . $tmp_id;
        #$path = $dir . '/' . $this->pl->cleanUpFilename($tmp_name);
        $path = $dir . '/' . $tmp_name;
        $this->temp_directories[] = $dir;
        $path = $this->signWithWAC($path);
        return ILIAS_HTTP_PATH . ltrim($path, '.');
    }

    /**
     * @param string $path
     * @return string
     * @throws ilWACException
     */
    protected function signWithWAC(string $path) : string
    {
        ilWACSignedPath::setTokenMaxLifetimeInSeconds(ilWACSignedPath::MAX_LIFETIME);
        $thumbnail_path = ilWACSignedPath::signFile($path);
        $thumbnail_path .= '&' . ilWebAccessChecker::DISPOSITION . '=' . ilFileDelivery::DISP_ATTACHMENT;
        return $thumbnail_path;
    }

    public function cleanUp() : void
    {
        foreach ($this->temp_directories as $temp_directory) {
            ilFileUtils::delDir(ilFileUtils::getWebspaceDir() . $temp_directory);
        }
    }

}
