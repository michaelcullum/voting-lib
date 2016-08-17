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

    public function testBallotWeighting()
    {
        $ranking = [3, 2, 1];
        $ballot = new Ballot($ranking);

        $this->assertEquals($ballot->getWeight(), 1.0);

        $ballot->setWeight(($ballot->getWeight() * 1.0) / 1.0);
        $this->assertEquals($ballot->getWeight(), 1.0);

        $ballot->setWeight(($ballot->getWeight() * 5.0) / 20);
        $this->assertEquals($ballot->getWeight(), 0.25);

        $ballot->setWeight(($ballot->getWeight() * 3) / 18);
        $this->assertEquals($ballot->getWeight(), 0.041666666666667);
    }

    public function testBallotLevels()
    {
        $ranking = [9, 6, 4, 1, 7];
        $ballot = new Ballot($ranking);

        $this->assertEquals($ballot->getLevelUsed(), -1);

        $ballot->incrementLevelUsed();
        $this->assertEquals($ballot->getLevelUsed(), 0);
        $ballot->incrementLevelUsed();
        $this->assertEquals($ballot->getLevelUsed(), 1);
        $ballot->incrementLevelUsed();
        $this->assertEquals($ballot->getLevelUsed(), 2);
    }

    public function testChoiceRetrival()
    {
        $ranking = [9, 6, 4, 1, 7];
        $ballot = new Ballot($ranking);

        $this->assertNull($ballot->getLastChoice());
        $this->assertNotNull($ballot->getNextChoice());

        $this->assertEquals(9, $ballot->getNextChoice());
        $ballot->incrementLevelUsed();

        foreach ($ranking as $rank => $candidate)
        {
            $this->assertEquals($candidate, $ballot->getLastChoice());

            if ($rank != 4)
            {
                $this->assertEquals($ranking[($rank+1)], $ballot->getNextChoice());
            }

            $ballot->incrementLevelUsed();
        }

        $this->assertNull($ballot->getNextChoice());
    }
}
