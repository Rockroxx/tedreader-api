<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class SearchTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGetSuccess()
    {
        $response = $this->json('GET','/')->response->original;

        self::assertEquals(20, count($response['results']));
    }

    public function testPostSuccess()
    {
        $response = $this->json('POST','/')->response->original;

        self::assertEquals(20, count($response['results']));
    }

    public function testTake10Success()
    {
        $response = $this->json('POST','/', ['take' => 10])->response->original;

        self::assertEquals(10, count($response['results']));
    }
    public function testSearchSuccess()
    {
        $response = $this->json('POST','/', ['search' => 'European'])->response->original;

        self::assertTrue(count($response['results']) > 0);
    }
}
