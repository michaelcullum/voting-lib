<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\Ballot;
use Michaelc\Voting\STV\Candidate;
use Michaelc\Voting\STV\Election;
use Michaelc\Voting\STV\VoteHandler;
use Psr\Log\LoggerInterface as Logger;
use Tests\Michaelc\Voting\StvElectionTest;

class StvElectionRunnerTest extends \PHPUnit_Framework_TestCase
{
    public function testElectionRun()
    {
        $election = StvElectionTest::getSampleElection();
        $logger = $this->createMock(Logger::class);
        //$logger = new \Tests\Michaelc\Voting\TestLogger();

        $handler = new VoteHandler($election, $logger);
        //$handler->run();
    }

    public function testBallotValidity()
    {
        $election = StvElectionTest::getSampleElection();
        $logger = $this->createMock(Logger::class);

        $handler = new VoteHandler($election, $logger);

        $method = new \ReflectionMethod('\Michaelc\Voting\STV\VoteHandler', 'checkBallotValidity');

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
