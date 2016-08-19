<?php

namespace Michaelc\Voting\STV;

use Psr\Log\LoggerInterface as Logger;
use Michaelc\Voting\Exception\VotingLogicException as LogicException;
use Michaelc\Voting\Exception\VotingRuntimeException as RuntimeException;

class VoteHandler
{
    /**
     * Election object.
     *
     * @var \Michaelc\Voting\STV\Election;
     */
    protected $election;

    /**
     * Array of all ballots in election.
     *
     * @var \MichaelC\Voting\STV\Ballot[]
     */
    protected $ballots;

    /**
     * Quota of votes needed for a candidate to be elected.
     *
     * @var int
     */
    protected $quota;

    /**
     * Number of candidates elected so far.
     *
     * @var int
     */
    protected $electedCandidates;

    /**
     * Invalid ballots.
     *
     * @var \MichaelC\Voting\STV\Ballot[]
     */
    protected $rejectedBallots;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $validBallots;

    protected $candidatesToElect;

    /**
     * Constructor.
     *
     * @param Election $election
     */
    public function __construct(Election $election, Logger $logger)
    {
        $this->logger = $logger;
        $this->election = $election;
        $this->ballots = $this->election->getBallots();
        $this->rejectedBallots = [];
        $this->candidatesToElect = $this->election->getWinnersCount();

        $this->validBallots = $this->election->getNumBallots();
    }

    /**
     * Run the election.
     *
     * @return \MichaelC\Voting\STV\Candidate[] Winning candidates
     */
    public function run()
    {
        $this->logger->notice('Starting to run an election');
        $this->logger->notice(sprintf('There are %d candidates, %d ballots and to be %d winners', $this->election->getCandidateCount(), $this->validBallots, $this->election->getWinnersCount()));

        $this->rejectInvalidBallots();
        $this->quota = $this->getQuota();

        $this->firstStep();

        $candidates = $this->election->getActiveCandidates();

        while (($this->candidatesToElect > 0) && ($this->election->getActiveCandidateCount() > $this->candidatesToElect)) {
            if (!$this->checkCandidates($candidates)) {
                $this->eliminateCandidates($candidates);
            }

            $candidates = $this->election->getActiveCandidates();
        }

        if (!empty($candidates))
        {
            $this->logger->info('All votes re-allocated. Electing all remaining candidates');

            foreach ($candidates as $i => $candidate) {
                $this->electCandidate($candidate);
            }
        }


        $this->logger->notice('Election complete');

        return $this->election->getElectedCandidates();
    }

    /**
     * Perform the initial vote allocation.
     *
     * @return
     */
    protected function firstStep()
    {
        $this->logger->info('Beginning the first step');

        foreach ($this->ballots as $i => $ballot) {
            $this->logger->debug("Processing ballot $i in stage 1");

            $this->allocateVotes($ballot);
        }

        $this->logger->notice('First step complete',
            ['candidatesStatus' => $this->election->getCandidatesStatus()]
        );

        return;
    }

    /**
     * Check if any candidates have reached the quota and can be elected.
     *
     * @param array $candidates Array of active candidates to check
     *
     * @return bool Whether any candidates were changed to elected
     */
    protected function checkCandidates(array $candidates): bool
    {
        $elected = false;
        $candidatesToElect = [];

        $this->logger->info('Checking if candidates have passed quota');

        if (empty($candidates))
        {
            throw new LogicException("There are no more candidates left");
        }

        foreach ($candidates as $i => $candidate) {
            $votes = $candidate->getVotes();

            $this->logger->debug("Checking candidate ($candidate) with $votes", ['candidate' => $candidate]);

            if ($votes >= $this->quota) {
                $candidatesToElect[] = $candidate;
                $elected = true;
            }
        }

        foreach ($candidatesToElect as $i => $candidate) {
            $this->electCandidate($candidate);
        }

        $this->logger->info(('Candidate checking complete. Elected: ' . count($candidatesToElect)));

        return $elected;
    }

    /**
     * Allocate the next votes from a Ballot.
     *
     * @param Ballot $ballot     The ballot to allocate the votes from
     * @param float  $multiplier Number to multiply the weight by (surplus)
     * @param float  $divisor    The divisor of the weight (Total number of
     *                           candidate votes)
     *
     * @return Ballot The same ballot passed in modified
     */
    protected function allocateVotes(Ballot $ballot, float $multiplier = 1.0, float $divisor = 1.0): Ballot
    {
        $currentWeight = $ballot->getWeight();
        $weight = $ballot->setWeight(($currentWeight * $multiplier) / $divisor);
        $candidate = $ballot->getNextChoice();

        // TODO: Check if candidate is withdrawn

        $this->logger->debug("Allocating vote of weight $weight to $candidate. Previous weight: $currentWeight", array(
            'ballot' => $ballot,
        ));

        if ($candidate !== null) {
            $this->election->getCandidate($candidate)->addVotes($weight);
            $ballot->incrementLevelUsed();
            $this->logger->debug('Vote added to candidate');

            // If the candidate is no longer running due to being defeated or
            // elected then we re-allocate their vote again.
            if (!in_array($candidate, $this->election->getActiveCandidateIds()))
            {
                $this->allocateVotes($ballot);
            }
        }

        return $ballot;
    }

