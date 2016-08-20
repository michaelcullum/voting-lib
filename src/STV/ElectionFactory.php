<?php

namespace Michaelc\Voting\STV;

use Psr\Log\LoggerInterface as Logger;
use Michaelc\Voting\Exception\VotingLogicException as LogicException;
use Michaelc\Voting\Exception\VotingRuntimeException as RuntimeException;

class ElectionFactory
{
	public static function createBallotCollection(array $rankings): array
	{
		$ballotCollection = [];

		foreach ($rankings as $ranking) {
			$ballotCollection[] = new Ballot($ranking);
		}

		return $ballotCollection;
	}

	public static function createCandidateCollection(array $candidates): array
	{
		$candidateCollection = [];

		$candidates = array_values($candidates);

		foreach ($candidates as $i => $candidate) {
			$candidateCollection[] = new Candidate($i);
		}

		return $candidateCollection;
	}

	public static function createCandidateBallotCollection(array $candidates, array $rankings): array
	{
		$candidateMatchup = $candidateCollection = [];

		$candidates = array_values($candidates);

		foreach ($candidates as $i => $name) {
			$candidateCollection[] = new Candidate($i);
			$candidateMatchup[$name] = $i;
		}

		foreach ($rankings as $ranking) {
			array_walk($ranking, function(&$value) {
			    $value = $candidateMatchup[$value];
			});
		}

		$ballotCollection = $this->createBallotCollection($rankings);

		return ['ballots' => $ballotCollection, 'candidates' => $candidateCollection];
	}

	public static function createElection(array $candidates, array $rankings, int $winnerCount, bool $ids = true): Election
	{
		if ($ids) {
			$candidateCollection = $this->createCandidateSet($candidates);
			$ballotCollection = $this->createBallotCollection($rankings);
		} else {
			$collections = $this->createCandidateBallotCollection($candidates, $rankings);
			$candidates = $collections['candidates'];
			$ballots = $collections['ballots'];
		}

		return new Election($winnerCount, $candidates, $ballots);
	}
}
