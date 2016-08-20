<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


use Crell\Document\Collection\DocumentIdentifierExceptionTrait;

class DocumentNotFoundException extends \InvalidArgumentException implements DocumentException
{
    use DocumentIdentifierExceptionTrait;


}
