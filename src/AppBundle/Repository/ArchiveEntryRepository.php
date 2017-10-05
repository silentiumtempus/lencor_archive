<?php

namespace AppBundle\Repository;


use Doctrine\ORM\EntityRepository;

class ArchiveEntryRepository extends EntityRepository
{
    public function getUpdateInfoByEntry(int $entryId)
    {
        return $this->createQueryBuilder('g')
            ->select('en.lastModified', 'us.usernameCanonical')
            ->from('AppBundle:ArchiveEntryEntity', 'en')
            ->leftJoin('AppBundle:User', 'us', 'with', 'en.modifiedByUserId = us.id')
            ->where('en.id = ' . $entryId)
            ->getQuery()
            ->getResult();
    }

    public function getUpdateInfoByFolder(int $entryId)
    {
        return $this->createQueryBuilder('g')
            ->select('en.lastModified', 'us.usernameCanonical')
            ->from('AppBundle:ArchiveEntryEntity', 'en')
            ->leftJoin('AppBundle:User', 'us', \Doctrine\ORM\Query\Expr\Join::WITH, 'en.modifiedByUserId = us.id')
            ->where('en.id IN (:archiveEntryId)')
            ->setParameter('archiveEntryId', $entryId)
            ->getQuery()
            ->getResult();
    }
}