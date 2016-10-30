<?php

declare (strict_types = 1);

namespace Crell\Document\Driver\DoctrineMySQL;

use Doctrine\DBAL\Schema\Table;

/**
 * Table definition for tracking Document/Commit membership.
 */
class CommitDocumentsTable extends Table implements DocumentDefinitionTableInterface
{

    public static function name() : string
    {
        return 'commit_documents';
    }

    public function __construct($tableName, Table $documentTable, Table $commitTable)
    {
        parent::__construct($tableName);

        $this->addColumn('revision', 'string', [
            'length' => 36,
        ]);
        $this->addColumn('commit', 'string', [
            'length' => 36,
        ]);

        $this->setPrimaryKey(['revision', 'commit']);
        $this->addForeignKeyConstraint($documentTable, ['revision'], ['revision']);
        $this->addForeignKeyConstraint($commitTable, ['commit'], ['uuid']);
    }
}
