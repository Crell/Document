<?php


namespace Crell\Document\Document;


interface MutableDocumentInterface {

    public function setRevisionId(string $revision) : MutableDocumentInterface;
}
