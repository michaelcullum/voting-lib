<?php

namespace Michaelc\Voting;

use Psr\Log\LoggerInterface as Logger;
use Michaelc\Voting\Election;

interface ElectionFactory
{
	public static function createBallotCollection(array $rankings): array;

	public static function createCandidateCollection(array $candidates): array;

	public static function createCandidateBallotCollection(array $candidates, array $rankings): array;

	public static function createElection(array $candidates, array $rankings, int $winnerCount, bool $ids = true): Election;
}
