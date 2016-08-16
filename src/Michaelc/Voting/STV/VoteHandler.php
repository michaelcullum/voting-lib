<?php

namespace Michaelc\Voting\STV;

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
     * Invalid ballots
     *
     * @var \MichaelC\Voting\STV\Ballot[]
     */
    protected $rejectedBallots;

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
        $this->rejectedBallots = [];
    }

    /**
     * Run the election
     *
     * @return \MichaelC\Voting\STV\Candidate[]	Winning candidates
     */
    public function run()
    {
        $this->rejectInvalidBallots();

        $this->firstStep();

        $candidates = $this->election->getActiveCandidates();

        while ($this->electedCandidates < $this->election->getWinnersCount())
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
    protected function firstStep()
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

        return ($elected ?? false);
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
        $weight = $ballot->setWeight(($ballot->getWeight() * $multiplier) / $divisor);
        $candidate = $ballot->getNextChoice();

        if ($candidate !== null)
        {
            $this->election->getCandidate($candidate)->addVotes($weight);
            $ballot->incrementLevelUsed();
        }

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
    	$totalVotes = $candidate->getVotes();
    	$candidateId = $candidate->getId();

    	foreach ($this->ballots as $i => $ballot)
    	{
        	if ($ballot->getLastChoice() == $candidateId)
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
        	if ($ballot->getLastChoice() == $candidateId)
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
            $this->transferSurplusVotes($surplus, $candidate);
        }

        return;
    }

    /**
     * Eliminate the candidate(s) with the lowest number of votes
     * and reallocated their votes
     *
     * @param  \Michaelc\Voting\STV\Candidate[] $candidates
     *                              Array of active candidates
     * @return int 					Number of candidates eliminated
     */
    protected function eliminateCandidates(array &$candidates): int
    {
        $minimumCandidates = $this->getLowestCandidates($candidates);

        foreach ($minimumCandidates as $minimumCandidate)
        {
            $this->transferEliminatedVotes($minimumCandidate);
            $minimumCandidate->setState(Candidate::DEFEATED);
        }

        return count($minimumCandidates);
    }

    /**
     * Get candidates with the lowest number of votes
     *
     * @param  \Michaelc\Voting\STV\Candidate[] $candidates
     *                             Array of active candidates
     * @return \Michaelc\Voting\STV\Candidate[]
     *                             Candidates with lowest score
     */
    protected function getLowestCandidates(array $candidates): array
    {
        $minimum = 0;
        $minimumCandidates = [];

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

        return $minimumCandidates;
    }

    /**
     * Reject any invalid ballots
     *
     * @return int    Number of rejected ballots
     */
    protected function rejectInvalidBallots(): int
    {
        foreach ($this->ballots as $i => $ballot)
        {
            if (!$this->checkBallotValidity($ballot))
            {
                $this->rejectedBallots[] = clone $ballot;
                unset($this->ballots[$i]);
            }
        }

        return count($this->rejectedBallots);
    }

    /**
     * Check if ballot is valid
     *
     * @param  Ballot &$Ballot  Ballot to test
     * @return bool             True if valid, false if invalid
     */
    private function checkBallotValidity(Ballot &$Ballot): bool
    {
        if (count($ballot->getRanking()) > $this->election->getCandidateCount())
        {
            return false;
        }
        else
        {
            $candidateIds = $this->election->getCandidateIds();

            foreach ($ballot->getRanking() as $i => $candidate)
            {
                if (!in_array($candidate, $candidateIds))
                {
                    return false;
                }
            }
        }

        return true;
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
