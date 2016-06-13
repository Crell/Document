<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * Generic class representing a Document.
 *
 * @todo This likely needs to be broken up into traits and interfaces to make it
 * easier to extend.
 */
interface DocumentInterface extends \JsonSerializable {
    /**
     * Returns the UUID of the Document.
     *
     * @return string
     */
    public function uuid() : string;

    /**
     * Returns the Revision ID of the Document.
     *
     * @return string
     */
    public function revision() : string;

    /**
     * Returns the Language Code of the Document.
     *
     * @return string
     */
    public function language() : string;

    /**
     * @todo This is probably wrong and terrible and should be removed in favor
     *       of some other approach. More research needed.
     *
     * @param string $language
     *
     * @return \Crell\Document\Document\Document
     */
    public function asLanguage(string $language) : self;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize();
}
