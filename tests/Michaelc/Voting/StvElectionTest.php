<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\{Election, Candidate, Ballot};

class StvElectionTest extends \PHPUnit_Framework_TestCase
{
	public function testNewElection()
	{
		$winners = 2;
		$candidateCount = 6;

		$candidates = $ballots = [];
		$candidates[1] = new Candidate(1);
		$candidates[2] = new Candidate(2);
		$candidates[3] = new Candidate(3);
		$candidates[4] = new Candidate(4);
		$candidates[5] = new Candidate(5);
		$candidates[6] = new Candidate(6);

		$ballots[] = new Ballot([4, 5, 6]);
		$ballots[] = new Ballot([1, 2, 3]);
		$ballots[] = new Ballot([4, 5, 6]);
		$ballots[] = new Ballot([1, 4, 2]);

		$election = new Election($winners, $candidates, $ballots);

		$this->assertEquals($candidates[3], $election->getCandidate(3));
		$this->assertEquals($candidateCount, $election->getCandidateCount());
		$this->assertEquals($candidateCount, $election->getActiveCandidateCount());
		$this->assertEquals($winners, $election->getWinnersCount());
	}
}
