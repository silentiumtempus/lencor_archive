<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializerService
{
    protected $em;
    protected $container;
    protected $pathRoot;
    protected $internalFolder;
    protected $pathPermissions;
    protected $factoriesRepository;
    protected $settingsRepository;
    protected $commonArchiveService;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container, CommonArchiveService $archiveService)
    {
        $this->em = $em;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->internalFolder = $this->container->getParameter('archive.internal.folder_name');
        $this->pathPermissions = $this->container->getParameter('lencor_archive.storage_permissions');
        $this->commonArchiveService = $archiveService;
        $this->factoriesRepository = $this->em->getRepository('App:FactoryEntity');
    }

    /**
     * @return string
     */

    public function constructInternalFolderPath()
    {
        return $this->pathRoot . '/' . $this->internalFolder . '/';
    }

    /**
     * @return bool
     */

    public function serializeFactoriesAndSettings()
    {
        $fs = new Filesystem();
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $normalizer->setSerializer(new Serializer(array($normalizer), array($encoder)));
        $normalizer->setIgnoredAttributes(array('settings' => 'id'));
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->__toString();
        });
        $internalPath = $this->constructInternalFolderPath();
        if (!$fs->exists($internalPath)) {
            $fs->mkdir($internalPath, $this->pathPermissions);
        }
        $internalFactoriesFile = $internalPath . "factories_and_settings";
        $factories = $this->factoriesRepository->findAll();
        if ($factories)
        {
            $factoriesArray = '';
            foreach ($factories as $factory)
            {
                $factory = $normalizer->normalize($factory);
                $factoriesArray .= json_encode($factory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
            $fs->dumpFile($internalFactoriesFile, $factoriesArray);
        }

        return true;
    }
}