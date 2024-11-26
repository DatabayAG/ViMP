<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpUploadFile
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpUploadFile
{
    /**
     * @param $name
     *
     * @return xvmpUploadFile
     */
    public static function getInstanceFromFileArray($name): xvmpUploadFile
    {
        $file = $_POST[$name];

        $inst = new self();
        $inst->setTitle($file['name']);
        $inst->setFilePath($file['tmp_name']);
        $inst->setFileSize($file['size']);
        $inst->setPostVar($name);

        return $inst;
    }


    /**
     * @return CURLFile
     */
    public function getCURLFile() : CURLFile
    {
        $xvmpPlupload = new xvmpPlupload();
        $CURLFile = new CURLFile($xvmpPlupload->getTargetDir() . '/' . $this->getTitle());

        return $CURLFile;
    }


    /**
     * @var string
     */
    protected string $file_path = '';
    /**
     * @var string
     */
    protected string $title = '';
    /**
     * @var int
     */
    protected int $file_size = 0;
    /**
     * @var string
     */
    protected string $post_var = '';
    /**
     * @var string
     */
    protected string $mime_type = '';


    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->file_path;
    }


    /**
     * @param string $file_path
     */
    public function setFilePath(string $file_path) : void
    {
        $this->file_path = $file_path;
    }


    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }


    /**
     * @param string $title
     */
    public function setTitle(string $title) : void
    {
        $this->title = $title;
    }


    /**
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->file_size;
    }


    /**
     * @param int $file_size
     */
    public function setFileSize(int $file_size) : void
    {
        $this->file_size = $file_size;
    }


    /**
     * @return string
     */
    public function getPostVar(): string
    {
        return $this->post_var;
    }


    /**
     * @param string $post_var
     */
    public function setPostVar(string $post_var) : void
    {
        $this->post_var = $post_var;
    }


    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mime_type;
    }


    /**
     * @param string $mime_type
     */
    public function setMimeType(string $mime_type) : void
    {
        $this->mime_type = $mime_type;
    }
}
