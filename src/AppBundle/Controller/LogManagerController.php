<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogManagerController
 * @package AppBundle\Controller
 */
class LogManagerController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/logging/", name="logging")
     */
    public function logManagerIndex()
    {
        return $this->render('lencor/admin/archive/logging_manager/index.html.twig');
    }
}