    /**
     * Transfer the votes from one candidate to other candidates.
     *
     * @param float     $surplus   The number of surplus votes to transfer
     * @param Candidate $candidate The candidate being elected to transfer
     *                             the votes from
     *
     * @return
     */
    protected function transferSurplusVotes(float $surplus, Candidate $candidate)
    {
        $totalVotes = $candidate->getVotes();
        $candidateId = $candidate->getId();

        $this->logger->info('Transfering surplus votes');

        foreach ($this->ballots as $i => $ballot) {
            if ($ballot->getLastChoice() == $candidateId) {
                $this->allocateVotes($ballot, $surplus, $totalVotes);
            }
        }

        return;
    }

    /**
     * Transfer the votes from one eliminated candidate to other candidates.
     *
     * @param Candidate $candidate Candidate being eliminated to transfer
     *                             the votes from
     *
     * @return
     */
    protected function transferEliminatedVotes(Candidate $candidate)
    {
        $candidateId = $candidate->getId();

        $votes = $candidate->getVotes();

        $this->logger->info("Transfering votes from eliminated candidate ($candidate) with $votes votes");

        foreach ($this->ballots as $i => $ballot) {
            if ($ballot->getLastChoice() == $candidateId) {
                $this->allocateVotes($ballot);
            }
        }

        return;
    }

    /**
     * Elect a candidate after they've passed the threshold.
     *
     * @param \Michaelc\Voting\STV\Candidate $candidate
     */
    protected function electCandidate(Candidate $candidate)
    {
        $this->logger->notice("Electing a candidate: $candidate");

        $candidate->setState(Candidate::ELECTED);
        $this->electedCandidates++;
        $this->candidatesToElect--;

        if ($this->electedCandidates < $this->election->getWinnersCount()) {
            $surplus = $candidate->getVotes() - $this->quota;
            if ($surplus > 0) {
                $this->transferSurplusVotes($surplus, $candidate);
            } else {
                $this->logger->notice("No surplus votes from $candidate to reallocate");
            }

        }

        return;
    }

    /**
     * Eliminate the candidate with the lowest number of votes
     * and reallocated their votes.
     *
     * @param \Michaelc\Voting\STV\Candidate[] $candidates Array of active candidates
     *
     * @return int Number of candidates eliminated
     */
    protected function eliminateCandidates(array $candidates): int
    {
        $minimumCandidates = $this->getLowestCandidates($candidates);
        $count = count($minimumCandidates);

        $minimumCandidate = $minimumCandidates[(array_rand($minimumCandidates))];

        $this->logger->notice(sprintf("There were %d joint lowest candidates,
            %d was randomly selected to be eliminated", $count, $minimumCandidate->getId()));

        $this->transferEliminatedVotes($minimumCandidate);
        $minimumCandidate->setState(Candidate::DEFEATED);

        return count($minimumCandidates);
    }

    /**
     * Get candidates with the lowest number of votes.
     *
     * @param \Michaelc\Voting\STV\Candidate[] $candidates
     *                                                     Array of active candidates
     *
     * @return \Michaelc\Voting\STV\Candidate[]
     *                                          Candidates with lowest score
     */
    public function getLowestCandidates(array $candidates): array
    {
        $minimum = count($this->election->getBallots());
        $minimumCandidates = [];

        foreach ($candidates as $i => $candidate) {
            if ($candidate->getVotes() < $minimum) {
                $minimum = $candidate->getVotes();
                unset($minimumCandidates);
                $minimumCandidates[] = $candidate;
            } elseif ($candidate->getVotes() == $minimum) {
                $minimumCandidates[] = $candidate;
                $this->logger->info("Calculated as a lowest candidate: $candidate");
            }
        }

        return $minimumCandidates;
    }

    /**
     * Reject any invalid ballots.
     *
     * @return int Number of rejected ballots
     */
    protected function rejectInvalidBallots(): int
    {
        foreach ($this->ballots as $i => $ballot) {
            if (!$this->checkBallotValidity($ballot)) {
                $this->rejectedBallots[] = clone $ballot;
                unset($this->ballots[$i]);
            }
        }

        $count = count($this->rejectedBallots);

        $this->logger->notice("Found $count rejected ballots");

        $this->validBallots = $this->validBallots - $count;

        return $count;
    }

    /**
     * Check if ballot is valid.
     *
     * @param Ballot $ballot Ballot to test
     *
     * @return bool True if valid, false if invalid
     */
    public function checkBallotValidity(Ballot $ballot): bool
    {
        if (count($ballot->getRanking()) > $this->election->getCandidateCount()) {
            $this->logger->debug('Invalid ballot - number of candidates', ['ballot' => $ballot]);

            return false;
        } else {
            $candidateIds = $this->election->getCandidateIds();

            foreach ($ballot->getRanking() as $i => $candidate) {
                if (!in_array($candidate, $candidateIds)) {
                    $this->logger->debug('Invalid ballot - invalid candidate');

                    return false;
                }
            }
        }

        // TODO: Check for candidates multiple times on the same ballot paper

        $this->logger->debug('Ballot is valid', ['ballot' => $ballot]);

        return true;
    }

    /**
     * Get the quota to win.
     *
     * @return int
     */
    public function getQuota(): int
    {
        $quota = floor(
            ($this->validBallots /
                ($this->election->getWinnersCount() + 1)
            )
            + 1);

        $this->logger->info(sprintf("Quota set at %d based on %d winners and %d valid ballots", $quota, $this->election->getWinnersCount(), $this->validBallots));

        return $quota;
    }
}
