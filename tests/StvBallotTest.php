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

        foreach ($ranking as $rank => $candidate) {
            $this->assertEquals($candidate, $ballot->getLastChoice());

            if ($rank != 4) {
                $this->assertEquals($ranking[($rank + 1)], $ballot->getNextChoice());
            }

            $ballot->incrementLevelUsed();
        }

        $this->assertNull($ballot->getNextChoice());
    }

    public static function getBallotSample()
    {
        $ballots = [];

        $ballots[] = new Ballot([1, 5, 10, 15]);
        $ballots[] = new Ballot([2, 3, 10, 12, 6, 1, 9, 12, 11, 4, 14]);
        $ballots[] = new Ballot([3, 6, 7, 12, 15, 2, 9, 4, 10, 11, 13, 5, 14]);
        $ballots[] = new Ballot([4, 9, 2, 3, 14, 12, 7, 13, 11, 10, 12, 14, 1, 5, 6]);
        $ballots[] = new Ballot([5, 2, 11, 9, 4, 8, 7, 13, 14]);
        $ballots[] = new Ballot([6, 11, 8, 12, 5, 7, 13, 1, 10, 15, 14, 3, 2, 4, 9]);
        $ballots[] = new Ballot([7, 14]);
        $ballots[] = new Ballot([8, 3, 4, 10, 11]);
        $ballots[] = new Ballot([9, 7, 6, 3, 5, 2, 10]);
        $ballots[] = new Ballot([10, 11, 6, 12, 7, 8, 1, 2, 8, 6, 4, 9, 1, 15, 14]);
        $ballots[] = new Ballot([11, 13, 8, 10, 14, 7, 1]);
        $ballots[] = new Ballot([12, 13, 2, 9, 15, 1, 14]);
        $ballots[] = new Ballot([13, 4, 2, 7, 6, 8, 9, 5, 14, 12, 1, 15]);
        // 14 and 15 have no first place votes

        $ballots[] = new Ballot([1, 14, 11, 15, 2, 4, 12, 8, 7, 6, 5]);
        $ballots[] = new Ballot([1, 14, 10, 12, 5, 6, 3, 2, 11]);
        $ballots[] = new Ballot([1, 3, 2, 9, 7, 8, 11, 4, 15, 13, 5, 14, 12]);
        $ballots[] = new Ballot([1, 4, 10, 15]);
        $ballots[] = new Ballot([1, 14, 10, 15]);
        $ballots[] = new Ballot([1, 4, 10, 15]);
        // 1 easily meets the quota. 3 votes
        // are re-allocated to 14 which would
        // otherwise have 0 votes.

        $ballots[] = new Ballot([9, 1, 12]);

        $ballots[] = new Ballot([12, 4, 10, 15]);
        $ballots[] = new Ballot([12, 4, 10, 15]);
        $ballots[] = new Ballot([12, 10, 10, 15]);
        $ballots[] = new Ballot([12, 4, 10, 15]);
        // 12 meets the quota in the first round

        $ballots[] = new Ballot([2, 14, 9, 13]);
        $ballots[] = new Ballot([4]); // Single vote for someone who will be elected
        $ballots[] = new Ballot([6, 13, 8, 12, 11, 7, 15, 9]);
        $ballots[] = new Ballot([8, 13, 9, 6, 15, 3, 4, 8]);
        $ballots[] = new Ballot([10, 6, 14, 8]);

        // Identical vote 1
        $ballots[] = new Ballot([13, 12, 5, 10, 7, 6, 4, 2, 11, 9, 14, 15, 8, 3, 1]);

        // All of these now are full ballots
        $ballots[] = new Ballot([1, 5, 7, 11, 3, 6, 9, 10, 12, 14, 4, 2, 13, 8, 15]);
        $ballots[] = new Ballot([5, 2, 15, 4, 14, 3, 13, 10, 12, 1, 9, 6, 7, 11, 8]);
        $ballots[] = new Ballot([8, 11, 12, 6, 14, 7, 9, 1, 3, 2, 13, 5, 15, 10, 4]);
        $ballots[] = new Ballot([11, 14, 5, 8, 13, 15, 7, 9, 1, 4, 12, 6, 10, 3, 2]);
        $ballots[] = new Ballot([13, 9, 7, 10, 8, 12, 3, 14, 15, 6, 2, 4, 5, 11, 1]);
        $ballots[] = new Ballot([3, 4, 5, 6, 12, 11, 9, 14, 15, 2, 7, 13, 10, 1, 8]);
        $ballots[] = new Ballot([6, 8, 4, 9, 11, 3, 12, 1, 5, 15, 13, 10, 7, 14, 2]);

        // Identical vote 2
        $ballots[] = new Ballot([13, 12, 5, 10, 7, 6, 4, 2, 11, 9, 14, 15, 8, 3, 1]);

        $ballots[] = new Ballot([20, 5, 10, 15]); // Invalid ballot
        $ballots[] = new Ballot([19, 5, 5, 10, 15]); // Invalid ballot
        $ballots[] = new Ballot([1, 6, 2, 12, 8, 15, 3, 7, 4, 13, 14, 9, 10, 12, 5, 11, 12]); // Invalid ballot

        return $ballots;
    }
}
