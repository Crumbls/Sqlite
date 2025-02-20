<?php

namespace Crumbls\Sqlite\Filament\Pages;

use Closure;
use Crumbls\Sqlite\Filament\Pages\Traits\HasConnection;
use Crumbls\Sqlite\Filament\Pages\Traits\WithSqliteConnection;
use Crumbls\Sqlite\Models\Database;
use Crumbls\Sqlite\Models\Table;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\EditAction;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Get;
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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;


class ViewDatabase extends Page  implements HasTable
{
    use WithSqliteConnection,
        InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?int $navigationSort = 1; // Optional: control ordering
    protected static string $view = 'sqlite::filament.pages.list-tables';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return trans('sqlite::sqlite.sql_browser');
    }
    public function getSubheading(): ?string
    {
        return trans('sqlite::sqlite.viewing').': '.$this->getConnectionName();
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }


    public static function getNavigationGroup(): ?string {
        return ListDatabases::getNavigationGroup();
    }


    public function mount(string $connectionName): void {
        $this->setConnectionName($connectionName);
    }


    public static function getSlug(): string
    {
        return 'sqlite-browser/{connectionName}';
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->query(function () {
                return Table::source($this->getConnectionName())->whereRaw('1=1');
            })
            ->headerActions([
                CreateAction::make('create_table')
                    ->label('Create Table')
                    ->form([
                        TextInput::make('table_name')
                            ->required()
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                    if (Schema::connection($this->getConnectionName())->hasTable($value)) {
                                        $fail('Table '.$value.' already exists.');
                                    }
                                }
                                ])
                            ->regex('/^[a-zA-Z_][a-zA-Z0-9_]*$/')
                            ->helperText('Table name must start with a letter or underscore, and can only contain letters, numbers, and underscores')
                            ->label('Table Name'),

                        Repeater::make('columns')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->regex('/^[a-zA-Z_][a-zA-Z0-9_]*$/')
                                            ->helperText('Column name must start with a letter or underscore')
                                            ->columnSpan(1),

                                        Select::make('type')
                                            ->options([
                                                'INTEGER' => 'INTEGER',
                                                'TEXT' => 'TEXT',
                                                'REAL' => 'REAL',
                                                'BLOB' => 'BLOB',
                                                'NUMERIC' => 'NUMERIC',
                                                'BOOLEAN' => 'BOOLEAN',
                                                'DATETIME' => 'DATETIME',
                                                'DATE' => 'DATE',
                                                'TIME' => 'TIME',
                                            ])
                                            ->required()
                                            ->columnSpan(1),

                                        Grid::make(3)
                                            ->schema([
                                                Checkbox::make('primary_key')
                                                    ->label('Primary Key'),

                                                Checkbox::make('autoincrement')
                                                    ->label('Auto Increment')
                                                    ->visible(fn (Get $get) => $get('type') === 'INTEGER'),

                                                Checkbox::make('nullable')
                                                    ->label('Nullable'),
                                            ])
                                            ->columnSpan(1),

                                        Select::make('default')
                                            ->options([
                                                'NONE' => 'None',
                                                'NULL' => 'NULL',
                                                'CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP',
                                                'custom' => 'Custom Value',
                                            ])
                                            ->default('NONE')
                                            ->live()
                                            ->columnSpan(1),

                                        TextInput::make('custom_default')
                                            ->visible(fn (Get $get) => $get('default') === 'custom')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add Column')
                            ->deletable(true)
                            ->reorderable(true)
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        $tableName = $data['table_name'];
                        $columns = collect($data['columns'])->map(function ($column) {
                            $definition = "`{$column['name']}` {$column['type']}";

                            if ($column['primary_key']) {
                                $definition .= ' PRIMARY KEY';
                                if ($column['type'] === 'INTEGER' && $column['autoincrement']) {
                                    $definition .= ' AUTOINCREMENT';
                                }
                            }

                            if (!($column['nullable'] ?? false)) {
                                $definition .= ' NOT NULL';
                            }

                            if ($column['default'] !== 'NONE') {
                                if ($column['default'] === 'custom') {
                                    $definition .= " DEFAULT '{$column['custom_default']}'";
                                } elseif ($column['default'] !== 'NULL') {
                                    $definition .= " DEFAULT {$column['default']}";
                                }
                            }

                            return $definition;
                        })->join(', ');

                        DB::connection($this->getConnectionName())
                            ->statement("CREATE TABLE {$tableName} ({$columns})");

                        Notification::make()
                            ->success()
                            ->title('Table created successfully')
                            ->send();

                        $this->redirect(ViewTable::getUrl([
                            'connectionName' => $this->getConnectionName(),
                            'tableName' => $tableName
                        ]));
                    })
                    ->modalWidth('4xl'), // Make the modal wider

            ])
            ->columns([
                TextColumn::make('name')
                    ->label(trans('sqlite::sqlite.table_name'))
                    ->sortable(true)
                ->searchable(true)
                ,
                TextColumn::make('count')
                    ->label(trans('sqlite::sqlite.rows'))
                ->sortable(true)
            ])
            ->recordUrl(function(Table $record) {
                return ViewTable::getUrl([
                    'connectionName' => $this->getConnectionName(),
                    'tableName' => $record->name
                ]);
            })
            /*
            ->actions([
                ActionGroup::make([
                    EditAction::make('t')
                ])
            ])
            */
            ->paginated(function (\Filament\Tables\Table $table) {
                return false;
            })

            ;
    }

}
