<?php
namespace Nord\Lumen\Doctrine\ODM\MongoDB;

use Doctrine\Common\Persistence\ObjectManager;

interface DocumentManagerInterface extends ObjectManager
{
    /**
     * @return mixed
     */
    public function getDocumentManager();

    /**
     * @inheritdoc
     */
    public function persist($object);

    /**
     * @inheritdoc
     */
    public function flush();

}
