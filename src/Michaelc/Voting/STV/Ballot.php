<?php

namespace Michaelc/Voting/STV;

class Ballot
{
	protected $ranking;
	protected $weight;
	protected $levelUsed;

	public function __construct(array $ranking)
	{
	    $this->weight = 1;
	    $this->ranking = $ranking;
    }
}
