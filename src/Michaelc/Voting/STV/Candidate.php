<?php

namespace Michaelc\Voting\STV;

class Candidate
{
	const ELECTED = 1;
  	const RUNNING = 2;
  	const DEFEATED = 3;

  	protected $id;
	protected $votes;
	protected $surplus;
	protected $state;

	function __construct()
	{
		$this->votes = 0;
		$this->surplus = 0;
		$this->state = self::RUNNING;
	}
}
