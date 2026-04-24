<?php

namespace DangerWayne\Specification\Specifications\Composites;

use DangerWayne\Specification\Contracts\SpecificationInterface;
use DangerWayne\Specification\Specifications\AbstractSpecification;
use Illuminate\Database\Eloquent\Builder;

class NotSpecification extends AbstractSpecification
{
    public function __construct(
        private SpecificationInterface $specification
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return ! $this->specification->isSatisfiedBy($candidate);
    }

    public function toQuery(Builder $query): Builder
    {
        return $query->whereNot(function ($query) {
            $this->specification->toQuery($query);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getParameters(): array
    {
        return [
            'specification' => $this->specification->getCacheKey(),
        ];
    }
}
