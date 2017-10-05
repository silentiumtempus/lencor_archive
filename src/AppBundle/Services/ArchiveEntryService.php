<?php

namespace AppBundle\Services;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ArchiveEntryService
 * @package AppBundle\Services
 */
class ArchiveEntryService
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    public function changeLastUpdateInfo(string $entryId, User $user)
    {
        $entriesRepository = $this->em->getRepository('AppBundle:ArchiveEntryEntity');
        $archiveEntry = $entriesRepository->findOneById($entryId);
        $archiveEntry->setModifiedbyUserId($user->getId());
        $this->em->flush();
    }

    public function setEntryId(string $entryId, Request $request)
    {
        $session = $this->container->get('session');
        if ($entryId) {
            return $session->set('entryId', $request->get('entryId'));
        } elseif (!$entryId) {
            return $session->get('entryId');
        }


    }
}