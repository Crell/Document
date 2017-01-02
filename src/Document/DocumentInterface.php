<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * Generic class representing a Document.
 *
 * @todo This likely needs to be broken up into traits and interfaces to make it
 * easier to extend.
 */
interface DocumentInterface extends \JsonSerializable
{
    /**
     * Returns the title of this document.
     *
     * This will likely be removed later, but I need editable data for testing.
     *
     * @return string
     */
    public function title() : string;

    /**
     * Returns the UUID of the Document.
     *
     * @return string
     */
    public function uuid() : string;

    /**
     * Returns the Language Code of the Document.
     *
     * @return string
     */
    public function language() : string;

    /**
     * Returns the time at which this revision was created.
     *
     * The timestamp must always be in UTC time, unconditionally.
     *
     * If the revision has never been saved, it will return the current time.
     *
     * @return \DateTimeImmutable
     */
    public function timestamp() : \DateTimeImmutable;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize();

}
