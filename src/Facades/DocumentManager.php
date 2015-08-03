<?php namespace Nord\Lumen\Doctrine\ODM\MongoDB\Facades;

use Illuminate\Support\Facades\Facade;

class DocumentManager extends Facade
{

    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return 'Doctrine\ODM\MongoDB\DocumentManager';
    }
}
