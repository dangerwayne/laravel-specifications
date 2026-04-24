<?php

namespace DangerWayne\Specification\Tests;

use DangerWayne\Specification\Facades\Specification;
use DangerWayne\Specification\Providers\SpecificationServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [SpecificationServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Specification' => Specification::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status')->default('active');
            $table->integer('age')->nullable();
            $table->string('role')->default('user');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }
}
