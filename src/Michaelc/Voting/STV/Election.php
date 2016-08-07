<?php

namespace Michaelc/Voting/STV;

class Election
{
	protected $candidateCount;
	protected $winnersCount;

	protected $candidates;
	protected $ballots;

	public function __construct(int $winnersCount, array $candidates, array $ballots)
	{
		$this->winnersCount = $winnersCount;
		$this->candidates = $candidates;
		$this->ballots = $ballots;
	}
}
