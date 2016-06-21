<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * Interface for the mutable variant of documents.
 *
 * Note: This is for internal use only, and should almost never be used by
 * user-space code.
 */
interface MutableDocumentInterface extends DocumentInterface {

    /**
     * Sets the revision ID of the document.
     *
     * @param string $revision
     *   The revision ID to set.
     * @return self
     */
    public function setRevisionId(string $revision) : self;

    /**
     * Sets the timestamp of the document.
     *
     * @param \DateTimeImmutable $timestamp
     *   The timestamp to set.
     *
     * @return self
     */
    public function setTimestamp(\DateTimeImmutable $timestamp) : self;

    /**
     * Sets whether or not this revision should be considered archived.
     *
     * @param bool $archived
     *   True if the revision should be flagged as archived, False otherwise.
     * @return self
     */
    public function setArchived(bool $archived) : self;
}
