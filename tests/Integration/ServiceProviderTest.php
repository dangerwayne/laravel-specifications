<?php

namespace DangerWayne\Specification\Tests\Integration;

use DangerWayne\Specification\Facades\Specification;
use DangerWayne\Specification\Specifications\Builders\SpecificationBuilder;
use DangerWayne\Specification\Specifications\Common\WhereSpecification;
use DangerWayne\Specification\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_specification_builder(): void
    {
        $specification = $this->app->make('specification');

        $this->assertInstanceOf(SpecificationBuilder::class, $specification);
    }

    public function test_facade_resolves_to_specification_builder(): void
    {
        $builder = Specification::getFacadeRoot();

        $this->assertInstanceOf(SpecificationBuilder::class, $builder);
    }

    public function test_collection_macro_is_registered(): void
    {
        $this->assertTrue(Collection::hasMacro('whereSpecification'));
    }

    public function test_builder_macro_is_registered(): void
    {
        $this->assertTrue(Builder::hasGlobalMacro('whereSpecification'));
    }

    public function test_collection_macro_actually_works(): void
    {
        $collection = collect([
            (object) ['status' => 'active'],
            (object) ['status' => 'inactive'],
        ]);

        $spec = new WhereSpecification('status', '=', 'active');

        $result = $collection->whereSpecification($spec);

        $this->assertCount(1, $result);
    }
}
