<?php

namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdministrationController
 * @package AppBundle\Controller
 */
class AdministrationController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/admin/", name="admin")
     */
    public function adminIndex(Request $request)
    {
        return $this->render(':lencor/admin/archive/administration:index.html.twig');
    }
}