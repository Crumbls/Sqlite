<?php

namespace Crumbls\Sqlite\Models;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class Table extends Model {

    use \Sushi\Sushi;

    protected ?string $_scope = null;

    /**
     * Scope a query to only include popular users.
     */
    public function scopeSource(Builder $query, string $connection): void
    {
        $this->_scope = $connection;

        /**
         * Purge old data.
         */
        $schemaBuilder = static::resolveConnection()->getSchemaBuilder();
        $tableName = $this->getTable();
        $schemaBuilder->dropIfExists($tableName);

        $this->migrate();
    }

    /**
     * Get a list of tables.
     * @return mixed
     */
    public function getRows()
    {
        if (!$this->_scope) {
            return [];
        }

        return Cache::remember(__METHOD__, 1, function() {
            $tables = array_map(function(\stdClass $table) {
                $table->count = DB::connection($this->_scope)->table($table->name)->count();
                return (array)$table;
            }, DB::connection($this->_scope)->select("SELECT name,type FROM sqlite_master WHERE type='table' ORDER BY name"));
            return $tables;
        });
    }

    protected function createTableSafely(string $tableName, \Closure $callback)
    {
        /** @var \Illuminate\Database\Schema\SQLiteBuilder $schemaBuilder */
        $schemaBuilder = static::resolveConnection()->getSchemaBuilder();

        try {
            $schemaBuilder->create($tableName, $callback);
        } catch (QueryException $e) {
            if (Str::contains($e->getMessage(), [
                'already exists (SQL: create table',
                sprintf('table "%s" already exists', $tableName),
            ])) {
                // This error can happen in rare circumstances due to a race condition.
                // Concurrent requests may both see the necessary preconditions for
                // the table creation, but only one can actually succeed.
                return;
            }

            throw $e;
        }
    }
}
