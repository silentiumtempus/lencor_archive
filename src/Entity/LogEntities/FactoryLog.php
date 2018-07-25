<?php

namespace App\Entity\LogEntities;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FactoryLog
 * @package App\Entity\LogEntity
 * @ORM\Table(name="log_archive_factories")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */

class FactoryLog extends AbstractLogEntry
{
}
