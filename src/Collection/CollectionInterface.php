<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentInterface;
use Crell\Document\Document\DocumentSetInterface;
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
     * @param bool $includeArchived
     *   True to return the document even if it is archived in its current
     *  revision. False otherwise.
     *
     * @return Document
     *   The corresponding document.
     */
    public function load(string $uuid, bool $includeArchived = false) : DocumentInterface;

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
    public function newRevision(string $uuid, string $parentRevision = null) : MutableDocumentInterface;

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
     * Retrieves a set of documents with the specified UUIDs.
     *
     * Note: Documents will be in the same order as the provided array.
     *
     * @param string[] $uuids
     *   An array of UUIDs of documents to load.
     * @param bool $includeArchived
     *   True to return the document even if it is archived in its current
     *  revision. False otherwise.
     *
     * @return DocumentSetInterface
     *   A document set containing the specified documents. Note: If any of the
     *   specified documents are not found, they will simply be omitted.
     */
    public function loadMultiple(array $uuids, bool $includeArchived = false) : DocumentSetInterface;

    /**
     * Sets a specified revision of a document as the default.
     *
     * @param string $uuid
     *   The UUID of the document to change.
     * @param string $language.
     *   The language context within which to work.
     * @param string $revision
     *   The revision ID of the revision to make the default.
     * @return self
     *   The invoked object.
     *
     * @todo Add a throw when the revision ID doesn't exist.
     */
    public function setDefaultRevision(string $uuid, string $language, string $revision) : self;

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
    public function save(MutableDocumentInterface $document, bool $setDefault = TRUE);

    /**
     * Marks the current default revision of a document archived.
     *
     * Archived documents will not appear in load() or loadMultiple() calls
     * by default.
     *
     * Note: This MAY get subsumed into moderation rules later rather than something
     * that can be set directly.  TBD.
     *
     * @param DocumentInterface $document
     *   The document to archive.
     */
    public function archive(DocumentInterface $document);

    /**
     * Returns the commit ID the collection points to.
     *
     * @return string
     */
    public function commit() : string;

    /**
     * Returns a new Collection instance pointing to the specified commit.
     *
     * @param string $commit
     * @return static
     */
    public function atCommit(string $commit) : self;

    /**
     * Retuns a new Collection instance pointing to the specified branch.
     *
     * @param string $name
     * @return static
     */
    public function atBranch(string $name) : CollectionInterface;

    /**
     * Returns a new Commit command object.
     *
     * @param string $message
     * @param string $author
     * @return Commit
     */
    public function createCommit(string $message = '', string $author = '') : Commit;

    /**
     * Saves a commit object atomically.
     *
     * @param Commit $commit
     *   The commit object to persist.
     * @param bool $setDefault
     *   True if all documents in the commit should be set as the default version,
     *   false otherwise.
     *
     * @return CollectionInterface
     *   The called object.
     */
    public function saveCommit(Commit $commit, bool $setDefault = true) : self;

}
