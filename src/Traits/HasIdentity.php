<?php

namespace Nord\Lumen\Doctrine\ODM\MongoDB\Traits;

use Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\DomainId;

/**
 * Class HasIdentity.
 *
 * @package Nord\Lumen\Doctrine\ODM\MongoDB\Traits
 */
trait HasIdentity
{
    /**
     * @var DomainId
     */
    private $domainId;

    /**
     * @param DomainId $domainId
     *
     * @return bool
     */
    public function compareDomainId(DomainId $domainId)
    {
        return $this->domainId->getValue() === $domainId->getValue();
    }

    /**
     * @return DomainId
     */
    public function getDomainId()
    {
        return $this->domainId;
    }

    /**
     * @return string
     */
    public function getDomainIdValue()
    {
        return $this->getDomainId()->getValue();
    }

    /**
     * @param null|string $value
     *
     * @throws \Exception
     */
    private function createDomainId($value = null)
    {
        $this->setDomainId(new DomainId($value));
    }

    /**
     * @param DomainId $domainId
     *
     * @throws \Exception
     */
    private function setDomainId(DomainId $domainId)
    {
        if ($this->domainId !== null) {
            throw new \Exception('Domain ID cannot be changed.');
        }

        $this->domainId = $domainId;
    }
}
