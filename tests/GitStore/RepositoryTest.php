<?php

declare(strict_types = 1);

namespace Crell\Document\Test\GitStore;


class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    use GitRepositoryTestUtils;

    public function testInitialize()
    {
        $repo = $this->getRepository();

        $this->assertEquals('master', $repo->getBranchPointer()->branch());
    }

    public function testCommitAndLoad() {
        $repo = $this->getRepository();

        $doc1 = ['hello' => 'world'];
        $doc2 = ['goodbye' => 'world'];

        $repo->commit(['doc1' => $doc1, 'doc2' => $doc2], 'Me <me>', 'Test commit', 'master', 'master');

        $this->assertEquals($doc1, $repo->load('doc1', 'master'));
    }

    public function testLoadInvalidFileThrowsException() {
        $repo = $this->getRepository();

        $doc1 = ['hello' => 'world'];
        $doc2 = ['goodbye' => 'world'];

        $repo->commit(['doc1' => $doc1, 'doc2' => $doc2], 'Me <me>', 'Test commit', 'master', 'master');

        $this->expectException(\InvalidArgumentException::class);

        $this->assertEquals($doc1, $repo->load('doc3', 'master'));
    }
}
