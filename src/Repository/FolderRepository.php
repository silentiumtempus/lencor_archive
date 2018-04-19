<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

class FolderRepository extends NestedTreeRepository
{
    /**
     * @param EntityRepository $entityRepository
     * @param int $folderId
     * @return QueryBuilder
     */
    public function getEntryFoldersQuery(EntityRepository $entityRepository, int $folderId)
    {
        return $entityRepository->createQueryBuilder('parent')
            ->where('parent.root = :folderId', 'parent.deleteMark = 0')
            ->setParameter(':folderId', $folderId)
            ->orderBy('parent.lft', 'ASC');
    }
}
