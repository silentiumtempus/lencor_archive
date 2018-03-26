<?php

namespace App\Entity\LogEntity;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FileLog
 * @package App\Entity\LogEntity
 * @ORM\Table(name="log_archive_files")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */

class FileLog extends AbstractLogEntry
{
}
