<?php namespace Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model;

use Crisu83\ShortId\ShortId as Crisu83ShortId;

class ShortId
{

    /**
     * @var string
     */
    private $value;

    /**
     * @var \Crisu83\ShortId\ShortId
     */
    private static $identityGenerator;


    /**
     * ObjectId constructor.
     *
     * @param string $value
     */
    public function __construct($value = null)
    {
        $this->value = $value === null ? $this->nextIdentity() : $value;
    }


    /**
     * @return string
     */
    private static function nextIdentity()
    {
        if (self::$identityGenerator === null) {
            self::$identityGenerator = Crisu83ShortId::create();
        }

        return self::$identityGenerator->generate();
    }


    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
