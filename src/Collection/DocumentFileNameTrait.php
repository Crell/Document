<?php

declare(strict_types = 1);

namespace Crell\Document\Collection;


use Crell\Document\Document\DocumentInterface;

/**
 * Utility trait for document => filename translation
 *
 * @todo This is putting Git-specific implementation details, namely that the filename is composed of
 * the uuid and the language, in the Collection object. That seems wrong. I am not sure of the solution.
 */
trait DocumentFileNameTrait
{
    /**
     * Returns the file name that should be used to store a document.
     *
     * @param DocumentInterface $document
     *   The document for which to get a filename.
     * @return string
     */
    protected function documentFileName(DocumentInterface $document) : string
    {
        return implode('/', [$document->language(), $document->uuid()]);
    }

    /**
     * Returns the file name that will look up a document.
     *
     * @param string $uuid
     *   The UUID of the document.
     * @param string $language
     *   The language the document is in.
     * @return string
     */
    protected function documentFileNameFromIds(string $uuid, string $language) : string
    {
        return implode('/', [$language, $uuid]);
    }

}
