<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\Ballot;
use Michaelc\Voting\STV\Candidate;
use Michaelc\Voting\STV\Election;

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

        $candidateIds = $election->getCandidateIds();
        $this->assertCount($candidateCount, $candidateIds);

        for ($i = 1; $i <= $candidateCount; ++$i) {
            $this->assertContains($i, $candidateIds);
        }
    }

    public function testCandidatesStateSetting()
    {
        $election = $this->getSampleElection();

        $election->getCandidates()[5]->setState(Candidate::ELECTED);
        $election->getCandidates()[6]->setState(Candidate::ELECTED);

        $election->getCandidates()[10]->setState(Candidate::DEFEATED);
        $election->getCandidates()[11]->setState(Candidate::DEFEATED);

        $this->assertEquals(Candidate::ELECTED, $election->getCandidates()[5]->getState());
        $this->assertEquals(Candidate::ELECTED, $election->getCandidates()[6]->getState());

        $this->assertEquals(Candidate::DEFEATED, $election->getCandidates()[10]->getState());
        $this->assertEquals(Candidate::DEFEATED, $election->getCandidates()[11]->getState());

        $this->assertEquals(Candidate::RUNNING, $election->getCandidates()[1]->getState());
        $this->assertEquals(Candidate::RUNNING, $election->getCandidates()[2]->getState());
    }

    public function testCandidatesFetchingByState()
    {
        $election = $this->getSampleElection();

        $election->getCandidates()[5]->setState(Candidate::ELECTED);
        $election->getCandidates()[6]->setState(Candidate::ELECTED);

        $election->getCandidates()[10]->setState(Candidate::DEFEATED);

        $active = $election->getActiveCandidates();
        $defeated = $election->getDefeatedCandidates();
        $elected = $election->getElectedCandidates();
        $activeIds = $election->getActiveCandidateIds();

        $this->assertContains($election->getCandidates()[5], $elected);
        $this->assertContains($election->getCandidates()[6], $elected);
        $this->assertContains($election->getCandidates()[10], $defeated);
        $this->assertContains($election->getCandidates()[1], $active);

        $this->assertContains(8, $activeIds);
        $this->assertContains(1, $activeIds);
        $this->assertNotContains(6, $activeIds);
        $this->assertNotContains(10, $activeIds);
        $this->assertCount(($election->getCandidateCount() - 3), $activeIds);

        $this->assertCount(2, $elected);
        $this->assertCount(1, $defeated);
        $this->assertCount(($election->getCandidateCount() - 3), $active);
    }

    public static function getSampleElection($winners = 4)
    {
        $candidates = $ballots = [];

        for ($i = 1; $i <= 15; ++$i) {
            $candidates[$i] = new Candidate($i);
        }

        $ballots = StvBallotTest::getBallotSample();

        $election = new Election($winners, $candidates, $ballots);

        return $election;
    }
}
