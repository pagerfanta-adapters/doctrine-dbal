<?php

namespace PagerfantaAdapters\Doctrine\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * @author Jean-Bernard Addor <jean-bernard.addor@umontreal.ca>
 * @author Michael Williams <michael@whizdevelopment.com>
 * @author Pablo Díez <pablodip@gmail.com>
 */
class TwoModifiers implements AdapterInterface
{
    private $queryBuilder;
    private $finishQueryBuilderModifier;
    private $countQueryBuilderModifier;
    /**
     * Constructor.
     *
     * @param QueryBuilder $queryBuilder              A DBAL query builder.
     * @param callable     $countQueryBuilderModifier A callable to modifier the query builder to count.
     */
    public function __construct(QueryBuilder $queryBuilder, $finishQueryBuilderModifier, $countQueryBuilderModifier)
    {
        if ($queryBuilder->getType() !== QueryBuilder::SELECT) {
            throw new InvalidArgumentException('Only SELECT queries can be paginated.');
        }
        if (!is_callable($finishQueryBuilderModifier)) {
            throw new InvalidArgumentException('The finish query builder modifier must be a callable.');
        }
        if (!is_callable($countQueryBuilderModifier)) {
            throw new InvalidArgumentException('The count query builder modifier must be a callable.');
        }
        $this->queryBuilder = clone $queryBuilder;
        $this->finishQueryBuilderModifier = $finishQueryBuilderModifier;
        $this->countQueryBuilderModifier = $countQueryBuilderModifier;
    }
    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        $qb = $this->prepareCountQueryBuilder();
        $result = $qb->execute()->fetchColumn();
        return (int) $result;
    }
    private function prepareCountQueryBuilder()
    {
        $qb = clone $this->queryBuilder;
        call_user_func($this->countQueryBuilderModifier, $qb);
        return $qb;
    }
    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $qb = clone $this->queryBuilder;
        call_user_func($this->finishQueryBuilderModifier, $qb);
        $result = $qb->setMaxResults($length)
            ->setFirstResult($offset)
            ->execute();
        return $result->fetchAll();
    }
}
