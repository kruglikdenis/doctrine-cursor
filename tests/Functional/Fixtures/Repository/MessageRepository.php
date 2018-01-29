<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional\Fixtures\Repository;

use Doctrine\ORM\EntityRepository;
use Mnk\Cursor\CursorInterface;
use Mnk\Doctrine\DoctrineQueryCursor;
use Mnk\Tests\Functional\Fixtures\Entity\Message;
use Mnk\Tests\Functional\Fixtures\Entity\Topic;

/**
 *
 */
class MessageRepository extends EntityRepository
{
    /**
     * @param Topic $topic
     * @return CursorInterface|Message[]
     */
    public function findByTopic(Topic $topic): CursorInterface
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $queryBuilder->where('m.topic = :topic');
        $queryBuilder->setParameter('topic', $topic);
        $queryBuilder->orderBy('m.createdAt', 'ASC');
        $queryBuilder->addOrderBy('m.id', 'ASC');

        return DoctrineQueryCursor::fromQueryBuilder($queryBuilder);
    }
}