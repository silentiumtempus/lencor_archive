<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ArchiveViewController
 * @package AppBundle\Controller
 */
class FlashMessagesController extends Controller
{
    /**
     * @return Response
     * @Route("entries/flash_messages",
     *     options = { "expose" = true },
     *     name="flash_messages")
     */
    public function showFlashMessages()
    {

        return $this->render('lencor/admin/archive/flash_messages/archive_manager/flash_messages.html.twig');
    }

    /**
     * @return Response
     * @Route("entries/flash_messages_summary",
     *     options = { "expose" = true },
     *     name="flash_messages_summary")
     */
    public function showFlashMessagesSummary()
    {

        return $this->render('lencor/admin/archive/flash_messages/archive_manager/summary.html.twig');
    }
}