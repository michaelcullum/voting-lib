<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\Candidate;

class StvCandidateTest extends \PHPUnit_Framework_TestCase
{
    public function testNewCandidateValues()
    {
        $id = 12;
        $candidate = new Candidate($id);
        $this->assertEquals($candidate->getId(), 12);
        $this->assertEquals($candidate->getVotes(), 0.0);
        $this->assertEquals($candidate->getState(), Candidate::RUNNING);
    }
}
