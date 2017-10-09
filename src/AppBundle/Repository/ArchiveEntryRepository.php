<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ArchiveEntryRepository extends EntityRepository
{
    protected $em;
    protected $queryBuilder;

    /**
     * ArchiveEntryRepository constructor.
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->em = $em;
        $this->queryBuilder = $this->em->createQueryBuilder();
    }

    /**
     * @param int $entryId
     * @return array
     */
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

    /**
     * @param int $entryId
     * @return array
     */
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