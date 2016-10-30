<?php

declare(strict_types = 1);

namespace Crell\Document\Driver\DoctrineMySQL;


interface DocumentDefinitionTableInterface
{
    /**
     * Returns the name of this table, within the context of a collection.
     *
     * @return string
     */
    public static function name() : string;

}
