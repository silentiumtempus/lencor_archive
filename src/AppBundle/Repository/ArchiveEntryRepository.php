<?php

namespace AppBundle\Repository;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class ArchiveEntryRepository extends EntityRepository
{
    protected $em;
    protected $queryBuilder;

    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->em = $em;
        $this->queryBuilder = $this->em->createQueryBuilder();
    }

    public function getUpdateInfoByEntry(int $entryId)
    {
        return $this->queryBuilder
            ->select('en.lastModified', 'us.usernameCanonical')
            ->from('AppBundle:ArchiveEntryEntity', 'en')
            ->leftJoin('AppBundle:User', 'us', 'with', 'en.modifiedByUserId = us.id')
            ->where('en.id = ' . $entryId)
            ->getQuery()
            ->getResult();
    }

    public function getUpdateInfoByFolder(int $entryId)
    {
        return $this->queryBuilder
            ->select('en.lastModified', 'us.usernameCanonical')
            ->from('AppBundle:ArchiveEntryEntity', 'en')
            ->leftJoin('AppBundle:User', 'us', \Doctrine\ORM\Query\Expr\Join::WITH, 'en.modifiedByUserId = us.id')
            ->where('en.id IN (:archiveEntryId)')
            ->setParameter('archiveEntryId', $entryId)
            ->getQuery()
            ->getResult();
    }
}