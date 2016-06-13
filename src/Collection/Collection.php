<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Crell\Document\Document\LoadableDocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Ramsey\Uuid\Uuid;

/**
 * A Collection represents a set of documents with similar characteristics.
 *
 *
 * For some definition of similar that is largely open to interpretation.
 */
class Collection
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $language;

    public function __construct(string $name, Connection $conn, $language = 'en')
    {
        $this->name = $name;
        $this->conn = $conn;
        $this->language = $language;
    }

    /**
     * Creates the schema for this collection if necessary.
     */
    public function initializeSchema()
    {
        $schemaManager = $this->conn->getSchemaManager();

        if (!$schemaManager->tablesExist($this->tableName())) {
            $table = new Table($this->tableName());
            $table->addColumn('revision', 'string', [
                'length' => 36,
            ]);
            $table->addColumn('uuid', 'string', [
                'length' => 36,
            ]);
            $table->addColumn('latest', 'boolean');
            // default_rev is named differently because "default" is a reserved word.
            $table->addColumn('default_rev', 'boolean');
            $table->addColumn('language', 'string', [
                'length' => 12,
            ]);

            $table->addColumn('document', 'json_array', [
                'length' => 16777215, // This size triggers a MEDIUMTEXT field on MySQL. Postgres will use native JSON.
            ]);
            $table->setPrimaryKey(['revision']);
            $table->addIndex(['uuid']);

            $schemaManager->createTable($table);
        }
    }

    /**
     * Returns a new collection targeted at ths specified language.
     *
     * @param string $language
     *   The language for which we want a collection.
     * @return Collection
     */
    public function forLanguage(string $language) : self
    {
        $new = clone $this;
        $new->language = $language;

        return $new;
    }

    /**
     *  Returns the name of the main table of this collection.
     *
     * @return string
     *   The name of the table.
     */
    protected function tableName() : string
    {
        return 'collection_'.$this->name;
    }

    /**
     * Returns a new, empty document.
     *
     * @todo If we want to move toward immutable objects, then what becomes of this?
     *
     * @return Document
     *   A new document with just the appropriate IDs.
     */
    public function createDocument() : MutableDocumentInterface
    {
        $uuid = Uuid::uuid4()->toString();
        $revision = Uuid::uuid4()->toString();

        $document = $this->createMutableDocument();

        // A newly created, unsaved revision has no Revision ID.
        $document->loadFrom([
            'uuid' => $uuid,
            'language' => $this->language,
            'revision' => $revision,
        ]);

        return $document;
    }

    protected function createMutableDocument() : MutableDocumentInterface
    {
        $document = new class extends Document implements MutableDocumentInterface {
            use MutableDocumentTrait;
            use LoadableDocumentTrait;
        };
        return $document;
    }

    protected function createLoadableDocument() : Document
    {
        $document = new class extends Document {
            use LoadableDocumentTrait;
        };
        return $document;
    }

    /**
     * Retrieves a specified document from the collection.
     *
     * Specifically, the default revision will be returned for the language
     * of this collection.
     *
     * @param string $uuid
     *   The UUID of the Document to load.
     * @return Document
     *   The corresponding document.
     */
    public function load(string $uuid) : Document
    {
        $data = $this->loadData($uuid);
        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

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
    public function loadMutable(string $uuid) : MutableDocumentInterface
    {
        $revision = Uuid::uuid4()->toString();

        $data = $this->loadData($uuid);
        $document = $this->createMutableDocument()->loadFrom($data);
        $document->setRevisionId($revision);

        return $document;
    }

    /**
     *
     *
     * @param $uuid
     *   The UUID to load.
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     *
     * @todo Catch and translate the exception.
     */
    protected function loadData($uuid) : array
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT document FROM ' . $this->tableName() . ' WHERE uuid = :uuid AND default_rev = :default AND language = :language', [
            ':uuid' => $uuid,
            ':default' => 1,
            ':language' => $this->language,
        ]);

        $data = json_decode($statement->fetchColumn(), true);

        return $data;
    }

    /**
     * Retrieves a specific revision of a specified document.
     *
     * @todo Should this be language-sensitive?
     *
     * @param string $uuid
     *   The UUID of the Document to load.
     * @param string $revision
     *   The revision ID of the Document to load.
     * @return Document
     *   The corresponding document.
     * @throws \Doctrine\DBAL\DBALException
     *
     * @throws \Exception
     *
     * @todo Catch and translate the Doctrine exception.
     */
    public function loadRevision(string $uuid, string $revision) : Document
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT document FROM ' . $this->tableName() . ' WHERE uuid = :uuid AND revision = :revision', [
            ':uuid' => $uuid,
            ':revision' => $revision,
        ]);

        $json = $statement->fetchColumn();
        if ($json === false) {
            // @todo Figure out what to do with this.
            throw new \Exception();
        }
        $data = json_decode($json, true);

        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

    protected function decodeDocument(array $data) : Document
    {
        return new Document($data['uuid'], $data['revision'], $data['language']);
    }

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
     * @throws \Exception
     */
    public function save(MutableDocumentInterface $document, bool $setDefault = true)
    {
        $this->conn->transactional(function (Connection $conn) use ($document, $setDefault) {

            $conn->insert($this->tableName(), [
                'uuid' => $document->uuid(),
                'revision' => $document->revision(),
                'latest' => true,
                'default_rev' => (int)$setDefault,
                'language' => $document->language(),
                'document' => json_encode($document),
            ]);

            // Set all revisions of this Document of the same language to not be
            // the latest, except the one we just saved as the latest.
            $conn->executeUpdate('UPDATE '.$this->tableName().' SET latest = :latest WHERE uuid = :uuid AND language = :language AND NOT revision = :revision ', [
                ':latest' => 0,
                ':uuid' => $document->uuid(),
                ':language' => $document->language(),
                ':revision' => $document->revision(),
            ]);

            if ($setDefault) {
                // If the Document we just saved was flagged as the default, set
                // all other revisions to not be the default (for the same document
                // and language).
                $conn->executeUpdate('UPDATE '.$this->tableName().' SET default_rev = :default WHERE uuid = :uuid AND language = :language AND NOT revision = :revision ', [
                    ':default' => 0,
                    ':uuid' => $document->uuid(),
                    ':language' => $document->language(),
                    ':revision' => $document->revision(),
                ]);
            }
        });
    }
}
