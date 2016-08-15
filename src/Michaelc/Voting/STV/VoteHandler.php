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
     * @param Ballot $ballot 	The ballot to allocate the votes from
     * @return Ballot 			The same ballot passed in modified
     */
    protected function allocateVotes(Ballot &$ballot): Ballot
    {
        $weight = $ballot->getWeight();
        $candidate = $ballot->getNextChoice();
        $this->election->getCandidate($candidate->getId())->addVotes($weight);
        $ballot->setLevelUsed(($step - 1));

        return $ballot;
    }

    /**
     * Transfer the votes from one candidate to other candidates
     *
     * @param  float     $votes     [description]
     * @param  Candidate $candidate [description]
     * @return [type]               [description]
     */
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
        	$this->transferVotes($minimumCandidate);
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
