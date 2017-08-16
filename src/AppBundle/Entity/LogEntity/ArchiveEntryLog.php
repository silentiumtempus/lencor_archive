<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 009 09.06.17
 * Time: 7:26
 */

namespace AppBundle\Entity\LogEntity;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ArchiveEntryLog
 * @package AppBundle\Entity\LogEntity
 * @ORM\Table(name="log_archive_entries")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */

class ArchiveEntryLog  extends AbstractLogEntry
{

}