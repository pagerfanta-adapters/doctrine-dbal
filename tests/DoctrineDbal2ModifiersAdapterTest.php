<?php

/*
 * This file is part of the Pagerfanta Adapters project, Doctrine DBAL package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// TODO
//* Add tests specific to DoctrineDbal2ModifiersAdapter
//* Add tests with (stricly) monotonly ordered queries and show error
// with Postgres or maybe even Sqlite on original Pagerfanta

namespace PagerfantaAdapters\Doctrine\DBAL\Tests;

use Doctrine\DBAL\Query\QueryBuilder;
use DoctrineDbalUtil\DbalTestingUtil\DoctrineDbalTestCase;
use PagerfantaAdapters\Doctrine\DBAL\DoctrineDbal2ModifiersAdapter;

class DoctrineDbal2ModifiersAdapterTest extends DoctrineDbalTestCase
{
    public function testGetNbResults()
    {
        $adapter = $this->createAdapterToTestGetNbResults();

        $this->doTestGetNbResults($adapter);
    }

    public function testGetNbResultsShouldWorkAfterCallingGetSlice()
    {
        $adapter = $this->createAdapterToTestGetNbResults();

        $adapter->getSlice(1, 10);

        $this->doTestGetNbResults($adapter);
    }

    private function doTestGetNbResults(DoctrineDbal2ModifiersAdapter $adapter)
    {
        $this->assertSame(50, $adapter->getNbResults());
    }

    public function testGetSlice()
    {
        $adapter = $this->createAdapterToTestGetSlice();

        $this->doTestGetSlice($adapter);
    }

    public function testGetSliceShouldWorkAfterCallingGetNbResults()
    {
        $adapter = $this->createAdapterToTestGetSlice();

        $adapter->getNbResults();

        $this->doTestGetSlice($adapter);
    }

    private function createAdapterToTestGetSlice()
    {
        $QueryBuilderModifier = function () {
        };

        return new DoctrineDbal2ModifiersAdapter($this->qb, $QueryBuilderModifier, $QueryBuilderModifier);
    }

    private function doTestGetSlice(DoctrineDbal2ModifiersAdapter $adapter)
    {
        $offset = 30;
        $length = 10;

        $qb = clone $this->qb;
        $qb->setFirstResult($offset)->setMaxResults($length);

        $expectedResults = $qb->execute()->fetchAll();
        $results = $adapter->getSlice($offset, $length);

        $this->assertSame($expectedResults, $results);
    }

    /**
     * @expectedException \Pagerfanta\Exception\InvalidArgumentException
     */
    public function testItShouldThrowAnInvalidArgumentExceptionIfTheQueryIsNotSelect()
    {
        $this->qb->delete('posts');
        $QueryModifier = function () {
        };

        new DoctrineDbal2ModifiersAdapter($this->qb, $QueryModifier, $QueryModifier);
    }

    public function testItShouldCloneTheQuery()
    {
        $adapter = $this->createAdapterToTestGetNbResults();

        $this->qb->innerJoin('p', 'comments', 'c', 'c.post_id = p.id')
                ->groupBy('c.post_id');

        $this->assertSame(50, $adapter->getNbResults());
    }

    /**
     * @expectedException \Pagerfanta\Exception\InvalidArgumentException
     */
    public function testItShouldThrowAnInvalidArgumentExceptionIfTheCountQueryBuilderModifierIsNotACallable()
    {
        $finishQueryBuilderModifier = function (QueryBuilder $queryBuilder) {
            // $queryBuilder->orderBy($orderby, 'ASC');
        };

        $countQueryBuilderModifier = 'ups';

        new DoctrineDbal2ModifiersAdapter($this->qb, $finishQueryBuilderModifier, $countQueryBuilderModifier);
    }

    private function createAdapterToTestGetNbResults()
    {
        $finishQueryBuilderModifier = function (QueryBuilder $queryBuilder) {
            // $queryBuilder->orderBy($orderby, 'ASC');
        };

        $countQueryBuilderModifier = function (QueryBuilder $queryBuilder) {
            $queryBuilder->select('COUNT(DISTINCT p.id) AS total_results')
                         ->setMaxResults(1);
        };

        return new DoctrineDbal2ModifiersAdapter($this->qb, $finishQueryBuilderModifier, $countQueryBuilderModifier);
    }
}
