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

    /**
     * The UUID of the parent revision, if any.
     *
     * @var string
     */
    protected $parentRev;

    /**
     * {@inheritdoc}
     */
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

    public function parent() : string
    {
        return $this->parentRev;
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
        $class = new \ReflectionClass($this);
        while ($class->isAnonymous()) {
            $class = $class->getParentClass();
        }

        $data = [
            'class' => $class->name,
            'uuid' => $this->uuid,
            'revision' => $this->revision,
            'parent_rev' => $this->parentRev,
            'language' => $this->language,
            'timestamp' => $this->timestamp()->format('c'),
            'title' => $this->title,
            'fields' => [],
        ];

        /**
         * @var string $name
         * @var FieldSet $set
         */
        foreach ($this->fields as $name => $set) {
            $class = new \ReflectionClass($set[0]);
            while ($class->isAnonymous()) {
                $class = $class->getParentClass();
            }

            $data['fields'][$name]['class'] = $class->name;
            foreach ($set as $index => $field) {
                $data['fields'][$name]['items'][] = $field->jsonSerialize();
            }
        }

        return $data;
    }
}
