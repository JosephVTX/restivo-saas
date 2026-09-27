<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Base for every `/api/v1` JSON controller.
 *
 * Provides the repeated plumbing (pagination size, index query with
 * spatie/query-builder, tenant-scoped uuid lookup) so controllers stay thin
 * and consistent. Extend this instead of re-implementing it per controller.
 */
abstract class ApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Clamp the requested page size between 1 and $max.
     */
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min(max($request->integer('per_page', $default), 1), $max);
    }

    /**
     * Build a filtered/sorted/paginated index query.
     *
     * @param  class-string<Model>  $model
     * @param  array<int, AllowedFilter|string>  $filters
     * @param  array<int, AllowedSort|string>  $sorts
     * @param  array<int, string>  $withCount
     * @param  array<int, string>  $with
     */
    protected function indexQuery(
        string $model,
        Request $request,
        array $filters = [],
        array $sorts = ['created_at'],
        array $withCount = [],
        array $with = [],
        string $defaultSort = '-created_at',
    ): LengthAwarePaginator {
        $query = QueryBuilder::for($model)
            ->allowedFilters(...$filters)
            ->allowedSorts(...$sorts)
            ->defaultSort($defaultSort);

        if ($withCount !== []) {
            $query->withCount($withCount);
        }

        if ($with !== []) {
            $query->with($with);
        }

        return $query->paginate($this->perPage($request))->withQueryString();
    }

    /**
     * Build the shared `filter[search]` callback across the given columns.
     *
     * @param  array<int, string>  $columns
     */
    protected function searchFilter(array $columns): AllowedFilter
    {
        return AllowedFilter::callback('search', function (Builder $query, mixed $value) use ($columns): void {
            $query->where(function (Builder $query) use ($columns, $value): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%'.$value.'%');
                }
            });
        });
    }

    /**
     * Resolve a model by uuid applying its global scopes (tenant isolation).
     *
     * Tenant models must NOT use implicit route-model binding because
     * SubstituteBindings runs before resolve.tenant. Use this instead.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return TModel
     */
    protected function findByUuid(string $model, string $uuid): Model
    {
        return $model::query()->where('uuid', $uuid)->firstOrFail();
    }
}
