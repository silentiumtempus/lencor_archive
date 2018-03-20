<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SystemManagerController
 * @package App\Controller
 */
class SystemManagerController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/system/", name = "system")
     */
    public function statsIndex(Request $request)
    {
        return $this->render('lencor/admin/archive/system_manager/index.html.twig');
    }
}