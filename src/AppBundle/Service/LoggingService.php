<?php

namespace AppBundle\Service;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormInterface;

/**
 * Class LoggingService
 * @package AppBundle\Service
 */
class LoggingService
{
    protected $em;
    protected $container;
    protected $pathRoot;
    protected $foldersRepository;
    protected $entriesRepository;
    protected $pathPermissions;

    /**
     * LoggingService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->entriesRepository = $this->em->getRepository('AppBundle:ArchiveEntryEntity');
        $this->pathPermissions = $this->container->getParameter('lencor_archive.storage_permissions');
    }

    /**
     * @param string $entryFolderName
     * @return string
     */
    public function getLogsPath(string $entryFolderName)
    {
        return $this->pathRoot . "/" . $entryFolderName . "/logs";
    }

    /**
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @param string $logsDir
     * @param User $user
     * @param array $flashArray
     */
    public function logEntry(ArchiveEntryEntity $archiveEntryEntity, string $logsDir, User $user, array $flashArray)
    {
        $fs = new Filesystem();
        if (!$fs->exists($logsDir)) {
            $fs->mkdir($logsDir, $this->pathPermissions);
        }
        $logFileName = $logsDir . "/" . $archiveEntryEntity->getArchiveNumber() . ".log";

        if (!$fs->exists($logFileName)) {
            $fs->touch($logFileName);
        }
        $date = new \DateTime();
        $dateString = $date->format('Y-m-d H:i:s');
        $recordTemplate = "[" . $dateString . "] :" . " (" . $user->getUsername() . ") ";
        foreach ($flashArray as $messagesArray) {
            foreach ($messagesArray as $message) {
                $logRecord = $recordTemplate . $message . "\n";
                $fs->appendToFile($logFileName, $logRecord);
            }
        }
    }

    /**
     * @param int $entryId
     * @param User $user
     * @param array $messages
     */
    public function logEntryContent(int $entryId, User $user, array $messages)
    {
        $entry = $this->entriesRepository->findOneById($entryId);
        $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        $logsDir = $this->getLogsPath($rootFolder->getFolderName());
        $this->logEntry($entry, $logsDir, $user, $messages);
    }

    /**
     * @param FormInterface $logSearchForm
     * @return string
     */
    public function getEntryLogsFolder(FormInterface $logSearchForm)
    {
        $entryId = $logSearchForm->getViewData('id');
        $entryFolder = $this->foldersRepository->findOneById($entryId);
        return $entryFolder ? $this->getLogsPath($entryFolder->getFolderName()) : '';
    }

    /**
     * @param string $logsPath
     * @return array
     */
    public function getEntryLogs(string $logsPath)
    {
        $files = [];
        $finder = new Finder();
        $finder->files()->in($logsPath);
        $finder->sortByModifiedTime();
        foreach ($finder as $file)
        {
            $files[] = $file->getFilename();
        }
            //$files = iterator_to_array($finder->files()->in($logsPath));
        return $files;
    }
}