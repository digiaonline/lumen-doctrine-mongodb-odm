<?php
namespace Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure\Types;

use Crisu83\ShortId\ShortId;
use Doctrine\ODM\MongoDB\Types\Type;
use Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\ShortId as ShortIdModel;

/**
 * ShortId implementation
 */
final class ShortIdType extends Type
{
    /**
     * @inheritdoc
     */
    public function convertToPHPValue($value)
    {
        return $value instanceof ShortId ? (string) $value : $value;
    }

    /**
     * @inheritdoc
     */
    public function closureToPHP()
    {
        return '$return = new \Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\ShortId($value);';
    }


    /**
     * @inheritdoc
     */
    public function convertToDatabaseValue($value)
    {
        return $value instanceof ShortIdModel ? $value->getValue() : $value;

    }


    public function closureToMongo()
    {
        return '$return = new \Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model\ShortId($value);';
    }
}
