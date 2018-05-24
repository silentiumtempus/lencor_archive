<?php

namespace App\Controller;

use App\Form\EntryForm;
use App\Form\EntrySearchByIdForm;
use App\Service\EntryService;
use App\Service\FolderService;
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
     * @param $entryId
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/entries/{entryId}",
     *     options = { "expose" = true },
     *     name = "admin-entries",
     *     requirements = { "$entryId" = "\d+" },
     *     defaults = { "entryId" : "0" }))
     */
    //@TODO: entryId is passing from GET as string
    public function entryEditIndex(Request $request, EntryService $entryService, FolderService $folderService, $entryId)
    {
        $updateStatus = false;
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
                    //try {
                    $originalEntry = $entryService->getOriginalData($archiveEntryEntity);
                    $matches = $entryService->checkPathChanges($originalEntry, $archiveEntryEntity);
                    //$this->addFlash('danger', 'Matches : ' . count($matches));
                    if (count($matches) > 0) {
                        $this->addFlash('warning', ' Обнаружено изменение параметров расположения ячейки. Перестраивается структура каталога');
                        $pathIsFree = $entryService->checkNewPath($archiveEntryEntity);
                        if ($pathIsFree) {
                            $newEntryPath = $folderService->checkAndCreateFolders($archiveEntryEntity, false);
                            $entryService->moveEntry($originalEntry, $newEntryPath);
                            $filename = $newEntryPath . "/" . $archiveEntryEntity->getArchiveNumber() . ".txt";
                            if ($entryService->writeDataToEntryFile($archiveEntryEntity, $filename)) {
                                $entryService->updateEntry();
                                $updateStatus = true;
                            }
                        } else {
                            $this->addFlash('danger', 'Указанный каталог уже существует. Операция прервана.');

                        }

                    }

                    // } catch (\Exception $exception) {
                    //      $this->addFlash('danger', 'Ошибка обновления ячейки: ' . $exception->getMessage());
                    //  }
                }
            }
            if (!$entryId || $updateStatus) {
                if ($updateStatus) {
                    $this->addFlash('success', 'Изменения сохранены');
                }

                return $this->render(
                    'lencor/admin/archive/administration/entry_edit.html.twig',
                    array(
                        'entryForm' => $entryForm->createView(),
                        'entryId' => $archiveEntryEntity->getId())
                );
            } else {
                return $this->render(
                    'lencor/admin/archive/administration/entries.html.twig',
                    array(
                        'entrySearchByIdForm' => $entrySearchByIdForm->createView(),
                        'entryForm' => $entryForm->createView(),
                        'entryId' => $archiveEntryEntity->getId())
                );
            }
        } else {
            if ($entrySearchByIdForm->isSubmitted()) {
                return $this->render('lencor/admin/archive/administration/entry_edit.html.twig');
            } else {
                return $this->render('lencor/admin/archive/administration/entries.html.twig',
                    array(
                        'entrySearchByIdForm' => $entrySearchByIdForm->createView()
                    ));
            }
        }
    }
}
