<?php

namespace App\Entity;

use App\Entity\Traits\CommonTrait;
use App\Entity\Traits\DeleteStateTrait;
use App\Entity\Traits\FolderFileTrait;
use App\Entity\Traits\SlugTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class FileEntity
 * @package App\Entity
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *     fields = {"fileName", "parentFolder"},
 *     groups = {"file_upload"}),
 * @ORM\Table(name="archive_files", uniqueConstraints = {
 *     @ORM\UniqueConstraint(
 *      name = "unique_file",
 *      columns = {"file_name", "parent_folder_id"}
 *     )
 * }),
 * @Gedmo\Loggable(logEntryClass = "App\Entity\LogEntity\FileLog")
 */
class FileEntity
{
    use CommonTrait;
    use FolderFileTrait;
    use SlugTrait;
    use DeleteStateTrait;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */

    protected $fileName;

    /**
     * @Assert\NotBlank
     * @Assert\File
     * @Gedmo\Versioned()
     */

    protected $files;

    /**
     * @ORM\ManyToOne(targetEntity = "FolderEntity")
     * @ORM\JoinColumn(name = "parent_folder_id", referencedColumnName = "id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */

    protected $parentFolder;

    /**
     * @ORM\Column(type = "string")
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $checksum;

    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     */

    protected $sumError;

    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields = {"fileName"})
     * @ORM\Column(name = "slug", type = "string", length = 128)
     */

    private $slug;

    public function __toString()
    {
        return $this->fileName;
    }

    /**
     * Set files
     * @param array $files
     * @return FileEntity
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get files
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set fileName
     * @param string $fileName
     * @return FileEntity
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set checksum
     * @param string $checksum
     * @return FileEntity
     */
    public function setChecksum($checksum)
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * Get checksum
     * @return string
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * Set sumError
     * @param boolean $sumError
     * @return FileEntity
     */
    public function setSumError($sumError)
    {
        $this->sumError = $sumError;

        return $this;
    }

    /**
     * Get sumError
     * @return boolean
     */
    public function getSumError()
    {
        return $this->sumError;
    }
}
