<?php

declare (strict_types = 1);

namespace Crell\Document\Driver\DoctrineMySQL;

use Doctrine\DBAL\Schema\Table;

/**
 * Table definition for tracking Commits.
 */
class CommitsTable extends Table implements DocumentDefinitionTableInterface
{

    public static function name() : string
    {
        return 'commits';
    }

    public function __construct($tableName)
    {
        parent::__construct($tableName);

        $this->addColumn('uuid', 'string', [
            'length' => 36,
        ]);
        // @todo This should probably change later, I guess?
        $this->addColumn('author', 'string', [
            'length' => 128,
        ]);
        // @todo Should we just make this a blob?
        $this->addColumn('message', 'string', [
            'length' => 1024,
        ]);

        $this->addColumn('created', 'datetime');

        $this->setPrimaryKey(['uuid']);
    }
}
