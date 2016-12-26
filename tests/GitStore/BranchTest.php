<?php

declare(strict_types = 1);

namespace Crell\Document\Test\GitStore;


class BranchTest extends \PHPUnit_Framework_TestCase
{
    use GitRepositoryTestUtils;

    public function testCommitOnBranch()
    {
        $repo = $this->getRepository();
        $branch = $repo->getBranchPointer();

        $doc1 = ['hello' => 'world'];
        $doc2 = ['goodbye' => 'world'];

        $branch->commit(['doc1' => $doc1, 'doc2' => $doc2], 'Me <me>', 'Test commit');

        $this->assertEquals($doc1, $branch->load('doc1'));
    }

}
