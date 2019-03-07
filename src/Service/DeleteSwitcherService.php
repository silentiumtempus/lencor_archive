<?php
declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DeleteSwitcherService
 * @package App\Service
 */

class DeleteSwitcherService
{
    private $em;

    /**
     * DeleteSwitcherService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param bool $switchDeleted
     */
    public function switchDeleted($switchDeleted)
    {
        if (!$this->em->getFilters()->isEnabled('deleted')) {
            $deletedFilter = $this->em->getFilters()->enable('deleted');
        } else {
            $deletedFilter = $this->em->getFilters()->getFilter('deleted');
        }
        if (!is_null($switchDeleted)) {
            $deletedFilter->setParameter('deleted', $switchDeleted);
        } else {
            if ($this->em->getFilters()->isEnabled('deleted')) {
                $this->em->getFilters()->disable('deleted');
            }
        }
    }
}
