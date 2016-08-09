<?php

namespace Michaelc\Voting\STV;

use Michaelc\Voting\STV\{Election, Ballot, Candidate};

class VoteHandler
{
	/**
	 * Election object
	 *
	 * @var \Michaelc\Voting\STV\Election;
	 */
	protected $election;

	/**
	 * Array of all ballots in election
	 *
	 * @var \MichaelC\Voting\STV\Ballot[]
	 */
	protected $ballots;

	/**
	 * Quota of votes needed for a candidate to be elected
	 *
	 * @var int
	 */
	protected $quota;

	/**
	 * Constructor
	 *
	 * @param Election $election
	 */
	public function __construct(Election $election)
	{
		$this->election = $election;
		$this->ballots = $this->election->getBallots();
		$this->quota = $this->getQuota();
	}

	public function step($step)
	{
		foreach ($this->ballots as $i => $ballot)
		{
			$this->allocateVotes($ballot);
		}

		$candidates = $election->getActiveCandidates();

		foreach ($candidates as $i => $candidate)
		{
			if ($candidate->getVotes() >= $this->quota)
			{
				$this->electCandidate($candidate);
        	}
		}
	}

	protected function allocateVotes(&$ballot): Ballot
	{
		$weight = $ballot->getWeight();
		$candidate = $ballot->getNextPreference();
		$this->election->getCandidate($candidate->getId())->addVotes($weight);
		$ballot->setLastUsedLevel(($step - 1));

		return $ballot;
	}

	protected function transferVotes(float $votes, Candidate $candidate)
	{
		return;
	}

	/**
	 * Elect a candidate after they've passed the threshold
	 *
	 * @param  \Michaelc\Voting\STV\Candidate $candidate
	 * @return null
	 */
	protected function electCandidate(Candidate $candidate)
	{
		if ($candidate->getVotes() < $this->quota)
		{
			throw new Exception("We shouldn't be electing someone who hasn't met the quota");
    	}

		$candidate->setState(Candidate::ELECTED);
		$surplus = $candidate->getVotes() - $this->quota;

		$this->transferVotes($surplus, $candidate);

		return;
	}

	/**
	 * Get the quota to win
	 *
	 * @return int
	 */
	public function getQuota(): int
	{
		return floor(
			($this->election->getNumBallots() /
				($this->election->getWinnersCount() + 1)
			)
			+ 1);
	}
}
