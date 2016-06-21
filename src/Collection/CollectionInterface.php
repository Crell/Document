<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentInterface;
use Crell\Document\Document\MutableDocumentInterface;


/**
 * A Collection represents a set of documents with similar characteristics.
 *
 * For some definition of similar that is largely open to interpretation.
 */
interface CollectionInterface {

    /**
     * Returns the name of this collection.
     *
     * @return string
     *   The name of this collection.
     */
    public function name() : string;

    /**
     * Returns the language this collection will use.
     *
     * @return string
     */
    public function language() : string;

    /**
     * Creates the schema for this collection if necessary.
     */
    public function initializeSchema();

    /**
     * Returns a new collection targeted at ths specified language.
     *
     * @param string $language
     *   The language for which we want a collection.
     *
     * @return CollectionInterface
     */
    public function forLanguage(string $language) : self;

    /**
     * Returns a new, empty document.
     *
     * @return Document
     *   A new document with just the appropriate IDs.
     */
    public function createDocument() : MutableDocumentInterface;

    /**
     * Retrieves a specified document from the collection.
     *
     * Specifically, the default revision will be returned for the language
     * of this collection.
     *
     * @param string $uuid
     *   The UUID of the Document to load.
     *
     * @return Document
     *   The corresponding document.
     */
    public function load(string $uuid) : DocumentInterface;

    /**
     * Retrieves a specified document, with special Mutable methods.
     *
     * Note that a mutable object will have a *new* revision ID already set,
     * so that it can later be serialized properly. If you need the original
     * revision ID, you should use load() instead.
     *
     * @param string $uuid
     *
     * @return Document
     */
    public function loadMutable(string $uuid) : MutableDocumentInterface;

    /**
     * Retrieves a specific revision of a specified Document.
     *
     * @todo Should this be language-sensitive?
     *
     * @param string $uuid
     *   The UUID of the Document to load.
     * @param string $revision
     *   The revision ID of the Document to load.
     *
     * @return Document
     *   The corresponding document.
     * @throws \Doctrine\DBAL\DBALException
     *
     * @throws \Exception
     */
    public function loadRevision(string $uuid, string $revision) : DocumentInterface;

    /**
     * Retrieves the latest revision of the specified Document.
     *
     * @param string $uuid
     *   The UUID of the Document to load.
     *
     * @return Document
     */
    public function loadLatestRevision(string $uuid) : DocumentInterface;

    /**
     * Creates a new revision of the specified Document.
     *
     * @todo Track parantage of entities.
     *
     * @todo We need to switch this to an explicitly mutable object, or a command,
     * or something.
     *
     * @param MutableDocumentInterface $document
     *   The document to be persisted.
     * @param bool $setDefault
     *   True if this should become the default revision of this Document in its
     *   language, False otherwise.
     *
     * @throws \Exception
     */
    public function save(
        MutableDocumentInterface $document,
        bool $setDefault = TRUE
    );
}
