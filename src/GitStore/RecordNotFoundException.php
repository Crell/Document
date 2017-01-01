<?php

declare(strict_types = 1);

namespace Crell\Document\GitStore;


class RecordNotFoundException extends \InvalidArgumentException implements GitException
{
}
