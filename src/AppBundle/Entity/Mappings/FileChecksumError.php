<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 026 26.08.17
 * Time: 11:58
 */

namespace AppBundle\Entity\Mappings;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class FileChecksumError
 * @package AppBundle\Entity\Mappings
 * @ORM\Entity()
 * @UniqueEntity(
 *     fields={"fileId", "parentFolderId"},
 *     groups={"file_checksum_error"}),
 * @ORM\Table(name="archive_checksum_errors", uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *      name="unique_checksum_entry",
 *      columns={"file_id", "parent_folder_id"}
 *     )
 * }),
 * @Gedmo\Loggable(logEntryClass="AppBundle\Entity\Mappings\LogMappings\FileChecksumErrorLog")
 */
class FileChecksumError
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\FileEntity")
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */

    protected $fileId;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\FolderEntity")
     * @ORM\JoinColumn(name="parent_folder_id", referencedColumnName="id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */

    protected $parentFolderId;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     * @Gedmo\Timestampable(on="create")
     * @Gedmo\Versioned()
     */

    protected $firstOccuredOn;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     * @Gedmo\Timestampable()
     * @Gedmo\Versioned()
     */

    protected $lastCheckOn;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Type("integer")
     * @Gedmo\Versioned()
     */

    protected $lastCheckByUser;

    /**
    * @ORM\Column(name="status", type="integer")
    * @Gedmo\Versioned()
    */


    protected $status;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set firstOccuredOn
     *
     * @param \DateTime $firstOccuredOn
     *
     * @return FileChecksumError
     */
    public function setFirstOccuredOn($firstOccuredOn)
    {
        $this->firstOccuredOn = $firstOccuredOn;

        return $this;
    }

    /**
     * Get firstOccuredOn
     *
     * @return \DateTime
     */
    public function getFirstOccuredOn()
    {
        return $this->firstOccuredOn;
    }

    /**
     * Set lastCheckOn
     *
     * @param \DateTime $lastCheckOn
     *
     * @return FileChecksumError
     */
    public function setLastCheckOn($lastCheckOn)
    {
        $this->lastCheckOn = $lastCheckOn;

        return $this;
    }

    /**
     * Get lastCheckOn
     *
     * @return \DateTime
     */
    public function getLastCheckOn()
    {
        return $this->lastCheckOn;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return FileChecksumError
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set fileId
     *
     * @param \AppBundle\Entity\FileEntity $fileId
     *
     * @return FileChecksumError
     */
    public function setFileId(\AppBundle\Entity\FileEntity $fileId = null)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId
     *
     * @return \AppBundle\Entity\FileEntity
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set parentFolderId
     *
     * @param \AppBundle\Entity\FolderEntity $parentFolderId
     *
     * @return FileChecksumError
     */
    public function setParentFolderId(\AppBundle\Entity\FolderEntity $parentFolderId = null)
    {
        $this->parentFolderId = $parentFolderId;

        return $this;
    }

    /**
     * Get parentFolderId
     *
     * @return \AppBundle\Entity\FolderEntity
     */
    public function getParentFolderId()
    {
        return $this->parentFolderId;
    }

    /**
     * Set lastCheckByUser
     *
     * @param string $lastCheckByUser
     *
     * @return FileChecksumError
     */
    public function setLastCheckByUser($lastCheckByUser)
    {
        $this->lastCheckByUser = $lastCheckByUser;

        return $this;
    }

    /**
     * Get lastCheckByUser
     *
     * @return string
     */
    public function getLastCheckByUser()
    {
        return $this->lastCheckByUser;
    }
}
