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
        $logger = new \Tests\Michaelc\Voting\TestLogger();

        $runner = new VoteHandler($election, $logger);
        $runner->run();
    }
}
