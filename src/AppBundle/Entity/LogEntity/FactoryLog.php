<?php

namespace AppBundle\Entity\LogEntity;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FactoryLog
 * @package AppBundle\Entity\LogEntity
 * @ORM\Table(name="log_archive_factories")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */

class FactoryLog extends AbstractLogEntry
{

}