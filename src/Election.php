<?php

namespace Michaelc\Voting;

interface Election
{
	public function __construct(array $candidates, array $ballots, int $winnersCount = 1);

	public function getCandidate(int $id): Candidate;

	public function getCandidateCount(): int;

	public function getActiveCandidates(): array;

	public function getActiveCandidateIds(): array;

	public function getElectedCandidates(): array;

	public function getDefeatedCandidates(): array;

	public function getStateCandidates(int $state): array;

	public function getActiveCandidateCount(): int;

	public function getCandidateIds(): array;

	public function getCandidates(): array;

	public function getBallots(): array;

	public function getNumBallots(): int;

	public function getCandidatesStatus(): array;
}
