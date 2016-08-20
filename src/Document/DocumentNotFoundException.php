<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


use Crell\Document\Collection\DocumentIdentifierExceptionTrait;

class DocumentNotFoundException extends \InvalidArgumentException implements DocumentException
{
    use DocumentIdentifierExceptionTrait;

    /**
     * The UUID of the document that was missing.
     *
     * @var string
     */
    protected $uuid;

    /**
     * Returns the UUID of the missing document.
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Sets the UUID of the missing document
     *
     * @param string $uuid
     * @return self
     */
    public function setUuid(string $uuid) : self
    {
        $this->uuid = $uuid;
        return $this;
    }
}
