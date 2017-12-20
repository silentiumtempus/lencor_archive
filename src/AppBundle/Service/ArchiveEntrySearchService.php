<?php

namespace AppBundle\Service;

use Psr\Container\ContainerInterface;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\Term;
use Elastica\Query;
use Symfony\Component\Form\Form;

/**
 * Class ArchiveEntrySearchService
 * @package AppBundle\Service
 */
class ArchiveEntrySearchService
{
    protected $container;
    protected $elasticManager;

    /**
     * ArchiveEntrySearchService constructor.
     * @param ContainerInterface $container
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->elasticManager = $this->container->get('fos_elastica.finder.lencor_archive.archive_entries');
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

        return $filterQuery;
    }

    /**
     * @param Query $finalQuery
     * @param BoolQuery $filterQuery
     * @return mixed
     */
    public function getQueryResult(Query $finalQuery, BoolQuery $filterQuery)
    {
        $finalQuery->setQuery($filterQuery);
        $finalQuery->addSort(array('year' => array('order' => 'ASC')));

        return $this->elasticManager->find($finalQuery, 5000);
    }
}