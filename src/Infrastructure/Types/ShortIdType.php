<?php

namespace Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\ShortId;

/**
 * Class DomainIdType.
 *
 * @package Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure\Types
 */
class ShortIdType extends Type
{

    /**
     * @inheritdoc
     */
    public function convertToDatabaseValue($value)
    {
        return $value instanceof ShortId ? $value->getValue() : $value;
    }

    /**
     * @inheritdoc
     */
    public function convertToPHPValue($value)
    {
        return new ShortId($value);
    }

    /**
     * @inheritdoc
     */
    public function closureToMongo()
    {
        return '$return = new \Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\ShortId($value);';
    }

    /**
     * @inheritdoc
     */
    public function closureToPHP()
    {
        return '$return = new \Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\ShortId($value);';
    }
}
