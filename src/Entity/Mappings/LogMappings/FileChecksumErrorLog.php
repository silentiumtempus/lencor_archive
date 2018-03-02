<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 026 26.08.17
 * Time: 12:05
 */

namespace App\Entity\Mappings\LogMappings;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FileChecksumErrorLog
 * @package App\Entity\Mappings\LogMappings
 * @ORM\Table(name="log_archive_checksum_errors")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class FileChecksumErrorLog extends AbstractLogEntry
{

}