<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


trait LoadableDocumentTrait {

    public function loadFrom(array $data) : self
    {
        foreach (['uuid', 'revision', 'language'] as $key) {
            $this->$key = $data[$key];
        }

        return $this;
    }
}
