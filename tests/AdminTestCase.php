<?php namespace Tests;

use App\Models\User;

abstract class AdminTestCase extends TestCase
{
    /**
     * Initiate the database, config and application.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $user = app('orm')->getOne(User::class, 1);
        $this->actingAs($user);
    }
}
