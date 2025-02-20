<?php

namespace Crumbls\Sqlite\Filament\Pages;

use Crumbls\Sqlite\Filament\Pages\Traits\HasConnection;
use Crumbls\Sqlite\Filament\Pages\Traits\WithSqliteConnection;
use Crumbls\Sqlite\Filament\Pages\Traits\WithSqliteTable;
use Crumbls\Sqlite\Models\Database;
use Crumbls\Sqlite\Models\Table;
use Crumbls\Sqlite\Models\TableSchema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\CreateAction;
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
            ->headerActions([
                CreateAction::make('create')
                    ->label(trans('sqlite::sqlite.actions.create_record'))
                    ->form(fn () => $this->generateFormSchema())
                    ->action(function (array $data) {
                        DB::connection($this->getConnectionName())
                            ->table($this->getTableName())
                            ->insert($data);

                        Notification::make()
                            ->success()
                            ->title(trans('sqlite::sqlite.notifications.record_created'))
                            ->send();
                    })
            ]);
    }

    protected function generateFormSchema(): array
    {
        return $this->getTableSchema()
            ->map(function ($column) {
                $type = strtolower($column->type);
                $required = $column->notnull && is_null($column->dflt_value);

                $field = match(true) {
                    // Integer types
                    str_contains($type, 'int') => TextInput::make($column->name)
                        ->numeric()
                        ->rules(['integer']),

                    // Decimal/Float types
                    str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double') =>
                    TextInput::make($column->name)
                        ->numeric()
                        ->rules(['numeric']),

                    // Boolean
                    str_contains($type, 'bool') || str_contains($type, 'tinyint(1)') =>
                    Select::make($column->name)
                        ->options([
                            '1' => 'Yes',
                            '0' => 'No',
                        ]),

                    // Date and Time
                    str_contains($type, 'datetime') => DateTimePicker::make($column->name),
                    str_contains($type, 'date') => DateTimePicker::make($column->name)->withoutTime(),
                    str_contains($type, 'time') => DateTimePicker::make($column->name)->withoutDate(),

                    // Text areas
                    str_contains($type, 'text') => Textarea::make($column->name),

                    // Default to text input
                    default => TextInput::make($column->name)
                };

                return $field
                    ->label(ucwords(str_replace('_', ' ', $column->name)))
                    ->required($required)
                    ->default($column->dflt_value);
            })
            ->toArray();
    }

}
