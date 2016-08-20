<?php

namespace Michaelc\Voting;

interface Candidate
{
	public function getId(): int;
	public function getState(): int;
	public function setState(int $state);
}
