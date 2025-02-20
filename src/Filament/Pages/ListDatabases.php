<?php

namespace Crumbls\Sqlite\Filament\Pages;

use App\Models\User;
use Crumbls\Sqlite\Models\Database;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Config;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ListDatabases extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?int $navigationSort = 1; // Optional: control ordering

    protected static string $view = 'sqlite::filament.pages.list-databases';

    // This will be useful later when adding child pages
    protected static bool $shouldRegisterNavigation = true;

    public function getTitle(): string | Htmlable
    {
        return trans('sqlite::sqlite.databases');
    }

    public static function getNavigationLabel(): string {
        return trans('sqlite::sqlite.sql_browser');
    }

    public function getHeading(): string
    {
        return trans('sqlite::sqlite.sql_browser');
    }
    public function getSubheading(): ?string
    {
        return trans('sqlite::sqlite.select_database');
    }

    public static function getNavigationGroup(): ?string {
        if (!trans()->has('sqlite::sqlite.navigation_group')) {
            return  null;
        }

        return trans('sqlite::sqlite.navigation_group');
    }

    public static function getSlug(): string
    {
        return 'sqlite-browser';
    }


    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return Database::whereRaw('1=1');
            })
            ->columns([
                TextColumn::make('name')
                    ->label(trans('sqlite::sqlite.connection_name')),
                TextColumn::make('database')
                    ->label(trans('sqlite::sqlite.database'))
            ])
            ->recordUrl(function(Database $record) {
                return ViewDatabase::getUrl(['connectionName' => $record->name]);
            })
        ->actions([
            ActionGroup::make([
                Action::make('t')
            ])
        ]);
    }
}
