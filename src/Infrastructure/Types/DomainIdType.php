<?php

namespace Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\DomainId;

/**
 * Class DomainIdType.
 *
 * @package Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure\Types
 */
class DomainIdType extends Type
{

    /**
     * @inheritdoc
     */
    public function convertToDatabaseValue($value)
    {
        return $value instanceof DomainId ? $value->getValue() : $value;
    }

    /**
     * @inheritdoc
     */
    public function convertToPHPValue($value)
    {
        return new DomainId($value);
    }

    /**
     * @inheritdoc
     */
    public function closureToMongo()
    {
        return '$return = new \Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\DomainId($value);';
    }

    /**
     * @inheritdoc
     */
    public function closureToPHP()
    {
        return '$return = new \Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\DomainId($value);';
    }

}