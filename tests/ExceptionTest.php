<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\Exception\VotingLogicException as LogicException;
use Michaelc\Voting\Exception\VotingRuntimeException as RuntimeException;

class ExceptionsTest extends \PHPUnit_Framework_TestCase
{
	public function testLogicException()
	{
		$this->expectException(LogicException::class);

		throw new LogicException('Logic Exception');
	}

	public function testRuntimeException()
	{
		$this->expectException(RuntimeException::class);

		throw new RuntimeException('Runtime Exception');
	}
}
