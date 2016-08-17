<?php

namespace Michaelc\Voting\STV;

class Ballot
{
    /**
     * Ranking of candidates ids.
     *
     * @var int[]
     */
    protected $ranking;

    /**
     * The current weighting or value of this person's vote.
     *
     * @var float
     */
    protected $weight;

    /**
     * The current preference in use from this ballot.
     *
     * @var int
     */
    protected $levelUsed;

    /**
     * Constructor.
     *
     * @param int[] $ranking The ranking of candidates Key being ranking,
     *                       value being a candidate id. Zero-indexed (Key
     *                       0 for first choice)
     */
    public function __construct(array $ranking)
    {
        $this->weight = 1.0;
        $this->ranking = $ranking;
        $this->levelUsed = -1;
    }

    /**
     * Gets the Ranking of candidates ids.
     *
     * @return array
     */
    public function getRanking(): array
    {
        return $this->ranking;
    }

    /**
     * Gets the The current weighting or value of this person's vote.
     *
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * Sets the The current weighting or value of this person's vote.
     *
     * @param float $weight The weight
     *
     * @return float $weight    The inputted weight
     */
    public function setWeight(float $weight): float
    {
        $this->weight = $weight;

        return $weight;
    }

    /**
     * Gets the the current preference in use from this ballot.
     *
     * @return int
     */
    public function getLevelUsed(): int
    {
        return $this->levelUsed;
    }

    /**
     * Sets the the current preference in use from this ballot.
     *
     * @return int
     */
    public function incrementLevelUsed(): int
    {
        ++$this->levelUsed;

        return $this->levelUsed;
    }

    public function getLastChoice()
    {
        $level = $this->levelUsed;

        if (empty($this->ranking[$level])) {
            return;
        }

        return $this->ranking[$level];
    }

    public function getNextChoice()
    {
        $level = $this->levelUsed + 1;

        if (empty($this->ranking[$level])) {
            return;
        }

        return $this->ranking[$level];
    }
}
