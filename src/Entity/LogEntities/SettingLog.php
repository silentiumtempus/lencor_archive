<?php
declare(strict_types=1);

namespace App\Entity\LogEntities;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SettingLog
 * @package App\Entity\LogEntity
 * @ORM\Table(name="log_archive_settings")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class SettingLog extends AbstractLogEntry
{
}
