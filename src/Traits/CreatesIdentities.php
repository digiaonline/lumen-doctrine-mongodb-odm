<?php

namespace Nord\Lumen\Doctrine\ODM\MongoDB\Traits;

use Closure;
use Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\DomainId;

/**
 * Class CreatesIdentities.
 *
 * @package Nord\Lumen\Doctrine\ODM\MongoDB\Traits
 */
trait CreatesIdentities
{

    /**
     * @param Closure $objectIdExists
     *
     * @return DomainId
     * @throws \Exception
     */
    private function createDomainId(Closure $objectIdExists)
    {
        $numTries = 0;

        do {
            $domainId = new DomainId();

            if ($numTries++ >= 10) {
                throw new \Exception('Failed to generate a unique identifier.');
            }
        } while (call_user_func($objectIdExists, $domainId->getValue()));

        return $domainId;
    }
}
