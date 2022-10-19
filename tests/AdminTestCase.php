<?php

namespace Tests;

use App\Models\User;
use App\Services\OrmService;
use Exception;

abstract class AdminTestCase extends TestCase
{
    /**
     * Initiate the database, config and application.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $user = app(OrmService::class)->getOne(User::class, 1);
        if ($user === null) {
            throw new Exception('Failed to load user from database');
        }
        $this->actingAs($user);
    }
}
