<?php

declare (strict_types = 1);

namespace Crell\Document\Driver\DoctrineMySQL;

use Doctrine\DBAL\Schema\Table;

/**
 * Table definition for tracking Branches.
 */
class BranchesTable extends Table implements DocumentDefinitionTableInterface
{

    public static function name() : string
    {
        return 'branches';
    }

    public function __construct($tableName, Table $commitTable)
    {
        parent::__construct($tableName);

        $this->addColumn('name', 'string', [
            'length' => 36,
        ]);
        $this->addColumn('commit', 'string', [
            'length' => 36,
        ]);

        $this->setPrimaryKey(['name']);
        $this->addForeignKeyConstraint($commitTable, ['commit'], ['uuid']);
    }
}
