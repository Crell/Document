<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * Default (well, only) implementation of MutableDocumentInterface.
 *
 * @see MutableDocumentInterface
 */
trait MutableDocumentTrait {

    public function setTitle(string $title) : MutableDocumentInterface
    {
        $this->title = $title;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRevisionId(string $revision) : MutableDocumentInterface
    {
        $this->revision = $revision;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimestamp(\DateTimeImmutable $timestamp) : MutableDocumentInterface
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}
