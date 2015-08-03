<?php namespace Nord\Lumen\Doctrine\ODM\MongoDB\Console;

use Doctrine\ODM\MongoDB\DocumentManager;
use Illuminate\Console\Command;

abstract class DoctrineCommand extends Command
{

    /**
     * @var DocumentManager
     */
    private $documentManager;


    /**
     * DoctrineCommand constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
    }


    /**
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->documentManager;
    }
}
