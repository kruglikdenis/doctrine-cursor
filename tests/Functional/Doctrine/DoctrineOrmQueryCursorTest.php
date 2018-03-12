<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional\Doctrine;

use Doctrine\ORM\Query;
use Mnk\Doctrine\DoctrineOrmQueryCursor;
use Mnk\Tests\Functional\BaseDoctrineTestCase;
use Mnk\Tests\Functional\Fixtures\Entity\Message;
use Mnk\Tests\Functional\Fixtures\Entity\Topic;

/**
 * Functional tests for @see DoctrineOrmQueryCursor
 */
class DoctrineOrmQueryCursorTest extends BaseDoctrineTestCase
{
    public function testCustomCountQuery()
    {
        $itemsQuery = new Query($this->entityManager);
        $itemsQuery->setDQL('SELECT m FROM '.Message::class.' m');

        $countQuery = new Query($this->entityManager);
        // Lets use weird way to get count of messages in table
        $countQuery->setDQL('SELECT MAX(m.id) FROM '.Message::class.' m');

        $cursor = new DoctrineOrmQueryCursor($itemsQuery, $countQuery);
        static::assertCount(34, $cursor, 'Incorrect result of custom count query');
    }

    public function testZeroLimit()
    {
        $this->sqlLogger->enabled = true;

        $itemsQuery = new Query($this->entityManager);
        $itemsQuery->setDQL('SELECT m FROM '.Message::class.' m');

        $countQuery = new Query($this->entityManager);
        $countQuery->setDQL('SELECT COUNT(1) FROM '.Message::class.' m');

        $cursor = new DoctrineOrmQueryCursor($itemsQuery, $countQuery);
        $cursor->setLimit(0);

        $this->assertCursor([], $cursor);
        $this->assertLoggedSqls([], 'No queries should be executed when limit is 0');
    }

    public function testDistinctCount()
    {
        $this->sqlLogger->enabled = true;

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Message::class, 'm')
            ->select('m')
            ->where('m.topic = :topic')
            ->setParameter('topic', 1)
            ->orderBy('m.id', 'DESC');

        $cursor = DoctrineOrmQueryCursor::fromQueryBuilder($queryBuilder, null, true);

        static::assertCount(20, $cursor, 'Incorrect result of count query');

        $expected = [
            'SELECT COUNT(DISTINCT m0_.id) AS sclr_0 FROM message m0_ WHERE m0_.topic_id = ?'
        ];

        $this->assertLoggedSqls($expected);
    }

    public function testDistinctQuery()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Topic::class, 't')
            ->select('t')
            ->join('t.messages', 'm')
            ->where('m IN(:mids)')
            ->setParameter('mids', [10, 15, 20, 30])
            ->orderBy('t.id', 'DESC');

        $cursor = DoctrineOrmQueryCursor::fromQueryBuilder($queryBuilder);

        $this->assertCursor([2, 1], $cursor);
        static::assertCount(4, $cursor, 'Count should be 4 because joined messages table matched 4 times');

        $distinctCursor = DoctrineOrmQueryCursor::fromQueryBuilder($queryBuilder, null, true);
        static::assertCount(2, $distinctCursor, 'With distinct flag count should return 2 - number of distinct topics');
    }
}