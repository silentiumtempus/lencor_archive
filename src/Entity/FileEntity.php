<?php
declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\CommonTrait;
use App\Entity\Traits\DeletedStateTrait;
use App\Entity\Traits\RemovalMarkTrait;
use App\Entity\Traits\FolderFileTrait;
use App\Entity\Traits\RestorationRequestsTrait;
use App\Entity\Traits\SlugTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class FileEntity
 * @package App\Entity
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *     fields = {"fileName", "parentFolder"},
 *     groups = {"file_common"}),
 * @ORM\Table(
 *     name="archive_files",
 *     uniqueConstraints = {
 *        @ORM\UniqueConstraint(
 *           name = "unique_file",
 *           columns = {"file_name", "parent_folder_id"}
 *       )
 * }),
 * @Gedmo\Loggable(logEntryClass = "App\Entity\LogEntities\FileLog")
 */
class FileEntity
{
    use CommonTrait;
    use FolderFileTrait;
    use SlugTrait;
    use RemovalMarkTrait;
    use RestorationRequestsTrait;
    use DeletedStateTrait;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */
    protected $fileName;

    /**
     * @Assert\NotBlank
     * @Assert\File
     * @Gedmo\Versioned()
     * @Serializer\Type("Symfony\Component\HttpFoundation\File\File")
     */
    protected $uploadedFiles;

    /**
     * @ORM\ManyToOne(targetEntity = "FolderEntity", inversedBy = "files")
     * @ORM\JoinColumn(name = "parent_folder_id", referencedColumnName = "id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\FolderEntity")
     */
    protected $parentFolder;

    /**
     * @ORM\Column(type = "string")
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */
    protected $checksum;

    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Serializer\Type("boolean")
     */
    protected $sumError;

    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields = {"fileName"})
     * @ORM\Column(name = "slug", type = "string", length = 128)
     * @Serializer\Type("string")
     */
    private $slug;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requestedByUsers = new ArrayCollection();
    }

    /**
     * Convert to string
     * @return mixed
     */
    public function __toString(): string
    {
        return $this->fileName;
    }

    /**
     * Set files
     * @param array $files
     * @return FileEntity
     */
    public function setUploadedFiles($files): self
    {
        $this->uploadedFiles = $files;

        return $this;
    }

    /**
     * Get files
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Set fileName
     * @param string $fileName
     * @return FileEntity
     */
    public function setFileName($fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     * @return string
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * Set checksum
     * @param string $checksum
     * @return FileEntity
     */
    public function setChecksum($checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * Get checksum
     * @return string
     */
    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    /**
     * Set sumError
     * @param boolean $sumError
     * @return FileEntity
     */
    public function setSumError($sumError): self
    {
        $this->sumError = $sumError;

        return $this;
    }

    /**
     * Get sumError
     * @return boolean
     */
    public function getSumError(): ?bool
    {
        return $this->sumError;
    }
}
