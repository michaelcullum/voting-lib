<?php

namespace Tests\Michaelc\Voting;

use Michaelc\Voting\STV\Candidate;
use Michaelc\Voting\STV\ElectionFactory;

class StvCandidateTest extends \PHPUnit_Framework_TestCase
{
    public function testNewCandidateValues()
    {
        $id = 12;
        $candidate = new Candidate($id);
        $this->assertEquals($candidate->getId(), 12);
        $this->assertEquals($candidate->getVotes(), 0.0);
        $this->assertEquals($candidate->getState(), Candidate::RUNNING);

        $this->assertEquals(((string) $candidate), '12'); // Testing __toString() method
    }

    public function testCandidateStates()
    {
        $candidate = new Candidate(random_int(0, 10));

        $candidate->setState(Candidate::ELECTED);
        $this->assertEquals($candidate->getState(), Candidate::ELECTED);

        $candidate->setState(Candidate::RUNNING);
        $this->assertEquals($candidate->getState(), Candidate::RUNNING);

        $candidate->setState(Candidate::DEFEATED);
        $this->assertEquals($candidate->getState(), Candidate::DEFEATED);
    }

    public function testCandidateVotes()
    {
        $candidate = new Candidate(random_int(0, 10));

        $this->assertEquals($candidate->getVotes(), 0.0);

        $candidate->addVotes(4);
        $this->assertEquals($candidate->getVotes(), 4.0);

        $candidate->addVotes(0.11112);
        $this->assertEquals($candidate->getVotes(), 4.11112);
    }

    public function testCandidateFactory()
    {
        $candidates = ['John', 'Finch', 'Lionel', 'Semaine', 'Root'];

        $candidatesCollection = ElectionFactory::createCandidateCollection($candidates);

        $this->assertCount(5, $candidatesCollection);
        $this->assertInstanceOf(Candidate::class, $candidatesCollection[0]);
    }
}
