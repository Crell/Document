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
    public function jsonSerialize() {
        $class = new \ReflectionClass($this);
        while ($class->isAnonymous()) {
            $class = $class->getParentClass();
        }

        $data = [
            'class' => $class->name,
            'uuid' => $this->uuid,
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
