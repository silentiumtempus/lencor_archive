<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpExtended\Tail\Tail;
use PhpExtended\Tail\TailException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class LoggingService
 * @package App\Service
 */
class LoggingService
{
    private $em;
    private $container;
    private $pathRoot;
    private $pathHTTP;
    private $foldersRepository;
    private $entriesRepository;
    private $pathPermissions;

    /**
     * LoggingService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');;
        $this->pathHTTP = $this->container->getParameter('archive.http_path');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
        $this->pathPermissions = $this->container->getParameter('archive.storage_permissions');
    }

    /**
     * @param int $entryId
     * @return string
     */
    public function getLogsRootPath(int $entryId)
    {
        $entryFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        if ($entryFolder) {

            return $entryFolder->getFolderName() . "/logs";
        } else {

            return false;
        }
    }

    /**
     * @param int $entryId
     * @return string
     */
    public function getLogsHTTPPath(int $entryId)
    {
        $entryFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        if ($entryFolder) {

            return $this->pathHTTP . "/" . $entryFolder->getFolderName() . "/logs";
        } else {

            return false;
        }
    }

    /**
     * @param string $parentFolder
     * @param string $folder
     * @return array|null
     */
    public function getLogsNavigationPath(string $parentFolder, string $folder)
    {
        if ($folder == "/" and $parentFolder == "") {
            return null;
        } elseif ($parentFolder == "") {
            return ["" => $folder];
        } else {
            $pathsArray = [];
            $path = "";
            $parentFolder = ltrim($parentFolder, "/");
            $folderArray = explode('/', ($parentFolder . "/" . $folder));
            foreach ($folderArray as $folder) {
                $pathsArray[$path] = $folder;
                $path .= "/" . $folder;
            }

            return $pathsArray;
        }

        //return (($parentFolder != "") ? explode('/', ($parentFolder . "/" .  $folder)) : $folder);
    }

    /**
     * @param string $parentFolder
     * @param string $folder
     * @return string
     */
    public function getLogsCurrentFolder(string $parentFolder, string $folder)
    {
        if ($folder != "/") {

            return (($parentFolder != "") ? $parentFolder . "/" : null) . $folder;
        } else {

            return "";
        }
    }

    /**
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @param string $logsDir
     * @param User $user
     * @param array $flashArray
     * @throws \Exception
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
     * @param ArchiveEntryEntity $entryEntity
     * @param User $user
     * @param array $messages
     * @throws \Exception
     */
    public function logEntryContent(ArchiveEntryEntity $entryEntity, User $user, array $messages)
    {
        $logsDir = $this->getLogsRootPath($entryEntity->getId());
        $this->logEntry($entryEntity, $logsDir, $user, $messages);
    }

    /**
     * @param Finder $finder
     * @return array
     */
    public function finderToArray(Finder $finder)
    {
        $array = [];
        foreach ($finder as $element) {
            $array[] = $element;
        }

        return $array;
    }

    /**
     * @param string $logsPath
     * @return array
     */
    public function getEntryLogFolders(string $logsPath)
    {
        $finder = new Finder();
        $finder->depth('== 0');
        $finder->directories()->in($logsPath);
        $finder->sortByName();
        $folders = $this->finderToArray($finder);

        return $folders;
    }

    /**
     * @param string $logsPath
     * @return array
     */
    public function getEntryLogFiles(string $logsPath)
    {
        $finder = new Finder();
        $finder->depth('== 0');
        $finder->files()->in($logsPath);
        $finder->sortByModifiedTime();
        $files = $this->finderToArray($finder);

        return $files;
    }

    /**
     * @param int $entryId
     * @param string $file
     * @param int $rowsCount
     * @return null|string[]
     */
    public function getFileContent(int $entryId, string $file, int $rowsCount)
    {
        $path = $this->getLogsRootPath($entryId);
        $file = $path . "/" . $file;

        if (filesize($file) > 0) {
            try {
                $tail = new Tail($file);
                $fileContent = $tail->cheat($rowsCount, null, false);
            } catch (TailException $tailException) {
                $fileContent[0] = 'Exception : ' . $tailException->getMessage();
            }

            return $fileContent;
        } else {

            return null;
        }
    }

    /**
     * @param string $newPath
     * @param string $oldName
     * @param string $newName
     */
    public function renameLogFile(string $newPath, string $oldName, string $newName)
    {
        $fs = new Filesystem();
        $logsDir = $newPath . '/logs/';
        $fs->rename($logsDir . $oldName . '.log', $logsDir . $newName . '.log');
    }
}
