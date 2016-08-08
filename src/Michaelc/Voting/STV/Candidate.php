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
         * @var integer
         */
        protected $id;

        /**
         * Number of votes the candidate currently has
         *
         * @var float
         */
    protected $votes;

    /**
     * Number of surplus votes the candidate has to be re-allocated
     *
     * @var float
     */
    protected $surplus;

    /**
     * State of the candidate (use class constants)
     *
     * @var integer
     */
    protected $state;

    /**
     * Constructor
     */
    public function __construct(int $id)
    {
        $this->id = $id;
        $this->votes = 0.0;
        $this->surplus = 0.0;
        $this->state = self::RUNNING;
    }

    /**
     * Gets the Identifier for the candidate.
     *
     * @return integer
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
     * Sets the Number of votes the candidate currently has.
     *
     * @param float $votes the votes
     *
     * @return self
     */
    protected function setVotes(float $votes)
    {
        $this->votes = $votes;

        return $this;
    }

    /**
     * Gets the Number of surplus votes the candidate has to be re-allocated.
     *
     * @return float
     */
    public function getSurplus(): float
    {
        return $this->surplus;
    }

    /**
     * Sets the Number of surplus votes the candidate has to be re-allocated.
     *
     * @param float $surplus the surplus
     *
     * @return self
     */
    protected function setSurplus(float $surplus)
    {
        $this->surplus = $surplus;

        return $this;
    }

    /**
     * Gets the State of the candidate (use class constants).
     *
     * @return integer
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Sets the State of the candidate (use class constants).
     *
     * @param integer $state the state
     *
     * @return self
     */
    protected function setState(int $state)
    {
        $this->state = $state;

        return $this;
    }
}
