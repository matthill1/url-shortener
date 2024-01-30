# Url Shortener

Various changes were made to make this run;

1 - The docker-compose file was changed to include 
    platform: linux/amd64

    This was required to get it to run on Apple MacBook Pro M1 Max.

    See git SHA 2aedc647ff0f6ae5ab5349565c119d8158b6d45a

2 - The included migrate function apparently caused problems due to a COLLATE statement which seemingly didn't match the MySQL Version. See git SHA e12c22eab6d5d75a2287db1f27a4f527c5cfb41d

# Tests

tests can be ran from;

vendor/bin/phpunit ./tests/ServerTests.php

Unfortunately this is not fully inclusive of functionality. 
This test file is missing re-direct tests due to unfamiliarity with the library, and time constraints.
Manual re-direct tests performed with cURL appear successful.