<?php
declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * Class FolderRepository
 * @package App\Repository
 */
class FolderRepository extends NestedTreeRepository
{
    /**
     * @param EntityRepository $entityRepository
     * @param int $folderId
     * @return QueryBuilder
     */
    public function showEntryFoldersQuery(EntityRepository $entityRepository, int $folderId)
    {
        return $entityRepository->createQueryBuilder('parent')
            ->where('parent.root = :folderId', 'parent.removalMark = 0')
            ->setParameter(':folderId', $folderId)
            ->orderBy('parent.lft', 'ASC');
    }
}
