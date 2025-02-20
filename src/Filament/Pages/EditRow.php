<?php

namespace Crumbls\Sqlite\Filament\Pages;

use Crumbls\Sqlite\Filament\Pages\Traits\HasConnection;
use Crumbls\Sqlite\Filament\Pages\Traits\WithSqliteConnection;
use Crumbls\Sqlite\Filament\Pages\Traits\WithSqliteTable;
use Crumbls\Sqlite\Models\Database;
use Crumbls\Sqlite\Models\Table;
use Crumbls\Sqlite\Models\TableSchema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Illuminate\Support\Collection;

class EditRow extends Page implements HasForms
{
    use WithSqliteConnection,
        WithSqliteTable,
        InteractsWithForms;


//    protected static ?string $cluster = Sqlite::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog';

//    protected static ?string $navigationGroup = 'System'; // Optional: if you want to group it

    protected static ?int $navigationSort = 1; // Optional: control ordering

    protected static string $view = 'sqlite::filament.pages.edit-row';

    public ?array $data = null;

    // This will be useful later when adding child pages
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return trans('sqlite::sqlite.sql_browser');
    }
    public function getSubheading(): ?string
    {
        return trans('sqlite::sqlite.editing').': '.$this->_primaryKey;
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }


    public static function getNavigationGroup(): ?string {
        return ListDatabases::getNavigationGroup();
    }

    public static function getSlug(): string
    {
        return 'sqlite-browser/{connectionName}/{tableName}/{recordId}';
    }

    public string $recordId;

    public function mount(string $connectionName, string $tableName, string $recordId): void {
        $this->setConnectionName($connectionName);

        $this->setTableName($tableName);

        $record = DB::connection($this->getConnectionName())
            ->table($this->getTableName())
            ->where($this->getTablePrimaryKey(), $recordId)
            ->firstOrFail();

        $this->data = (array)$record;
    }

    /**
     * Build the form schema based on the table structure.
     *
     * @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema($this->generateFormSchema())
            ->statePath('data');
    }

    /**
     * Generate form fields based on table schema.
     *
     * @return array
     */
    protected function generateFormSchema(): array
    {
        return $this->getTableSchema()
            ->map(function ($column) {
                return $this->mapColumnToFormField($column);
            })
            ->filter()
            ->toArray();
    }

    /**
     * Map a database column to a Filament form field.
     *
     * @param array|object $column
     * @return \Filament\Forms\Components\Component|null
     */
    protected function mapColumnToFormField($column): mixed
    {
        // Assuming column has properties: name, type, notnull, dflt_value
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

        // Add common configurations
        return $field
            ->label(ucwords(str_replace('_', ' ', $column->name)))
            ->required($required)
            ->default($column->dflt_value);
    }

    /**
     * Get the initial form data.
     *
     * @return array
     */
    protected function getFormData(): array
    {
        // Assuming you have the row data available
        return $this->row ?? [];
    }

    /**
     * Handle the form submission.
     *
     * @param array $data
     * @return void
     */
    public function update(): void
    {
        $primaryKey = $this->getTablePrimaryKey();

        DB::connection($this->getConnectionName())
            ->table($this->getTableName())
            ->where($primaryKey, $this->recordId)
            ->limit(1)
            ->update($this->data);

        Notification::make()
            ->success()
            ->title('Saved successfully')
            ->send();
    }

}
