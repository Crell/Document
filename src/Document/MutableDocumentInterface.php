<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


interface MutableDocumentInterface {

    public function setRevisionId(string $revision) : MutableDocumentInterface;
}
