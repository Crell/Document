<?php

namespace Crell\Document\Document;

class Document {

    /**
     * UUID of this document.
     *
     * @var string
     */
    protected $uuid;

    /**
     * Revision ID of this document.
     *
     * @var string.
     */
    protected $revision;

    public function __construct(string $uuid) {
        $this->uuid = $uuid;
    }

    public function uuid() : string {
        return $this->uuid;
    }
}
