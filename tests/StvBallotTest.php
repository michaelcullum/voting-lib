<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\Ballot;

class StvBallotTest extends \PHPUnit_Framework_TestCase
{
    public function testRawBallotValues()
    {
        $ranking = [4, 5, 8];
        $ballot = new Ballot($ranking);
        $this->assertEquals($ballot->getRanking(), $ranking);
        $this->assertEquals($ballot->getWeight(), 1.0);
        $this->assertEquals($ballot->getLevelUsed(), -1);
    }
}
