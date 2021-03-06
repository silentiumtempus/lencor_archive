<?php
declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class ArchiveEntryRepository
 * @package App\Repository
 */
class ArchiveEntryRepository extends EntityRepository
{
    protected $em;
    protected $queryBuilder;
    protected $class;

    /**
     * ArchiveEntryRepository constructor.
     * @param EntityManager $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->em = $em;
        $this->queryBuilder = $this->em->createQueryBuilder();
    }

    /**
     * @param int $entryId
     * @return array
     */
    public function getUpdateInfoByEntry(string $entryId)
    {
        return $this->queryBuilder
            ->select('en.lastModified', 'us.usernameCanonical')
            ->from('App:ArchiveEntryEntity', 'en')
            ->leftJoin(
                'App:User',
                'us',
                Join::WITH,
                'en.modifiedByUser = us.id'
            )
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
            ->from('App:ArchiveEntryEntity', 'en')
            ->leftJoin(
                'App:User',
                'us',
                Join::WITH,
                'en.modifiedByUser = us.id'
            )
            ->where('en.id IN (:archiveEntryId)')
            ->setParameter('archiveEntryId', $entryId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createEntriesAndErrorsQueryBuilder()
    {
        return $this->queryBuilder
            ->select('o')
            ->from('App:ArchiveEntryEntity', 'o')
            ->innerJoin(
                'App:FolderEntity',
                'folders',
                Join::WITH,
                'o.id = folders.archiveEntry'
            );
        //->innerJoin('App:FolderEntity', 'folders', Join::WITH, 'o.id = folders.archiveEntry');
            //->getQuery()
            //->getResult();
    }
}
