<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\Ballot;
use Michaelc\Voting\STV\Candidate;
use Michaelc\Voting\STV\Election;
use Michaelc\Voting\STV\ElectionFactory;

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

        $election = new Election($candidates, $ballots, $winners);

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

        $election = new Election($candidates, $ballots, $winners);

        return $election;
    }

    public function testCandidateBallotCollectionCreator()
    {
        $candidates = ['Gibbs', 'Kate', 'Dinozzo', 'McGee', 'Bishop', 'Ziva'];

        $rankings = [];
        $rankings[] = ['Gibbs', 'Kate', 'Dinozzo'];
        $rankings[] = ['Dinozzo', 'McGee', 'Bishop', 'Kate', 'Ziva','Gibbs'];
        $rankings[] = ['Kate', 'Dinozzo', 'McGee', 'Gibbs', 'Bishop', 'Ziva'];

        $collections = ElectionFactory::createCandidateBallotCollection($candidates,$rankings);

        $this->assertCount(6, $collections['candidates']);
        $this->assertCount(3, $collections['ballots']);
        $this->assertContainsOnlyInstancesOf(Candidate::class, $collections['candidates']);
        $this->assertContainsOnlyInstancesOf(Ballot::class, $collections['ballots']);
        $this->assertCount(3, $collections['ballots'][0]->getRanking());
        $this->assertEquals(1, $collections['ballots'][0]->getRanking()[1]);
        $this->assertNotContains(4, $collections['ballots'][0]->getRanking());
    }

    public function testElectionFactory()
    {
        $candidates = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
        $ballots = StvBallotTest::getBallotArrays();
        $election = ElectionFactory::createElection($candidates, $ballots, 4, true);
        $this->assertInstanceOf(Election::class, $election);
        $this->assertCount(15, $election->getCandidates());
        $this->assertCount(41, $election->getBallots());
        $this->assertEquals(4, $election->getWinnersCount());
        unset($candidates, $ballots, $elections);

        $candidates = ['Gibbs', 'Vance', 'Dinozzo', 'McGee', 'Abby', 'Ducky'];

        $rankings = [];
        $rankings[] = ['Gibbs', 'Vance', 'Dinozzo'];
        $rankings[] = ['Ducky', 'McGee', 'Abby', 'Vance','Gibbs'];
        $rankings[] = ['Abby', 'Dinozzo', 'McGee', 'Gibbs', 'Vance', 'Ducky'];
        $election = ElectionFactory::createElection($candidates, $rankings, 2, false);
        $this->assertInstanceOf(Election::class, $election);
        $this->assertCount(6, $election->getCandidates());
        $this->assertCount(3, $election->getBallots());
        $this->assertEquals(2, $election->getWinnersCount());

        return $election;
    }
}
