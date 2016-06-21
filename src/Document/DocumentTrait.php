<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

use Ramsey\Uuid\Uuid;

/**
 * Stock implementation of DocumentInterface
 *
 * @see DocumentInterface
 */
trait DocumentTrait
{
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

    /**
     * The language this document is in.
     *
     * @var string
     */
    protected $language;

    public function uuid() : string
    {
        return $this->uuid;
    }

    public function revision() : string
    {
        return $this->revision;
    }

    public function language() : string
    {
        return $this->language;
    }

    public function asLanguage(string $language) : DocumentInterface
    {
        $new = clone $this;
        $new->revision = Uuid::uuid4()->toString();
        $new->language = $language;

        return $new;
    }

    public function jsonSerialize() {
        return [
            'uuid' => $this->uuid,
            'revision' => $this->revision,
            'language' => $this->language,
        ];
    }

}
