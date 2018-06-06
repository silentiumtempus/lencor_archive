<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * EntrySearchService constructor
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
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
                if ($key == 'year') {
                    $conditionFactory = (new Match())->setFieldQuery($child->getName(), $child->getViewData());
                    $filterQuery->addMust($conditionFactory);
                } elseif ($key == 'factory') {
                    $conditionFactory = (new Term())->setTerm('factory.id', $child->getViewData());
                    $filterQuery->addMust($conditionFactory);
                } elseif ($key == 'setting') {
                    $conditionSetting = (new Term())->setTerm('setting.id', $child->getViewData());
                    $filterQuery->addMust($conditionSetting);
                } else {
                    $conditionString = new Query\QueryString();
                    $conditionString->setQuery('*' . $child->getViewData() . '*');
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
     * @return mixed
     */
    public function getQueryResult(Query $finalQuery, BoolQuery $filterQuery, int $limit)
    {
        $finalQuery->setQuery($filterQuery);
        $finalQuery->addSort(array('year' => array('order' => 'ASC')));

        return $this->elasticManager->find($finalQuery, $limit);
    }

}
