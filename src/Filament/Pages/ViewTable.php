<?php

namespace Crumbls\Sqlite\Filament\Pages;

use Crumbls\Sqlite\Filament\Pages\Traits\HasConnection;
use Crumbls\Sqlite\Filament\Pages\Traits\WithSqliteConnection;
use Crumbls\Sqlite\Filament\Pages\Traits\WithSqliteTable;
use Crumbls\Sqlite\Models\Database;
use Crumbls\Sqlite\Models\Table;
use Crumbls\Sqlite\Models\TableSchema;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class ViewTable extends Page  implements HasTable
{
    use WithSqliteConnection,
        WithSqliteTable,
        InteractsWithTable;

//    protected static ?string $cluster = Sqlite::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function getSlug(): string
    {
        return 'sqlite-browser/{connectionName}/{tableName}';
    }

    protected static ?int $navigationSort = 1; // Optional: control ordering

    protected static string $view = 'sqlite::filament.pages.view-table';

    // This will be useful later when adding child pages
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return trans('sqlite::sqlite.sql_browser');
    }
    public function getSubheading(): ?string
    {
        return trans('sqlite::sqlite.viewing').': '.$this->getTableName();
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }


    public static function getNavigationGroup(): ?string {
        return ListDatabases::getNavigationGroup();
    }


    public function mount(string $connectionName, string $tableName): void {
        $this->setConnectionName($connectionName);
        $this->setTableName($tableName);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        /**
         * Move this to a method to keep it cleaner here.
         */
        $primaryKey = $this->getTablePrimaryKey();

        return $table
            ->query(function () {
                $model = new class extends \Illuminate\Database\Eloquent\Model {};
                $model->setConnection($this->getConnectionName());
                $model->setTable($this->getTableName());
                return $model;
            })
            ->columns(
                $this
                    ->getTableSchema()
                    ->map(function($column) {
                        return TextColumn::make($column->name)
                        ->label($column->name);
                })->toArray()
            )
            ->recordUrl(function(Model $record) use ($primaryKey) {
                if (!$primaryKey) {
                    return null;
                }
                return EditRow::getUrl([
                    'connectionName' => $this->getConnectionName(),
                    'tableName' => $this->getTableName(),
                    'recordId' => $record->$primaryKey
                ]);
            })
            ->actions([
            ]);
    }

}
