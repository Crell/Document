<?php


namespace Crell\Document\Document;


trait MutableDocumentTrait {

    /**
     *
     * @param string $revision
     *
     * @return self
     *   The modified object.
     */
    public function setRevisionId(string $revision) : MutableDocumentInterface
    {
        $this->revision = $revision;

        return $this;
    }
}
