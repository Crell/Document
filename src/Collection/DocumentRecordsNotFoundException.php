<?php


namespace Crell\Document\Collection;


class DocumentRecordsNotFoundException extends \InvalidArgumentException
{
    use DocumentIdentifierExceptionTrait;

    /**
     * The arrays that were requested but not found.
     *
     * @var array
     */
    protected $uuids;

    /**
     * @return array
     */
    public function getUuids(): array
    {
        return $this->uuids;
    }

    /**
     * @param array $uuids
     * @return static
     */
    public function setUuids(array $uuids) : self
    {
        $this->uuids = $uuids;
        return $this;
    }
}
