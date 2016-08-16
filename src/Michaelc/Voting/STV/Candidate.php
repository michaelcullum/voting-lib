<?php

namespace Michaelc\Voting\STV;

class Candidate
{
    const ELECTED = 1;
    const RUNNING = 2;
    const DEFEATED = 3;

    /**
     * Identifier for the candidate
     *
     * @var int
     */
    protected $id;

    /**
     * Number of votes the candidate currently has
     *
     * @var float
     */
    protected $votes;

    /**
     * State of the candidate (use class constants)
     *
     * @var int
     */
    protected $state;

    /**
     * Constructor
     */
    public function __construct(int $id)
    {
        $this->id = $id;
        $this->votes = 0.0;
        $this->state = self::RUNNING;
    }

    /**
     * Gets the Identifier for the candidate.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the Number of votes the candidate currently has.
     *
     * @return float
     */
    public function getVotes(): float
    {
        return $this->votes;
    }

    /**
     * Adds votes to a candidate
     *
     * @param float $votes Number of votes to add
     *
     * @return self
     */
    public function addVotes(float $votes)
    {
        $this->votes += $votes;

        return $this;
    }

    /**
     * Gets the State of the candidate (use class constants).
     *
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Sets the State of the candidate (use class constants).
     *
     * @param int $state the state
     *
     * @return self
     */
    public function setState(int $state)
    {
        $this->state = $state;

        return $this;
    }
}
