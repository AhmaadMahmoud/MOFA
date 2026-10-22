<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class ShopSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('Delivery');
    }

    public static function getNavigationLabel(): string
    {
        return __('Ordering & payment');
    }

    public function getTitle(): string
    {
        return __('Ordering & payment');
    }

    public function mount(): void
    {
        $values = Settings::all();
        $values['days_off'] = array_map('strval', Settings::daysOff());
        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        $weekdays = [];
        foreach ([6, 0, 1, 2, 3, 4, 5] as $day) {
            // Saturday first, as the week starts in Egypt.
            $weekdays[(string) $day] = now()->locale(app()->getLocale())->startOfWeek(0)->addDays($day)->translatedFormat('l');
        }

        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('Pre-ordering'))
                    ->description(__('When customers can order, and how many orders a day you can bake.'))
                    ->columns(['md' => 2])
                    ->schema([
                        TimePicker::make('order_cutoff')
                            ->label(__('Last order time for tomorrow'))
                            ->helperText(__('After this time, the earliest delivery is the day after tomorrow.'))
                            ->seconds(false)
                            ->format('H:i')
                            ->required(),
                        TextInput::make('max_days_ahead')
                            ->label(__('Days customers can book ahead'))
                            ->numeric()->integer()->minValue(1)->maxValue(30)
                            ->required(),
                        TextInput::make('daily_capacity')
                            ->label(__('Orders per day (all slots)'))
                            ->helperText(__('Leave empty for no limit. Each time slot can also have its own limit.'))
                            ->numeric()->integer()->minValue(1),
                        CheckboxList::make('days_off')
                            ->label(__('Weekly days off'))
                            ->options($weekdays)
                            ->columns(['default' => 2, 'sm' => 4])
                            ->columnSpanFull(),
                    ]),

                Section::make('InstaPay')
                    ->description(__('What customers see when they pay.'))
                    ->columns(['md' => 2])
                    ->schema([
                        TextInput::make('instapay_address')
                            ->label(__('InstaPay address'))
                            ->placeholder('mofa@instapay')
                            ->maxLength(100)
                            ->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('instapay_phone')
                            ->label(__('Phone number on InstaPay'))
                            ->tel()
                            ->maxLength(20)
                            ->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('instapay_name')
                            ->label(__('Account name'))
                            ->helperText(__('So customers know they are sending to the right person.'))
                            ->maxLength(100),
                        FileUpload::make('instapay_qr')
                            ->label(__('InstaPay QR code'))
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),

                Section::make('WhatsApp')
                    ->description(__('Customers send the InstaPay receipt to this number, and ask their questions here. Online ordering stays closed until it is filled in.'))
                    ->schema([
                        TextInput::make('contact_whatsapp')
                            ->label(__('WhatsApp number for receipts'))
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('01xxxxxxxxx')
                            ->extraInputAttributes(['dir' => 'ltr']),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('Save'))->submit('save')->keyBindings(['mod+s']),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['days_off'] = array_map('intval', $data['days_off'] ?? []);
        $data['daily_capacity'] = filled($data['daily_capacity'] ?? null) ? (int) $data['daily_capacity'] : null;
        $data['max_days_ahead'] = (int) $data['max_days_ahead'];

        Settings::put($data);

        Notification::make()->success()->title(__('Saved'))->send();
    }
}
