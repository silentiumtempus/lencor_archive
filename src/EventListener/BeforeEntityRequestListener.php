<?php

namespace App\EventListener;


use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class BeforeRequestListener
 * @package App\EventListener
 */
class BeforeEntityRequestListener
{
    protected $em;

    /**
     * BeforeRequestListener constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $deletedFilter = $this->em
            ->getFilters()
            ->enable('deleted');
        $deletedFilter->setParameter('deleted', false);
    }
}