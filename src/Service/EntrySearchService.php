<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Elastica\Util;
use Psr\Container\ContainerInterface;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\Term;
use Elastica\Query;
use Symfony\Component\Form\Form;

/**
 * Class EntrySearchService
 * @package App\Service
 */

class EntrySearchService
{
    protected $em;
    protected $container;
    protected $elasticManager;
    protected $entriesRepository;
    protected $dSwitcherService;

    /**
     * EntrySearchService constructor
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param DeleteSwitcherService $dSwitcherService
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, DeleteSwitcherService $dSwitcherService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
        $this->elasticManager = $this->container->get('fos_elastica.finder.archive.archive_entries');
        $this->dSwitcherService = $dSwitcherService;
    }

    /**
     * @param Form $searchForm
     * @param BoolQuery $filterQuery
     * @return BoolQuery
     */

    public function performSearch(Form $searchForm, BoolQuery $filterQuery)
    {
        foreach ($searchForm->getIterator() as $key => $child) {
            if ($child->getData()) {
                if ($key == 'year') {
                    $conditionYear = (new Match())->setFieldQuery($child->getName(), $child->getViewData());
                    $filterQuery->addMust($conditionYear);
                } elseif ($key == 'factory') {
                    $conditionFactory = (new Term())->setTerm('factory.id', $child->getViewData());
                    $filterQuery->addMust($conditionFactory);
                } elseif ($key == 'setting') {
                    $conditionSetting = (new Term())->setTerm('setting.id', $child->getViewData());
                    $filterQuery->addMust($conditionSetting);
                } else {
                    $conditionString = new Query\QueryString();
                    $conditionString->setQuery('*' . Util::escapeTerm($child->getViewData()) . '*');
                    $conditionString->setDefaultOperator('AND');
                    $conditionString->setParam('fields', array($key));
                    $filterQuery->addMust($conditionString);
                }
            }
        }

        return $filterQuery;
    }

    /**
     * @param Query $finalQuery
     * @param BoolQuery $filterQuery
     * @param integer $limit
     * @param bool $switchDeleted
     * @return mixed
     */

    public function getQueryResult(Query $finalQuery, BoolQuery $filterQuery, int $limit, bool $switchDeleted)
    {
        $filterQuery = $this->showDeleted($filterQuery, $switchDeleted);
        $finalQuery->setQuery($filterQuery);
        $finalQuery->addSort(array('year' => array('order' => 'ASC')));

        return $this->elasticManager->find($finalQuery, $limit);
    }

    /**
     * @param BoolQuery $filterQuery
     * @param bool $switchDeleted
     * @return BoolQuery
     */

    public function showDeleted(BoolQuery $filterQuery, bool $switchDeleted)
    {
        $this->dSwitcherService->switchDeleted($switchDeleted);
        $conditionDeleted = (new Term())->setTerm('deleted', $switchDeleted);
        $filterQuery->addShould($conditionDeleted);
        if ($switchDeleted) {
            $conditionDeletedChildren = (new Query\Range('deleted_children', array('gt' => 0)));
            $filterQuery->addShould($conditionDeletedChildren);
        }
        $filterQuery->setMinimumShouldMatch(1);

        return $filterQuery;
    }
}
