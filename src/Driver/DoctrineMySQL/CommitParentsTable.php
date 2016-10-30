<?php

declare (strict_types = 1);

namespace Crell\Document\Driver\DoctrineMySQL;

use Doctrine\DBAL\Schema\Table;

/**
 * Table definition for tracking Commit parentage.
 */
class CommitParentsTable extends Table implements DocumentDefinitionTableInterface
{

    public static function name() : string
    {
        return 'commit_parents';
    }

    public function __construct($tableName, Table $commitTable)
    {
        parent::__construct($tableName);

        $this->addColumn('commit', 'string', [
            'length' => 36,
        ]);
        $this->addColumn('parent', 'string', [
            'length' => 36,
        ]);

        $this->setPrimaryKey(['commit', 'parent']);
        $this->addForeignKeyConstraint($commitTable, ['commit'], ['uuid']);
        // @todo May need to skip this one until we figure out how to make it optional.
        $this->addForeignKeyConstraint($commitTable, ['parent'], ['uuid']);
    }
}
