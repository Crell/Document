<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Doctrine\DBAL\Schema\Table;

/**
 * Table definition for a Collection.
 */
class DoctrineCollectionTable extends Table
{

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
