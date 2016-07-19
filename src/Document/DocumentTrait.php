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

    /**
     * The timestamp at which this revision was created, in UTC.
     *
     * @var \DateTimeImmutable
     */
    protected $timestamp;

    /**
     * The title of this document.
     *
     * @var string
     */
    protected $title;

    public function title() : string
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function uuid() : string
    {
        return $this->uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function revision() : string
    {
        return $this->revision;
    }

    /**
     * {@inheritdoc}
     */
    public function language() : string
    {
        return $this->language;
    }

    /**
     * {@inheritdoc}
     */
    public function timestamp() : \DateTimeImmutable
    {
        return $this->timestamp ?: new \DateTimeImmutable();
    }

    /**
     * {@inheritdoc}
     */
    public function asLanguage(string $language) : DocumentInterface
    {
        $new = clone $this;
        $new->revision = Uuid::uuid4()->toString();
        $new->language = $language;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() {
        return [
            'uuid' => $this->uuid,
            'revision' => $this->revision,
            'language' => $this->language,
            'timestamp' => $this->timestamp()->format('c'),
            'title' => $this->title,
        ];
    }

}
