<?php

namespace Michaelc\Voting\STV;

use Michaelc\Voting\STV\{Election, Candidate};

class VoteHandler
{
	protected $election;
	protected $ballots;

	/**
	 * Constructor
	 *
	 * @param Election $election
	 */
	public function __construct(Election $election)
	{
		$this->election = $election;
		$this->ballots = $this->election->getBallots();
	}

	public function step($step)
	{
		foreach ($this->ballots as $i => $ballot)
		{
			$weight = $ballot->getWeight();
			$candidate = $ballot->getNextPreference();
			$election->getCandidate($candidate->getId())->addVotes($weight);
			$ballot->setLastUsedLevel(($step - 1));
		}

		$candidates = $election->getActiveCandidates();

		foreach ($candidates as $i => $candidate) {
			if ($candidate->getVotes() >= $this->getQuota())
			{
        		$candidate->setState(Candidate::ELECTED);
        		$surplus = $candidate->getVotes() - $quota;

        		$this->transferVotes($surplus, $candidate);
        	}
		}
	}

	protected function transferVotes(float $votes, Candidate $candidate)
	{

	}

	public function getQuota()
	{
		return floor(($election->getNumBallots() / ($election->getNumSeats() + 1)) + 1);
	}
}
