<?php

namespace App\Model;

class _LogFileModel
{
    /**
     * @var string $fileName
     */
    protected $fileName;
    /**
     * @var string $fileSize
     */
    protected $fileSize;
    /**
     * @var string $createdAt
     */
    protected $createdAt;
    /**
     * @var string $modifiedAt;
     */
    protected $modifiedAt;

    /**
     * @param string $fileName
     * @return $this
     */
    public function setName(string $fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileSize
     * @return $this
     */
    public function setFileSize(string $fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param string $modifiedAt
     * @return $this
     */
    public function setModifiedAt(string $modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }
}
