<?php

namespace Michaelc\Voting\STV;

use Psr\Log\LoggerInterface as Logger;
use Michaelc\Voting\Exception\VotingLogicException as LogicException;
use Michaelc\Voting\Exception\VotingRuntimeException as RuntimeException;

/**
 * This class will calculate the outcome of an election.
 *
 * References throughout are provided as to what is going on.
 * It follows a system similar to Scottish STV and all paragraph
 * references are for: http://www.legislation.gov.uk/sdsi/2011/9780111014639/pdfs/sdsi_9780111014639_en.pdf
 */
class ElectionRunner
{
    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

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
    public $electedCandidates;

    /**
     * Invalid ballots.
     *
     * @var \MichaelC\Voting\STV\Ballot[]
     */
    public $rejectedBallots;

    /**
     * Number of valid ballots.
     *
     * @var int
     */
    public $validBallots;

    /**
     * Number of winners to still be elected (at current stage).
     *
     * @var int
     */
    public $candidatesToElect;

    /**
     * Array of each stage of the election and the vote totals of each candidate
     *
     * @var array
     */
    protected $steps;

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
        $this->steps = [];
    }

    /**
     * Run the election.
     *
     * @return Candidate[] Winning candidates
     */
    public function run()
    {
        $this->logger->notice('Starting to run an election');
        $this->logger->notice(sprintf('There are %d candidates, %d ballots and to be %d winners', $this->election->getCandidateCount(), $this->validBallots, $this->election->getWinnersCount()));

        // Reject invalid ballots, then calculate the quota based on remaining valid ballots
        // p. 46(3) and p 47
        $this->rejectInvalidBallots();
        $this->setQuota();

        // First step of standard allocation of ballots
        // p. 46(1) and 46(2)
        $this->firstStep();

        $candidates = $this->election->getActiveCandidates();

        // All the re-allocation rounds until we have filled all seats or
        // have the same number of seats left to fill and candidates remaining
        // (then elect them).
        $this->processReallocationRounds($candidates);
        // p. 53
        $this->reallocateRemainingVotes($candidates);

        $this->logger->notice('Election complete');

        return $this->election->getElectedCandidates();
    }

    /**
     * Perform the initial vote allocation.
     * p. 46.
     *
     * @return
     */
    protected function firstStep()
    {
        $this->logger->info('Beginning the first step');

        // Allocate all the ballots
        foreach ($this->ballots as $i => $ballot) {
            $this->allocateVotes($ballot);
        }

        $this->logger->notice('Step 1 complete',
            ['candidatesStatus' => $this->election->getCandidatesStatus()]
        );

        $this->steps[1] = $this->election->getCandidatesStatus();

        return;
    }

    /**
     * Process re-allocation rounds (elimination re-allocations and surplus re-allocations).
     *
     * @param Candidate[] $candidates All active candidates to elect
     *
     * @return Candidate[]
     */
    protected function processReallocationRounds(array &$candidates): array
    {
        $counter = 1;
        while (($this->candidatesToElect > 0) && ($this->election->getActiveCandidateCount() > $this->candidatesToElect)) {
            if (!$this->checkCandidates($candidates)) {
                // p. 51(1)
                $this->eliminateCandidates($candidates);
            }

            $candidates = $this->election->getActiveCandidates();

            ++$counter;

            $this->logger->notice("Step $counter complete",
                ['candidatesStatus' => $this->election->getCandidatesStatus()]
            );

            $this->steps[$counter] = $this->election->getCandidatesStatus();
        }

        return $candidates;
    }

    /**
     * Check if any candidates have reached the quota and can be elected.
     *
     * @param Candidate[] $candidates Array of active candidates to check
     *
     * @return bool Whether any candidates were changed to elected
     */
    protected function checkCandidates(array $candidates): bool
    {
        $elected = false;
        $candidatesToElect = [];

        $this->logger->info('Checking if candidates have passed quota');

        if (empty($candidates)) {
            throw new LogicException('There are no more candidates left');
        }

        foreach ($candidates as $i => $candidate) {
            if ($candidate->getState() !== Candidate::RUNNING) {
                throw new LogicException('Candidate is not marked as not running but has not been excluded');
            }

            $votes = $candidate->getVotes();

            $this->logger->debug("Checking candidate ($candidate) with $votes", ['candidate' => $candidate]);

            // p. 48(1)
            // Immediately elect a candidate if they hit the quota
            // We check all the candidates, see who has hit the quota,
            // add them to a queue, then elect those who have hit the quota to prevent
            // surplus allocations pushing a candidate over the quota early.
            if ($votes >= $this->quota) {
                $candidatesToElect[] = $candidate;
                $elected = true;
            }
        }

        // TODO: Put this in a try, and catch the RuntimeError.
        // Then sort by the surplus, and fill available seats.
        // If have same surplus then select randomly (Contary to Scottish STV)
        // p. 50
        $this->electCandidates($candidatesToElect);

        $this->logger->info(('Candidate checking complete. Elected: '.count($candidatesToElect)));

        return $elected;
    }

    /**
     * Elect an array of candidates.
     *
     * @param Candidate[] $candidates Array of candidates to elect
     *
     * @return
     */
    protected function electCandidates(array $candidates)
    {
        if ($this->candidatesToElect < count($candidates)) {
            throw new RuntimeException('Cannot elect candidate as not enough seats to fill');
        }

        foreach ($candidates as $i => $candidate) {
            $this->electCandidate($candidate);
        }

        return;
    }

    /**
     * Allocate the next votes from a Ballot.
     * p. 49.
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
        // p. 49(3)
        // "A divided by B" Where A = the value which is calculated
        // by multiplying the surplus of the transferring candidate
        // by the value of the ballot paper when received by that candidate; and
        // B = the total number of votes credited to that candidate
        $weight = $ballot->setWeight(($currentWeight * $multiplier) / $divisor);

        // Get the next candidate on their ballot paper which has not been assigned
        // a vote this could be the first candidate in round 1
        $candidate = $ballot->getNextChoice();

        $this->logger->debug("Allocating vote of weight $weight to $candidate. Previous weight: $currentWeight", array(
            'ballot' => $ballot,
        ));

        // Check there was a next candidate, if only x candidates where listed where
        // x < the number of candidates standing this will occur.
        if ($candidate !== null) {
            // Allocate those surplus votes. p. 49(1) and p. 49(2)
            $this->election->getCandidate($candidate)->addVotes($weight);
            $ballot->incrementLevelUsed();
            $this->logger->debug('Vote added to candidate');

            // If the candidate is no longer running due to being defeated or
            // elected then we re-allocate their vote again.
            if (!in_array($candidate, $this->election->getActiveCandidateIds())) {
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
     * p. 51(2).
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

        // p. 51(2)(a) - Sort into next preference candidates
        // p. 51(3) - Add votes to candidates
        // p. 51(4) - Use previous weighting
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
     * @param Candidate $candidate
     */
    protected function electCandidate(Candidate $candidate)
    {
        $this->logger->notice("Electing a candidate: $candidate");

        $candidate->setState(Candidate::ELECTED);
        ++$this->electedCandidates;
        --$this->candidatesToElect;

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
     * p. 51.
     *
     * @param Candidate[] $candidates Array of active candidates
     *
     * @return int Number of candidates eliminated
     */
    protected function eliminateCandidates(array $candidates): int
    {
        $minimumCandidates = $this->getLowestCandidates($candidates);
        $count = count($minimumCandidates);

        // p. 52(2)(b) - the returning officer shall decide, by lot, which of those
        // candidates is to be excluded.
        // We do not look back on previous rounds at all as per p. 52(2)(a)
        $minimumCandidate = $minimumCandidates[(array_rand($minimumCandidates))];

        $this->logger->notice(sprintf('There were %d joint lowest candidates,
            %d was randomly selected to be eliminated', $count, $minimumCandidate->getId()));

        $this->transferEliminatedVotes($minimumCandidate);
        $minimumCandidate->setState(Candidate::DEFEATED);

        return count($minimumCandidates);
    }

    /**
     * Get candidates with the lowest number of votes
     * p. 51 and p. 52(1).
     *
     * @param Candidate[] $candidates
     *                                Array of active candidates
     *
     * @return Candidate[]
     *                     Candidates with lowest score
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
     * p. 46(3).
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
        $ranking = $ballot->getRanking();

        if (count($ranking) > $this->election->getCandidateCount()) {
            $this->logger->debug('Invalid ballot - number of candidates', ['ballot' => $ballot]);

            return false;
        } elseif (count($ranking) !== count(array_unique($ranking))) {
            return false;
        } else {
            $candidateIds = $this->election->getCandidateIds();

            foreach ($ranking as $i => $candidate) {
                if (!in_array($candidate, $candidateIds)) {
                    $this->logger->debug('Invalid ballot - invalid candidate');

                    return false;
                }
            }
        }

        $this->logger->debug('Ballot is valid', ['ballot' => $ballot]);

        return true;
    }

    /**
     * Reallocate any remaining votes
     * p. 53.
     *
     * @param Candidate[] $candidates All remaining candidates to elect
     *
     * @return
     */
    protected function reallocateRemainingVotes(array &$candidates)
    {
        if (!empty($candidates)) {
            $this->logger->info('All votes re-allocated. Electing all remaining candidates');

            if ($this->candidatesToElect < count($candidates)) {
                throw new LogicException('Cannot elect candidate as no more seats to fill');
            }

            $this->electCandidates($candidates);
        }

        return;
    }

    /**
     * Get the quota to win.
     * p. 47.
     *
     * TODO: Move this out of this method and use params/args
     *
     * @return int
     */
    public function setQuota(): int
    {
        $this->quota = (int) floor(
            ($this->validBallots /
                ($this->election->getWinnersCount() + 1)
            ) // p. 47 (1)
            + 1); // p. 47 (2)

        $this->logger->info(sprintf('Quota set at %d based on %d winners and %d valid ballots', $this->quota, $this->election->getWinnersCount(), $this->validBallots));

        return $this->quota;
    }

    /**
     * Return the quota for the election
     *
     * @return int
     */
    public function getQuota(): int
    {
        return $this->quota;
    }

    /**
     * Gets an array of the state of all candidates at the end of each
     * phase/step
     *
     * @return array
     */
    public function getSteps(): array
    {
        return $this->steps;
    }
}
