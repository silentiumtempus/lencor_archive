<?php

namespace AppBundle\Entity\LogEntity;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FileLog
 * @package AppBundle\Entity\LogEntity
 * @ORM\Table(name="log_archive_files")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */

class FileLog  extends AbstractLogEntry
{

}