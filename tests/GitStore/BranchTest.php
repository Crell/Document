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

    public function testBranchFromBranch()
    {
        $repo = $this->getRepository();
        $master = $repo->getBranchPointer();

        $doc1 = ['hello' => 'world'];
        $doc2 = ['goodbye' => 'world'];

        $master->commit(['doc1' => $doc1, 'doc2' => $doc2], 'Me <me>', 'Test commit');

        $test = $master->createBranch('test');

        $doc3 = ['hello' => 'everyone'];

        $test->commit(['doc3' => $doc3], 'Me <me>', 'Branch commit');

        // Master should have 2 documents.
        $this->assertEquals($doc1, $master->load('doc1'));
        $this->assertEquals($doc2, $master->load('doc2'));

        // The test branch should have 3 documents.
        $this->assertEquals($doc1, $test->load('doc1'));
        $this->assertEquals($doc2, $test->load('doc2'));
        $this->assertEquals($doc3, $test->load('doc3'));

        // But master should not have the 3rd document.
        try {
            $master->load('doc3');
            $this->fail('No exception thrown for missing document');
        } catch (\Exception $e) {
            // No action needed.
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    public function testCommitHistory()
    {
        $repo = $this->getRepository();
        $master = $repo->getBranchPointer();

        $doc1 = ['hello' => 'world'];

        $commit1 = $master->commit(['doc1' => $doc1], 'Me <me>', 'Test commit');

        // This is a different document so should not show up in the history for doc1.
        $doc2 = ['goodbye' => 'world'];
        $commit2 = $master->commit(['doc2' => $doc2], 'Me <me>', 'Test commit');

        $doc1Version2 = ['hello' => 'everyone'];
        $commit3 = $master->commit(['doc1' => $doc1Version2], 'Me <me>', 'Test commit');

        $commits = $master->history('doc1');

        $commitsArray = iterator_to_array($commits);

        $this->assertEquals($commit3, $commitsArray[0]);
        $this->assertEquals($commit1, $commitsArray[1]);
        $this->assertEquals(2, count($commitsArray));
    }

}
