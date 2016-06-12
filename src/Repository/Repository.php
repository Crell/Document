<?php

namespace Crell\Document\Repository;

use Crell\Document\Document\Document;

class Repository
{

    public function load(string $id) : Document {

        return new Document($id);

    }

}
