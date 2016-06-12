<?php

declare(strict_types=1);

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

    public function __construct(string $uuid, string $revision) {
        $this->uuid = $uuid;
        $this->revision = $revision;
    }

    public function uuid() : string {
        return $this->uuid;
    }

    public function revision() : string {
        return $this->revision;
    }
}
