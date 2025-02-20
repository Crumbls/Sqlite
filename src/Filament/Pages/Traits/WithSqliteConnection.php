<?php

namespace Crumbls\Sqlite\Filament\Pages\Traits;

use Crumbls\Sqlite\Models\Database;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Trait WithSqliteConnection
 *
 * Provides SQLite connection management functionality for Filament pages.
 * Handles connection name storage and database record retrieval.
 */
trait WithSqliteConnection
{
    /**
     * The name of the current SQLite connection.
     *
     * @var string
     */
    public string $connectionName;

    /**
     * The cached Database model instance.
     *
     * @var Database|null
     */
    protected ?Database $_connectionRecord = null;

    /**
     * Get the current connection name.
     *
     * @return string
     */
    public function getConnectionName(): string
    {
        if (!isset($this->connectionName)) {
            dd($this);
        }
        return $this->connectionName;
    }

    /**
     * Set the current connection name.
     *
     * @param string $connection
     * @return void
     */
    public function setConnectionName(string $connection): void
    {
        $this->connectionName = $connection;
    }

    /**
     * Get or retrieve the Database model instance.
     *
     * @return Database
     * @throws ModelNotFoundException|HttpException
     */
    protected function retrieveConnectionRecord(): Database
    {
        if ($this->_connectionRecord !== null) {
            return $this->_connectionRecord;
        }

        if (!$this->getConnectionName()) {
            abort(500, 'Connection name not set');
        }

        return $this->_connectionRecord = Database::where('name', $this->getConnectionName())->firstOrFail();
    }
}
