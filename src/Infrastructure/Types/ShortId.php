<?php
namespace Nord\Lumen\Doctrine\ODM\MongoDB\Infrastructure\Types;

use Doctrine\ODM\MongoDB\Types\Type;

/**
 * My custom datatype.
 */
final class ShortId extends Type
{
    public function convertToPHPValue($value)
    {
        // Note: this function is only called when your custom type is used
        // as an identifier. For other cases, closureToPHP() will be called.
        return new \DateTime('@' . $value->sec);
    }

    public function closureToPHP()
    {
        // Return the string body of a PHP closure that will receive $value
        // and store the result of a conversion in a $return variable
        return '$return = new \DateTime($value);';
    }

    public function convertToDatabaseValue($value)
    {
        // This is called to convert a PHP value to its Mongo equivalent
        return new \MongoDate($value);
    }
}
