<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * Generic class representing a Document.
 *
 * @todo This likely needs to be broken up into traits and interfaces to make it
 * easier to extend.
 */
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

    /**
     * Returns the UUID of the Document.
     *
     * @return string
     */
    public function uuid() : string
    {
        return $this->uuid;
    }

    /**
     * Returns the Revision ID of the Document.
     *
     * @return string
     */
    public function revision() : string
    {
        return $this->revision;
    }

    /**
     * Returns the Language Code of the Document.
     *
     * @return string
     */
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
