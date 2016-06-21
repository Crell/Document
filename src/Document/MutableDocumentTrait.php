<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


trait MutableDocumentTrait {

    /**
     *
     * @param string $revision
     *
     * @return self
     *   The modified object.
     */
    public function setRevisionId(string $revision) : MutableDocumentInterface
    {
        $this->revision = $revision;
        return $this;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp) : MutableDocumentInterface
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}
