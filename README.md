# Voting Library

Currently this library just contains the code for an STV election following rules
similar to that of [Scottish STV](http://www.legislation.gov.uk/sdsi/2011/9780111014639/pdfs/sdsi_9780111014639_en.pdf).

Documentation and stuff doesn't quite exist, and most of the STV working is done in
`ElectionRunner.php`. References are provided throughout the codebase in comments with explanations on what it's doing with paragraph references to Scottish STV rules.

Scottish STV is used because it is well defined as it is defined in UK legislature.

The primary differences are its inability to look back on previous rounds to brea
ties, so will select a random choice (which is the method Scottish STV always
suggests in an event where two candidates are tied in all previous rounds). These
elements and use of 'random' means that sometimes running an election count twice
will produce slightly different results.

Code coverage is pretty good but the `ElectionRunner` class could use a little
more unit testing.

Scrutinizer: [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/michaelcullum/voting-lib/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/michaelcullum/voting-lib/?branch=master)

Code Coverage: [![Code Coverage](https://scrutinizer-ci.com/g/michaelcullum/voting-lib/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/michaelcullum/voting-lib/?branch=master)

Travis CI: [![Build Status](https://travis-ci.org/michaelcullum/voting-lib.svg?branch=master)](https://travis-ci.org/michaelcullum/voting-lib)
