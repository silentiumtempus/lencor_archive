<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 009 09.06.17
 * Time: 7:13
 */

namespace AppBundle\Entity\LogEntity;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SettingLog
 * @package AppBundle\Entity\LogEntity
 * @ORM\Table(name="log_archive_settings")
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */

class SettingLog extends AbstractLogEntry
{

}