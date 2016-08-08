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

		for ($i=1; $i <= $candidateCount; $i++) {
			$candidates[$i] = new Candidate($i);
		}

		$ballots[] = new Ballot([4, 5, 6]);
		$ballots[] = new Ballot([1, 2, 3]);

		$election = new Election($winners, $candidates, $ballots);

		$this->assertEquals($candidates[3], $election->getCandidate(3));
		$this->assertEquals($candidateCount, $election->getCandidateCount());
		$this->assertEquals($candidateCount, $election->getActiveCandidateCount());
		$this->assertEquals($winners, $election->getWinnersCount());
	}
}
