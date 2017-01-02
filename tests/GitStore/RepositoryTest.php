<?php

declare(strict_types = 1);

namespace Crell\Document\Test\GitStore;


use Crell\Document\GitStore\InvalidCommitterException;
use Crell\Document\GitStore\RecordNotFoundException;

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

    public function testCommitWithEmptyMessageCausesNoError() {
        $repo = $this->getRepository();

        $doc1 = ['hello' => 'world'];
        $doc2 = ['goodbye' => 'world'];

        $repo->commit(['doc1' => $doc1, 'doc2' => $doc2], 'Me <me>', '', 'master', 'master');

        $this->assertEquals($doc1, $repo->load('doc1', 'master'));
    }

    public function testCommitWithInvalidAuthor() {
        $repo = $this->getRepository();

        $doc1 = ['hello' => 'world'];
        $doc2 = ['goodbye' => 'world'];

        $this->expectException(InvalidCommitterException::class);

        $repo->commit(['doc1' => $doc1, 'doc2' => $doc2], 'Me', '', 'master', 'master');
    }

    public function testLoadInvalidFileThrowsException() {
        $repo = $this->getRepository();

        $doc1 = ['hello' => 'world'];
        $doc2 = ['goodbye' => 'world'];

        $repo->commit(['doc1' => $doc1, 'doc2' => $doc2], 'Me <me>', 'Test commit', 'master', 'master');

        $this->expectException(RecordNotFoundException::class);

        $this->assertEquals($doc1, $repo->load('doc3', 'master'));
    }

    public function testLoadOldRevision()
    {
        $repo = $this->getRepository();

        $doc1 = ['hello' => 'world'];

        $commit1 = $repo->commit(['doc1' => $doc1], 'Me <me>', 'Test commit', 'master', 'master');

        $doc2 = ['goodbye' => 'world'];
        $commit2 = $repo->commit(['doc1' => $doc2], 'Me <me>', 'Test commit', 'master', 'master');

        $loaded = $repo->load('doc1', $commit1);

        $this->assertEquals($doc1, $loaded);
    }

    public function testGetOldRevisionHistoryForDocument()
    {
        $repo = $this->getRepository();

        $doc1 = ['hello' => 'world'];

        $commit1 = $repo->commit(['doc1' => $doc1], 'Me <me>', 'Test commit', 'master', 'master');

        // This is a different document so should not show up in the history for doc1.
        $doc2 = ['goodbye' => 'world'];
        $commit2 = $repo->commit(['doc2' => $doc2], 'Me <me>', 'Test commit', 'master', 'master');

        $doc1Version2 = ['hello' => 'everyone'];
        $commit3 = $repo->commit(['doc1' => $doc1Version2], 'Me <me>', 'Test commit', 'master', 'master');

        $commits = $repo->history('doc1', 'master');

        $commitsArray = iterator_to_array($commits);

        $this->assertEquals($commit3, $commitsArray[0]);
        $this->assertEquals($commit1, $commitsArray[1]);
        $this->assertEquals(2, count($commitsArray));
    }

}
