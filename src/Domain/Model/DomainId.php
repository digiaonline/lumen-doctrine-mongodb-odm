<?php

namespace Nord\Lumen\Doctrine\ODM\MongoDB\Domain\Model;

use Crisu83\ShortId\ShortId;

/**
 * Class DomainId.
 *
 * @package Nord\Lumen\Core\Domain
 */
class DomainId
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
     * DomainId constructor.
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
            self::$identityGenerator = ShortId::create();
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

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }
}
