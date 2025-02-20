<?php

namespace Crumbls\Sqlite;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Crumbls\Sqlite\Filament\Pages\{
    EditRow,
    ListDatabases,
    ListTables,
    ViewDatabase,
    ViewTable
};

/**
 * SQLite Service Provider
 *
 * Provides SQLite database management functionality through Filament admin panel.
 * Includes features for viewing databases, tables, and editing rows.
 */
class SqliteServiceProvider extends ServiceProvider
{
    /**
     * All the Filament pages provided by this package
     *
     * @var array
     */
    private const FILAMENT_PAGES = [
        ListDatabases::class,
        ViewDatabase::class,
        ViewTable::class,
        EditRow::class
    ];

    /**
     * Bootstrap the package services.
     *
     * Registers views, translations, publishes config files,
     * and sets up Filament/Livewire components.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerResources();
        $this->registerPublishables();
        $this->registerFilamentComponents();
    }

    /**
     * Register package configuration.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sqlite.php',
            'sqlite'
        );
    }

    /**
     * Register the package resources (views and translations).
     *
     * @return void
     */
    private function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sqlite');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'sqlite');
    }

    /**
     * Register the publishable resources.
     *
     * @return void
     */
    private function registerPublishables(): void
    {
        $this->publishes([
            __DIR__ . '/../config/sqlite.php' => config_path('sqlite.php'),
            __DIR__ . '/../resources/views' => resource_path('views/vendor/sqlite'),
        ]);
    }

    /**
     * Register Filament and Livewire components if Filament is installed.
     *
     * @return void
     */
    private function registerFilamentComponents(): void
    {
        if (!class_exists(\Filament\Resources\Resource::class)) {
            return;
        }

        // Register Filament pages
        Filament::registerPages(self::FILAMENT_PAGES);

        // Register Livewire components
        foreach (self::FILAMENT_PAGES as $page) {
            Livewire::component(
                $this->getLivewireComponentName($page),
                $page
            );
        }
    }

    /**
     * Generate the Livewire component name from the class name.
     * Converts PHP namespace notation to Livewire dot notation.
     *
     * @param string $class
     * @return string
     */
    private function getLivewireComponentName(string $class): string
    {
        // Get the class name without namespace
        $className = class_basename($class);

        // Get namespace without the class name
        $namespace = str_replace('\\' . $className, '', $class);

        // Convert namespace separators to dots and convert to kebab-case
        $name = str_replace('\\', '.', strtolower($namespace))
            . '.'
            . strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));

        // Remove leading dot if present
        return ltrim($name, '.');
    }
}
