<?php

namespace Crell\Document\Document;

class Document {

    /**
     * UUID of this document
     *
     * @var string
     */
    protected $id;

    public function __construct(string $id) {
        $this->id = $id;
    }

    public function id() : string {
        return $this->id;
    }
}
