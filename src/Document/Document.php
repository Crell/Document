<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * Generic class representing a Document.
 *
 * @todo This likely needs to be broken up into traits and interfaces to make it
 * easier to extend.
 */
class Document implements \JsonSerializable
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

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() {
        return [
            'uuid' => $this->uuid,
            'revision' => $this->revision,
            'language' => $this->language,
        ];
    }

}
