<?php

namespace Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure;

use \Doctrine\ODM\MongoDB\DocumentRepository as BaseRepository;

/**
 * Class DocumentRepository.
 *
 * @package Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure
 */
class DocumentRepository extends BaseRepository
{

    /**
     * @param string $domainId
     *
     * @return object|null
     */
    public function findByDomainId($domainId)
    {
        return $this->findOneBy(['domainId' => $domainId]);
    }

    /**
     * @param string $domainId
     *
     * @return int
     */
    public function domainIdExists($domainId)
    {
        return (int)$this->createQueryBuilder()
                         ->field('domainId')
                         ->equals($domainId)
                         ->getQuery()
                         ->count();
    }

}