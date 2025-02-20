<?php

namespace Crumbls\Sqlite\Filament\Pages;

use Crumbls\Sqlite\Filament\Pages\Traits\HasConnection;
use Crumbls\Sqlite\Filament\Pages\Traits\WithSqliteConnection;
use Crumbls\Sqlite\Models\Database;
use Crumbls\Sqlite\Models\Table;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Route;


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
            ->actions([
                ActionGroup::make([
                    Action::make('t')
                ])
            ])
            ->paginated(function (\Filament\Tables\Table $table) {
                return false;
            })

            ;
    }

}
