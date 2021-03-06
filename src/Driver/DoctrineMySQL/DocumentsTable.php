<?php

declare (strict_types = 1);

namespace Crell\Document\Driver\DoctrineMySQL;

use Doctrine\DBAL\Schema\Table;

/**
 * Table definition for Documents and Revisions.
 */
class DocumentsTable extends Table implements DocumentDefinitionTableInterface
{

    public static function name() : string
    {
        return 'documents';
    }

    public function __construct($tableName)
    {
        parent::__construct($tableName);

        $this->addColumn('revision', 'string', [
            'length' => 36,
        ]);
        $this->addColumn('uuid', 'string', [
            'length' => 36,
        ]);
        $this->addColumn('parent_rev', 'string', [
            'length' => 36,
        ]);
        $this->addColumn('latest', 'boolean');
        // default_rev is named differently because "default" is a reserved word.
        $this->addColumn('default_rev', 'boolean');
        $this->addColumn('archived', 'boolean');
        $this->addColumn('language', 'string', [
            'length' => 12,
        ]);
        $this->addColumn('created', 'datetime');

        $this->addColumn('title', 'string', [
            'length' => 255,
            'default' => '',
        ]);

        $this->addColumn('document', 'json_array', [
            'length' => 16777215, // This size triggers a MEDIUMTEXT field on MySQL. Postgres will use native JSON.
        ]);
        $this->setPrimaryKey(['revision']);
        $this->addIndex(['uuid']);

    }
}
