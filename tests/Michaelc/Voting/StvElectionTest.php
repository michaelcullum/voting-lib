<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\Ballot;
use Michaelc\Voting\STV\Candidate;
use Michaelc\Voting\STV\Election;
use Michaelc\Voting\STV\VoteHandler;
use Psr\Log\LoggerInterface as Logger;

class StvElectionTest extends \PHPUnit_Framework_TestCase
{
    public function testNewElection()
    {
        $winners = 2;
        $candidateCount = 6;

        $candidates = $ballots = [];

        for ($i = 1; $i <= $candidateCount; ++$i) {
            $candidates[$i] = new Candidate($i);
        }

        $ballots[] = new Ballot([4, 5, 6]);
        $ballots[] = new Ballot([1, 2, 3]);

        $election = new Election($winners, $candidates, $ballots);

        $this->assertEquals($candidates[3], $election->getCandidate(3));
        $this->assertEquals($candidateCount, $election->getCandidateCount());
        $this->assertEquals($candidateCount, $election->getActiveCandidateCount());
        $this->assertEquals($winners, $election->getWinnersCount());
        $this->assertEquals(2, $election->getNumBallots());
    }

    public function testElectionRunner()
    {
        $election = $this->getSampleElection();
        $logger = $this->createMock(Logger::class);

        $runner = new VoteHandler($election, $logger);
    }

    protected function getSampleElection()
    {
        $candidates = $ballots = [];

        for ($i = 1; $i <= 20; ++$i) {
            $candidates[$i] = new Candidate($i);
        }

        for ($i = 0; $i <= 40; ++$i) {
            $ballots[] = new Ballot([random_int(1, 20), random_int(1, 20), random_int(1, 20)]);
        }

        $election = new Election(12, $candidates, $ballots);

        return $election;
    }
}
