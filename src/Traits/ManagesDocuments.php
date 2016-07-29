<?php

namespace Nord\Lumen\Doctrine\ODM\MongoDB\Traits;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class ManagesDocuments.
 *
 * @package Nord\Lumen\Doctrine\ODM\MongoDB\Traits
 */
trait ManagesDocuments
{

    /**
     * @param mixed $document
     */
    private function saveDocument($document)
    {
        $this->getDocumentManager()->persist($document);
    }

    /**
     * @param mixed $document
     */
    private function saveDocumentAndCommit($document)
    {
        $this->saveDocument($document);
        $this->commitDocuments();
    }

    /**
     * @param mixed $document
     */
    private function updateDocument($document)
    {
        $this->getDocumentManager()->merge($document);
    }

    /**
     * @param mixed $document
     */
    private function updateDocumentAndCommit($document)
    {
        $this->updateDocument($document);
        $this->commitDocuments();
    }

    /**
     * @param mixed $document
     */
    private function deleteDocument($document)
    {
        $this->getDocumentManager()->remove($document);
    }

    /**
     * @param mixed $document
     */
    private function deleteDocumentAndCommit($document)
    {
        $this->deleteDocument($document);
        $this->commitDocuments();
    }

    /**
     *
     */
    private function commitDocuments()
    {
        $this->getDocumentManager()->flush();
    }

    /**
     * @param mixed $document
     */
    private function refreshDocument($document)
    {
        $this->getDocumentManager()->refresh($document);
    }

    /**
     * @param string $documentClassName
     *
     * @return DocumentRepository
     */
    private function getDocumentRepository($documentClassName)
    {
        return $this->getDocumentManager()->getRepository($documentClassName);
    }

    /**
     * @return DocumentManager
     */
    private function getDocumentManager()
    {
        return app(DocumentManager::class);
    }
}