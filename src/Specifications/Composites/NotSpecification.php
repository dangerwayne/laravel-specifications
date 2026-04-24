<?php

namespace DangerWayne\Specification\Specifications\Composites;

use DangerWayne\Specification\Contracts\SpecificationInterface;
use DangerWayne\Specification\Specifications\AbstractSpecification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        // Check if whereNot method exists (Laravel 10+)
        if (method_exists($query, 'whereNot')) {
            return $query->whereNot(function ($query) {
                $this->specification->toQuery($query);
            });
        }

        // For Laravel 9, we need to manually negate the conditions
        // Since whereNot is not available, we'll use a subquery approach
        $model = $query->getModel();
        $table = $model->getTable();
        $keyName = $model->getKeyName();

        // Create a subquery that will contain the conditions to negate
        /** @var Builder $result */
        $result = $query->whereNotExists(function (\Illuminate\Database\Query\Builder $subQuery) use ($table, $keyName, $model) {
            $subQuery->select(DB::raw(1))
                ->from($table.' as not_sub')
                ->whereColumn('not_sub.'.$keyName, $table.'.'.$keyName);

            // Create a new Eloquent builder instance for applying the specification
            /** @var Builder $tempBuilder */
            $tempBuilder = $model->newQuery();

            // Apply the specification to get the conditions
            $this->specification->toQuery($tempBuilder);

            // Extract and apply the where conditions to the subquery
            $queryBuilder = $tempBuilder->getQuery();
            if (! empty($queryBuilder->wheres)) {
                foreach ($queryBuilder->wheres as $where) {
                    $subQuery->wheres[] = $where;
                }

                // Also copy the bindings
                if (! empty($queryBuilder->bindings['where'])) {
                    foreach ($queryBuilder->bindings['where'] as $binding) {
                        $subQuery->addBinding($binding, 'where');
                    }
                }
            }
        });

        return $result;
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
