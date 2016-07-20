<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


trait LoadableDocumentTrait {

    public function loadFrom(array $data) : self
    {
        foreach (['uuid', 'revision', 'language', 'title'] as $key) {
            $this->$key = $data[$key];
        }

        // Named differently because coding standards.
        // @todo Do something about this.
        $this->parentRev = $data['parent_rev'];

        //$this->timestamp = new \DateTimeImmutable($data['timestamp']);
        $this->timestamp = $data['timestamp'];

        return $this;
    }
}
