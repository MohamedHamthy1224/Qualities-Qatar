<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentNumber
{
    /** Serialize document creation across customers without adding a counter table. */
    public static function transaction(callable $callback): mixed
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            return $connection->transaction($callback, 3);
        }

        $lock = 'qualities-documents-'.substr(hash('sha256', $connection->getDatabaseName()), 0, 40);
        $result = $connection->selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lock]);
        if ((int) $result->acquired !== 1) {
            throw ValidationException::withMessages(['document' => 'Another document is being saved. Please try again.']);
        }

        try {
            return $connection->transaction($callback, 3);
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
        }
    }

    /** @param class-string<Model> $modelClass */
    public static function next(string $modelClass, string $column, string $prefix): string
    {
        $highest = 0;
        foreach ($modelClass::where($column, 'like', $prefix.'%')->pluck($column) as $number) {
            $suffix = substr($number, strlen($prefix));
            if (ctype_digit($suffix)) {
                $highest = max($highest, (int) $suffix);
            }
        }

        return $prefix.str_pad((string) ($highest + 1), 3, '0', STR_PAD_LEFT);
    }
}
