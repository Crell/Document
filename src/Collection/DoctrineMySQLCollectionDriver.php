<?php


namespace Crell\Document\Collection;


use Doctrine\DBAL\Connection;
use Crell\Document\Document\Document;
use Crell\Document\Document\LoadableDocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;
use Doctrine\DBAL\Schema\Table;
use Ramsey\Uuid\Uuid;

class DoctrineMySQLCollectionDriver implements CollectionDriverInterface
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeSchema(Collection $collection)
    {
        $schemaManager = $this->conn->getSchemaManager();

        $table = $this->tableName($collection->name());

        if (!$schemaManager->tablesExist($table)) {
            $table = new Table($table);
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
     *  Returns the name of the main table for a collection.
     *
     * @param string $collection
     *   The name of the collection
     * @return string
     *   The name of the table.
     */
    protected function tableName(string $collection) : string
    {
        return 'collection_' . $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function loadDefaultRevisionData(Collection $collection, string $uuid) : array
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT document FROM ' . $this->tableName($collection->name()) . ' WHERE uuid = :uuid AND default_rev = :default AND language = :language', [
            ':uuid' => $uuid,
            ':default' => 1,
            ':language' => $collection->language(),
        ]);

        $data = json_decode($statement->fetchColumn(), true);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function loadLatestRevisionData(Collection $collection, string $uuid) : array {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT document FROM ' . $this->tableName($collection->name()) . ' WHERE uuid = :uuid AND latest = :latest AND language = :language', [
            ':uuid' => $uuid,
            ':latest' => 1,
            ':language' => $collection->language(),
        ]);

        $data = json_decode($statement->fetchColumn(), true);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function loadRevisionData(Collection $collection, string $uuid, string $revision) : array
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT document FROM ' . $this->tableName($collection->name()) . ' WHERE uuid = :uuid AND revision = :revision', [
            ':uuid' => $uuid,
            ':revision' => $revision,
        ]);

        $json = $statement->fetchColumn();
        if ($json === false) {
            // @todo Figure out what to do with this.
            throw new \Exception();
        }
        $data = json_decode($json, true);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Collection $collection, MutableDocumentInterface $document, bool $setDefault)
    {
        $this->conn->transactional(function (Connection $conn) use ($collection, $document, $setDefault) {

            $table = $this->tableName($collection->name());

            $conn->insert($table, [
                'uuid' => $document->uuid(),
                'revision' => $document->revision(),
                'latest' => true,
                'default_rev' => (int)$setDefault,
                'language' => $document->language(),
                'document' => json_encode($document),
            ]);

            // Set all revisions of this Document of the same language to not be
            // the latest, except the one we just saved as the latest.
            $conn->executeUpdate('UPDATE '.$table.' SET latest = :latest WHERE uuid = :uuid AND language = :language AND NOT revision = :revision ', [
                ':latest' => 0,
                ':uuid' => $document->uuid(),
                ':language' => $document->language(),
                ':revision' => $document->revision(),
            ]);

            if ($setDefault) {
                // If the Document we just saved was flagged as the default, set
                // all other revisions to not be the default (for the same document
                // and language).
                $conn->executeUpdate('UPDATE '.$table.' SET default_rev = :default WHERE uuid = :uuid AND language = :language AND NOT revision = :revision ', [
                    ':default' => 0,
                    ':uuid' => $document->uuid(),
                    ':language' => $document->language(),
                    ':revision' => $document->revision(),
                ]);
            }
        });
    }


}
