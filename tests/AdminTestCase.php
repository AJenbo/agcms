<?php namespace Tests;

use App\Models\User;
use App\Services\OrmService;

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
        /** @var OrmService */
        $orm = app(OrmService::class);
        /** @var User */
        $user = $orm->getOne(User::class, 1);
        $this->actingAs($user);
    }
}
