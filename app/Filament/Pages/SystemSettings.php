<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property \Filament\Schemas\Schema $form
 */
class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'System Settings';

    protected static ?int $navigationSort = 99;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return tms_user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'company_name' => SystemSetting::get('company_name', 'Menkem International Business PLC'),
            'max_trailers_per_power' => (int) SystemSetting::get('max_trailers_per_power', 1),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('company_name')
                    ->label('Company name')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('max_trailers_per_power')
                    ->label('Max active trailers per power unit')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SystemSetting::set('company_name', $data['company_name'], 'general');
        SystemSetting::set('max_trailers_per_power', (string) $data['max_trailers_per_power'], 'fleet');

        Notification::make()->title('Settings saved')->success()->send();
    }
}
