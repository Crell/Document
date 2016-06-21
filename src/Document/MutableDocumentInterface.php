<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


interface MutableDocumentInterface extends DocumentInterface {

    public function setRevisionId(string $revision) : MutableDocumentInterface;

    public function setTimestamp(\DateTimeImmutable $timestamp) : MutableDocumentInterface;
}
