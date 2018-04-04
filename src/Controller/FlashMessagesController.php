<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ArchiveViewController
 * @package App\Controller
 */
class FlashMessagesController extends Controller
{
    /**
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/flash_messages",
     *     options = { "expose" = true },
     *     name = "flash_messages")
     */
    public function showFlashMessages()
    {
        return $this->render('lencor/admin/archive/flash_messages/archive_manager/flash_messages.html.twig');
    }

    /**
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/flash_messages_summary",
     *     options = { "expose" = true },
     *     name = "flash_messages_summary")
     */
    public function showFlashMessagesSummary()
    {
        return $this->render('lencor/admin/archive/flash_messages/archive_manager/summary.html.twig');
    }

    /**
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/flash_messages_clear",
     *     options = { "expose" = true },
     *     name = "flash_messages_clear")
     */
    public function clearFlashMessages()
    {
        $session = $this->container->get('session');
        $session->getFlashBag()->clear();

        return new Response();
    }
}
