<?php


namespace Crell\Document\Collection;


class DocumentRecordNotFoundException extends \InvalidArgumentException
{
    use DocumentIdentifierExceptionTrait;
}
