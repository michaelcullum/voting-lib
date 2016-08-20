<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\Ballot;
use Michaelc\Voting\STV\Candidate;
use Michaelc\Voting\STV\Election;
use Michaelc\Voting\STV\ElectionRunner;
use Psr\Log\LoggerInterface as Logger;
use Tests\Michaelc\Voting\StvElectionTest;

class StvElectionRunnerTest extends \PHPUnit_Framework_TestCase
{
    public function testElectionRun()
    {
        for ($i=1; $i <= 15; $i++) {
            $election = StvElectionTest::getSampleElection($i);
            $logger = $this->createMock(Logger::class);
            //$logger = new TestLogger();

            $handler = new ElectionRunner($election, $logger);
            $result = $handler->run();

            $this->assertNotNull($result);
            $this->assertCount($i, $result);

            // Candidate 1 will get 8 votes in the first round so should always be elected with a quota of 8 or lower
            if ($handler->getQuota() <= 8)
            {
                $this->assertContains($election->getCandidate(1), $result);
            }

            unset($election, $handler, $result);
        }
    }

    public function testBallotValidity()
    {
        $election = StvElectionTest::getSampleElection();
        $logger = $this->createMock(Logger::class);

        $handler = new ElectionRunner($election, $logger);

        $method = new \ReflectionMethod('\Michaelc\Voting\STV\ElectionRunner', 'checkBallotValidity');

        $method->setAccessible(TRUE);
        $ballot = new Ballot([4, 5, 8]);
        $this->assertTrue($method->invoke($handler, $ballot));

        $ballot = new Ballot([-1, 5, 8]);
        $this->assertFalse($method->invoke($handler, $ballot));

        $ballot = new Ballot([1, 5, 22]);
        $this->assertFalse($method->invoke($handler, $ballot));

        $ballot = new Ballot([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 18, 17]);
        $this->assertFalse($method->invoke($handler, $ballot));
    }
}
