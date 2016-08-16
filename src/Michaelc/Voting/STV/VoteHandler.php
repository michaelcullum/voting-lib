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
     * Number of candidates elected so far
     *
     * @var int
     */
    protected $electedCandidates;

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

    /**
     * Run the election
     *
     * @return \MichaelC\Voting\STV\Candidate[]	Winning candidates
     */
    public function run()
    {
    	$this->firstStep();

        $candidates = $this->election->getActiveCandidates();

        while ($electedCandidates < $this->election->getWinners())
        {
	    	if (!$this->checkCandidates($candidates))
	    	{
	    		$this->eliminateCandidates($candidates);
	    	}
        }

        return $this->election->getElectedCandidates();
    }

    /**
     * Perform the initial vote allocation
     *
     * @return
     */
    public function firstStep()
    {
        foreach ($this->ballots as $i => $ballot)
        {
            $this->allocateVotes($ballot);
        }

        return;
    }

    /**
     * Check if any candidates have reached the quota and can be elected
     *
     * @param  array  $candidates 	Array of active candidates to check
     * @return bool 				Whether any candidates were changed to elected
     */
    protected function checkCandidates(array &$candidates): bool
    {
    	foreach ($candidates as $i => $candidate)
        {
            if ($candidate->getVotes() >= $this->quota)
            {
                $this->electCandidate($candidate);
                $elected = true;
            }
        }

        return $elected ?? false;
    }

    /**
     * Allocate the next votes from a Ballot
     *
     * @param Ballot $ballot 		The ballot to allocate the votes from
     * @param float  $multiplier 	Number to multiply the weight by (surplus)
     * @param float  $divisor 		The divisor of the weight (Total number of
     *                          	candidate votes)
     * @return Ballot 	The same ballot passed in modified
     */
    protected function allocateVotes(Ballot &$ballot, float $multiplier = 1.0, float $divisor = 1.0): Ballot
    {
        $weight = ($ballot->getWeight() * $multiplier) / $divisor;
        $candidate = $ballot->getNextChoice();
        $this->election->getCandidate($candidate->getId())->addVotes($weight);
        $ballot->setLevelUsed(($step - 1));

        return $ballot;
    }

    /**
     * Transfer the votes from one candidate to other candidates
     *
     * @param  float     $surplus   The number of surplus votes to transfer
     * @param  Candidate $candidate The candidate being elected to transfer
     *                              the votes from
     * @return
     */
    protected function transferSurplusVotes(float $surplus, Candidate $candidate)
    {
    	$totalVotes = $candiate->getVotes();
    	$candidateId = $candidate->getId();

    	foreach ($this->ballots as $i => $ballot)
    	{
        	if ($ballot->getLastChoice()->getId() == $candidateId)
        	{
		        $this->allocateVotes($ballot, $surplus, $totalVotes);
        	}
    	}

        return;
    }

    /**
     * Transfer the votes from one eliminated candidate to other candidates
     *
     * @param  Candidate $candidate  Candidate being eliminated to transfer
     *                               the votes from
     * @return
     */
    protected function transferEliminatedVotes(Candidate $candidate)
    {
    	$candidateId = $candidate->getId();

    	foreach ($this->ballots as $i => $ballot)
        {
        	if ($ballot->getLastChoice()->getId() == $candidateId)
        	{
		        $this->allocateVotes($ballot);
        	}
        }

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
        $this->electedCandidates++;

        if ($this->electedCandidates < $this->election->getWinnersCount())
        {
        	$surplus = $candidate->getVotes() - $this->quota;
        	$this->transferVotes($surplus, $candidate);
        }

        return;
    }

    /**
     * Eliminate the candidate(s) with the lowest number of votes
     * and reallocated their votes
     *
     * @param  array  $candidates 	Array of active candidates
     * @return int 					Number of candidates eliminated
     */
    protected function eliminateCandidates(array &$candidates): int
    {
    	$minimum = 0;

    	foreach ($candidates as $i => $candidate)
        {
            if ($candidate->getVotes() > $minimum)
            {
                $minimum = $candidate->getVotes();
                unset($minimumCandidates);
                $minimumCandidates[] = $candidate;
            }
            elseif ($candidate->getVotes() == $minimum)
            {
                $minimumCandidates[] = $candidate;
            }
        }

        foreach($minimumCandidates as $minimumCandidate)
        {
        	$this->transferEliminatedVotes($minimumCandidate);
        	$minimumCandidate->setState(Candidate::DEFEATED);
        }

        return count($minimumCandidates);
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
