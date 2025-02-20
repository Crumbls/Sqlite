<?php

namespace Crumbls\Sqlite\Filament\Pages\Traits;

use Crumbls\Sqlite\Models\Table;
use Crumbls\Sqlite\Models\TableSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Trait WithSqliteTable
 *
 * Provides SQLite table management functionality for Filament pages.
 * Handles table name storage and record retrieval.
 */
trait WithSqliteTable
{
    /**
     * The name of the current SQLite table.
     *
     * @var string
     */
    public string $tableName;

    /**
     * The cached Table model instance.
     *
     * @var Table|null
     */
    protected ?Table $_tableRecord = null;


    /**
     * The cached primary key name.
     *
     * @var string|null
     */
    protected ?string $_primaryKey = null;

    /**
     * The cached table schema.
     *
     * @var string|null
     */
    protected ?Collection $_tableSchema = null;

    /**
     * Get the current table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Set the current table name.
     *
     * @param string $table
     * @return void
     */
    public function setTableName(string $table): void
    {
        $this->tableName = $table;
    }

    /**
     * Get or retrieve the Table model instance.
     *
     * @return Table
     * @throws ModelNotFoundException|HttpException
     */
    protected function retrieveTableRecord(): Table
    {
        if ($this->_tableRecord !== null) {
            return $this->_tableRecord;
        }

        if (!$this->getTableName()) {
            abort(500, 'Table name not set');
        }

        return $this->_tableRecord = Table::source($this->getConnectionName())->where('name', $this->getTableName())->firstOrFail();
    }

    /**
     * Get or retrieve the primary key.
     *
     * @return string|null
     */
    protected function getTablePrimaryKey() : string|null {
        if (isset($this->_primaryKey)) {
            return $this->_primaryKey;
        }

        $schema = $this->getTableSchema();

        $primaryKey = $schema->filter(function(TableSchema $item) {
            return $item->pk;
        })->first();

        $this->_primaryKey = $primaryKey ? $primaryKey->name : null;
        return $this->_primaryKey;
    }

    /**
     * Get or retrieve the table schema.
     *
     * @return TableSchema
     */
    protected function getTableSchema(): Collection {
        if (isset($this->_tableSchema)) {
            return $this->_tableSchema;
        }
        $this->_tableSchema = TableSchema::source($this->getConnectionName(), $this->getTableName())->get();
        return $this->_tableSchema;
    }
}
