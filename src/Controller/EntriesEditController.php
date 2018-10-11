<?php

namespace App\Controller;

use App\Form\EntryForm;
use App\Form\EntrySearchByIdForm;
use App\Service\EntryService;
use App\Service\FolderService;
use App\Service\LoggingService;
use App\Service\SerializerService;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EntriesEditController
 * @package App\Controller
 */
class EntriesEditController extends Controller
{
    /**
     * @param Request $request
     * @param EntryService $entryService
     * @param FolderService $folderService
     * @param LoggingService $loggingService
     * @param integer $entryId
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/entries/{entryId}",
     *     options = { "expose" = true },
     *     name = "admin-entries",
     *     requirements = { "$entryId" = "\d+" },
     *     defaults = { "entryId" : "" }))     *
     */

    //@ParamConverter("archiveEntryEntity", class="App:ArchiveEntryEntity", options = { "id" = "entryId" }, isOptional="true")
    public function entryEditIndex(Request $request, EntryService $entryService, FolderService $folderService, LoggingService $loggingService, $entryId)
    {
        $session = $this->container->get('session');
        $archiveEntryEntity = null;
        $entrySearchByIdForm = $this->createForm(EntrySearchByIdForm::class);
        if ($request->request->has('entry_search_by_id_form')) {
            $entrySearchByIdForm->handleRequest($request);
            if ($entrySearchByIdForm->isSubmitted() && $request->isMethod("POST")) {
                if ($entrySearchByIdForm->isValid()) {
                    $archiveEntryEntity = $entryService->getEntryById($entrySearchByIdForm->get('id')->getData());
                }
            }
        }
        if ($entryId) {
            $archiveEntryEntity = $entryService->getEntryById($entryId);
        }
        if ($archiveEntryEntity) {
            $entryForm = $this->createForm(
                EntryForm::class,
                $archiveEntryEntity,
                array('attr' => array('id' => 'entry_form', 'function' => 'edit'))
            );
            $entryForm->handleRequest($request);
            if ($entryForm->isSubmitted()) {
                if ($entryForm->isValid()) {
                    $originalEntry = $entryService->getOriginalData($archiveEntryEntity);
                    $matches = $entryService->checkPathChanges($originalEntry, $archiveEntryEntity);
                    if (count($matches) > 0) {
                        $this->addFlash('warning', ' Обнаружено изменение параметров расположения ячейки. Перестраивается структура каталога');
                        $pathIsFree = $entryService->checkNewPath($archiveEntryEntity, false);
                        if ($pathIsFree) {
                            $folderService->moveEntryFolder($originalEntry, $archiveEntryEntity);
                        } else {
                            $this->addFlash('danger', 'Директория назначения уже существует. Операция прервана.');
                            $loggingService->logEntryContent($archiveEntryEntity, $this->getUser(), $session->getFlashBag()->peekAll());

                            return new Response();
                        }
                    } else {
                        $entryFolder = $entryService->constructEntryPath($archiveEntryEntity, false);
                    }
                    if (isset($entryFolder)) {
                        try {
                            $entryService->updateEntry();
                            $entryService->updateEntryInfo($archiveEntryEntity, $this->getUser(), true);
                            $this->addFlash('success', 'Изменения параметров ячейки сохранены');
                            $loggingService->logEntryContent($archiveEntryEntity, $this->getUser(), $session->getFlashBag()->peekAll());

                            return new Response($archiveEntryEntity->getId());
                        } catch (\Exception $exception) {
                            $this->addFlash('danger', 'Изменения не сохранены. Ошибка: ' . $exception->getMessage());
                            $loggingService->logEntryContent($archiveEntryEntity, $this->getUser(), $session->getFlashBag()->peekAll());

                            return $this->render(
                                'lencor/admin/archive/administration/entries/entry_edit.html.twig',
                                array(
                                    'entryForm' => $entryForm->createView(),
                                    'entryId' => $archiveEntryEntity->getId())
                            );
                        }
                    } else {
                        $this->addFlash('danger', 'Указанный каталог уже существует. Операция прервана.');
                        $loggingService->logEntryContent($archiveEntryEntity, $this->getUser(), $session->getFlashBag()->peekAll());

                        return new Response();
                    }

                } else {
                    if (!$request->get('submit')) {
                        $this->addFlash('danger', 'Форма заполнена неверно. Архивная запись с такими ключевыми параметрами уже существует.');
                        $loggingService->logEntryContent($archiveEntryEntity, $this->getUser(), $session->getFlashBag()->peekAll());

                        return new Response();
                    }

                    return $this->render('lencor/admin/archive/administration/entries/entry_edit.html.twig',
                        array(
                            'entryForm' => $entryForm->createView(),
                            'entryId' => $archiveEntryEntity->getId()));
                }
            }
            if ($entryId) {

                return $this->render(
                    'lencor/admin/archive/administration/entries/entries.html.twig',
                    array(
                        'entrySearchByIdForm' => $entrySearchByIdForm->createView(),
                        'entryForm' => $entryForm->createView(),
                        'entryId' => $archiveEntryEntity->getId())
                );
            }
        } else {

            return $this->render(
                'lencor/admin/archive/administration/entries/entries.html.twig', array('entrySearchByIdForm' => $entrySearchByIdForm->createView()));
        }
        if ($entrySearchByIdForm->isSubmitted()) {

            return $this->render('lencor/admin/archive/administration/entries/entry_edit.html.twig',
                array(
                    'entryForm' => $entryForm->createView(),
                    'entryId' => $archiveEntryEntity->getId()));
        } else {

            return $this->render('lencor/admin/archive/administration/entries/entries.html.twig', array('entrySearchByIdForm' => $entrySearchByIdForm->createView()));
        }
    }
}

