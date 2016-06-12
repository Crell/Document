<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

class Document
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

    public function __construct(string $uuid, string $revision, string $language)
    {
        $this->uuid = $uuid;
        $this->revision = $revision;
        $this->language = $language;
    }

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

    /**
     * @todo This is probably wrong and terrible and should be removed in favor
     *       of some other approach. More research needed.
     *
     * @param string $language
     *
     * @return \Crell\Document\Document\Document
     */
    public function asLanguage(string $language) : self
    {
        $new = clone $this;
        $new->revision = '';
        $new->language = $language;

        return $new;
    }
}
