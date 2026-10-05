<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AccountListQuery
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  list<string>  $fields
     * @return Collection<int, array<string, mixed>>
     */
    public static function filter(Collection $rows, ?string $search, array $fields): Collection
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $rows->values();
        }

        $needle = Str::lower($search);

        return $rows
            ->filter(function (array $row) use ($needle, $fields): bool {
                foreach ($fields as $field) {
                    $value = $row[$field] ?? null;
                    if ($value === null || $value === '') {
                        continue;
                    }

                    if (Str::contains(Str::lower((string) $value), $needle)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    /**
     * @param  Collection<int, mixed>  $rows
     */
    public static function paginate(Collection $rows, int $perPage = 15, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = max(5, min(50, $perPage));
        $page = max(1, (int) request()->input($pageName, 1));
        $total = $rows->count();
        $slice = $rows->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ],
        );
    }
}
