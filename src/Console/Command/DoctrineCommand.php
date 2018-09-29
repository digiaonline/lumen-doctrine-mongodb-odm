<?php
namespace Nord\Lumen\Doctrine\ODM\MongoDB\Console\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Command\Command;

class DoctrineCommand extends Command
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
    }

    /**
     * @return SchemaManager
     */
    protected function getSchemaManager()
    {
        return $this->getDocumentManager()->getSchemaManager();
    }

    /**
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->documentManager;
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->getDocumentManager()->getMetadataFactory();
    }
}
