<?php

namespace Michaelc\Voting\STV;

use Michaelc\Voting\STV\Candidate;

class Election
{
	/**
	 * Count of candidates in election
	 *
	 * @var int
	 */
	protected $candidateCount;

	/**
	 * Count of number of seats/winners
	 *
	 * @var int
	 */
	protected $winnersCount;

	/**
	 * Array of candidates competing in election
	 *
	 * @var array
	 */
	protected $candidates;

	/**
	 * Array of ballots cast in election
	 *
	 * @var array
	 */
	protected $ballots;

	public function __construct(int $winnersCount, array $candidates, array $ballots)
	{
		$this->winnersCount = $winnersCount;
		$this->candidates = $candidates;
		$this->ballots = $ballots;
		$this->candidateCount = count($candidates);
	}

	public function getCandidate(int $id): Candidate
	{
		return $this->candidates[$id];
	}

	public function getCandidateCount(): int
	{
		return $this->candidateCount;
	}

	public function getActiveCandidates(): array
	{
		$activeCandidates = [];

		foreach ($this->candidates as $candidateId => $candidate)
		{
			if ($candidate->getState() == Candidate::RUNNING)
			{
				$activeCandidates[$candidateId] = $candidate;
			}
		}

		return $activeCandidates;
	}

	public function getActiveCandidateCount(): int
	{
		return count($this->getActiveCandidates());
	}

    /**
     * Gets the value of winnersCount.
     *
     * @return int
     */
    public function getWinnersCount(): int
    {
        return $this->winnersCount;
    }

    /**
     * Gets the value of candidates.
     *
     * @return array
     */
    public function getCandidates(): array
    {
        return $this->candidates;
    }

    /**
     * Gets the value of ballots.
     *
     * @return array
     */
    public function getBallots(): array
    {
        return $this->ballots;
    }
}
