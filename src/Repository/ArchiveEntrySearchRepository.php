<?php
declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;

class ArchiveEntrySearchRepository extends EntityRepository
{

    public function getSearchHintsByFormData()
    {

        return true;
    }
}
