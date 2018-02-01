<?php

namespace AppBundle\Entity\LogEntity;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FolderLog
 * @package AppBundle\Entity\LogEntity
 * @ORM\Table(name="log_archive_folders")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class FolderLog extends AbstractLogEntry
{

}