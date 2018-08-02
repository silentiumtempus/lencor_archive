<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DeleteService
 * @package App\Service
 */

class DeleteService
{
    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }


}