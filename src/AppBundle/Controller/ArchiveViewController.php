<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 021 21.02.17
 * Time: 19:41
 */

namespace AppBundle\Controller;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Form\ArchiveEntrySearchForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\Term;
use Elastica\Query;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArchiveViewController extends Controller
{

        /*set_include_path('/var/www/lencor/public_html/new/web/');
$file = 'test.txt';

$wr = file_get_contents($file);

$wr = $wr . $request->get('entryId') . "!!!!!!!!!!!!!!" . "\n\n";
//$wr = $wr . $newFolder>get('parentFolder')->getViewData() . "!!!!!!!!!!!!!!" . "\n\n";

file_put_contents($file, $wr); */

    /**
     * @param Request $request
     * @return Response
     * @Route("/welcome_index", name="lencor_welcome_index")
     */

    public function welcomeIndexAction(Request $request)
    {
        $entrySearchEntity = new ArchiveEntryEntity();
        $searchForm = $this->createForm(ArchiveEntrySearchForm::class, $entrySearchEntity);
        $elasticManager = $this->container->get('fos_elastica.finder.lencor_archive.archive_entries');
        $finalQuery = new Query();
        $filterQuery = new BoolQuery();
        $entryCatalogue = null;

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid() && $request->isMethod('POST')) {
            try {
                // if ($form->getData()) {

                foreach ($searchForm->getIterator() as $key => $child) {
                    if ($child->getData()) {
                        if ($key == 'factory') {
                            $conditionFactory = (new Term())->setTerm('factory.id', $child->getViewData());
                            $filterQuery->addMust($conditionFactory);
                        } else if ($key == 'setting') {
                            $conditionSetting = (new Term())->setTerm('setting.id', $child->getViewData());
                            $filterQuery->addMust($conditionSetting);
                        } else {
                            $filterMatchField = (new Match())->setFieldQuery($child->getName(), $child->getViewData());
                            $filterQuery->addMust($filterMatchField);
                        }
                    }
                }
                $finalQuery->setQuery($filterQuery);
                //}
            } catch (\Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }
        $finalQuery->addSort(array('year' => array('order' => 'ASC')));
        $archiveEntries = $elasticManager->find($finalQuery, 3000);
        $boolQuery = null;
        $finalQuery = null;

        return $this->render('lencor/admin/archive/index.html.twig', array('archiveEntries' => $archiveEntries, 'searchForm' => $searchForm->createView()));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries/view_folders", name="lencor_entries_view_folders")
     */

    function showEntryFolders(Request $request)
    {
        $entryId = null;
        $folderId = null;
        $fileNodes = null;
        $folderTree = null;
        $foldersRepository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $entryUpdatedData = null;
        $options = array();

        if ($request->request->has('folderId')) {
            $folderId = $request->get('folderId');
            $folderNode = $foldersRepository->findOneById($folderId);
            $folderTree = $foldersRepository->childrenHierarchy($folderNode, true, $options, false);
        }

        return $this->render('lencor/admin/archive/archive_manager_folder.html.twig', array('entryId' => $entryId, 'folderTree' => $folderTree));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries/view_files", name="lencor_entries_view_files")
     */

    function showEntryFiles(Request $request)
    {
        $folderId = null;
        $fileList = null;
        $filesRepository = $this->getDoctrine()->getRepository('AppBundle:FileEntity');
        $entryUpdatedData = null;

        if ($request->request->has('folderId')) {
            $folderId = $request->get('folderId');
            $fileList = $filesRepository->findByParentFolder($folderId);
        }

        return $this->render('lencor/admin/archive/archive_manager_file.html.twig', array('fileList' => $fileList));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries_view", name="lencor_entries_view")
     */

    function showEntryDetails(Request $request)
    {
        $entryId = null;
        $folderId = null;
        $foldersRepository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $addHeaderAndButtons = true;

        if ($request->request->has('entryId')) {
            $entryId = $request->get('entryId');
            $folderNode = $foldersRepository->findOneByArchiveEntry($entryId);
            $folderId = $folderNode->getId();

        }
        /** for file system handling **/
        /*$entryId = $request->get('entryId');
        $pathRoot = $this->getParameter('lencor_archive.storage_path');
        $targetEntry = $this->getDoctrine()->getRepository('AppBundle:ArchiveEntryEntity')->find($entryId);
        $entryCatalogue = $pathRoot . "/" . $targetEntry->getCataloguePath();

        $finder = new Finder();
        $filesystem = new Filesystem();
        $entryDirectories = $finder->directories()->in($entryCatalogue);
        $entryDirectories->sortByName();
        $entryDirectories = array_keys(iterator_to_array($entryDirectories));

        foreach ($entryDirectories as $key => $absolutePath) {
            $relativePath = $filesystem->makePathRelative($absolutePath, $entryCatalogue);
            unset($entryDirectories[$key]);
            $entryDirectories[$relativePath] = $relativePath;
        }

        $entryFiles = $finder->files()->in($entryCatalogue)->depth('== 0');
        $entryFiles->sortByName();
        $entryFiles = array_keys(iterator_to_array($entryFiles));


        foreach ($entryFiles as $key => $absolutePath) {
            $relativePath = $filesystem->makePathRelative($absolutePath, $entryCatalogue);
            $relativePath = rtrim($relativePath, "/");
            unset($entryFiles[$key]);
            $entryFiles[$absolutePath] = $relativePath; */
        //}
        //} catch (\Exception $exception) {
        //    $this->addFlash('error', $exception->getMessage());
        //}
        //}

        return $this->render('lencor/admin/archive/archive_manager_entries_view.html.twig', array('folderId' => $folderId, 'entryId' => $entryId, 'addHeaderAndButtons' => $addHeaderAndButtons));
    }

    /**
     * @return Response
     * @Route("/lencor_flash_messages", name="lencor_flash_messages")
     */
    public function showFlashMessages()
    {
        return $this->render('lencor/default/flash_messages.html.twig');
    }

}

