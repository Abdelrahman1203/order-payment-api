<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaymentGatewaySeeder::class);
    }

    protected function actingAsJwt(User $user): self
    {
        $token = JWTAuth::fromUser($user);

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
