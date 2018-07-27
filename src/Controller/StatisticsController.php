<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StatsController
 * @package App\Controller
 */

class StatisticsController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/stats/",
     *     options = { "expose" = true },
     *     name = "stats")
     */

    public function statsIndex(Request $request)
    {
        return $this->render('lencor/admin/archive/statistics/index.html.twig');
    }
}
