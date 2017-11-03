<?php

namespace AppBundle\Service;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\User;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class LoggingService
 * @package AppBundle\Service
 */
class LoggingService
{

    /**
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @param string $entryPath
     * @param User $user
     * @param array $messages
     */
    public function logEntry(ArchiveEntryEntity $archiveEntryEntity, string $entryPath, User $user, array $messages)
    {
        $fs = new Filesystem();
        $logFileName = $entryPath . "/" . $archiveEntryEntity->getArchiveNumber() . ".log";
        $fs->touch($logFileName);
        foreach ($messages as $message)
        {
            //$message = "[" . date('Y') . "] : " .  " " . $message;
            $fs->appendToFile($logFileName, $message);
        }
    }
}