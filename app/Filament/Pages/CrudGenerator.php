<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\Acl;
use App\Support\CrudGenerator as CrudGeneratorService;
use App\Support\Icons;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;

class CrudGenerator extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'CRUD Generator';

    protected static ?string $title = 'CRUD Generator';

    protected static ?int $navigationSort = 5;

    /**
     * Navigation is rendered from the dynamic `menus` table instead.
     */
    protected static bool $shouldRegisterNavigation = false;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user() ?? auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole(Acl::SUPER_ADMIN_ROLE)) {
            return true;
        }

        return $user->checkPermissionTo('generate_crud');
    }

    public function mount(): void
    {
        $this->form->fill([
            'icon' => 'heroicon-o-rectangle-stack',
            'fields' => [$this->blankField()],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Modul')
                    ->description('Generator akan membuat Model, migration, Filament Resource, Policy, permission, dan menu secara otomatis.')
                    ->schema([
                        TextInput::make('model')
                            ->label('Nama Model')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Gunakan bentuk tunggal PascalCase, contoh: Product.'),

                        TextInput::make('label')
                            ->label('Label Menu')
                            ->maxLength(255)
                            ->helperText('Kosongkan untuk otomatis dari nama model.'),

                        Select::make('icon')
                            ->label('Ikon')
                            ->options(fn (): array => Icons::options())
                            ->default('heroicon-o-rectangle-stack')
                            ->searchable()
                            ->native(false),

                        TextInput::make('navigation_group')
                            ->label('Grup Menu (opsional)')
                            ->maxLength(255)
                            ->helperText('Menaruh menu di bawah grup, contoh: Master Data.'),
                    ])
                    ->columns(2),

                Section::make('Field')
                    ->description('Definisikan kolom yang akan dibuat untuk modul ini.')
                    ->schema([
                        Repeater::make('fields')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Kolom')
                                    ->required()
                                    ->helperText('snake_case, contoh: product_name.'),

                                Select::make('type')
                                    ->label('Tipe')
                                    ->options(fn (): array => CrudGeneratorService::FIELD_TYPES)
                                    ->default('string')
                                    ->required()
                                    ->native(false),

                                TextInput::make('options')
                                    ->label('Opsi Dropdown')
                                    ->helperText('Hanya untuk tipe Dropdown, pisahkan dengan koma.'),

                                TextInput::make('default')
                                    ->label('Nilai Default'),

                                Toggle::make('nullable')
                                    ->label('Nullable'),

                                Toggle::make('in_form')
                                    ->label('Tampil di Form')
                                    ->default(true),

                                Toggle::make('in_table')
                                    ->label('Tampil di Tabel')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Field')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    EmbeddedSchema::make('form'),
                ])
                    ->id('form')
                    ->livewireSubmitHandler('generate')
                    ->footer([
                        Actions::make([
                            Action::make('generate')
                                ->label('Generate Modul')
                                ->icon(Heroicon::OutlinedSparkles)
                                ->submit('generate'),
                        ]),
                    ]),
            ]);
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        try {
            $result = app(CrudGeneratorService::class)->generate($data);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Gagal membuat modul')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Modul berhasil dibuat')
            ->body("Model {$result['model']} beserta menu dan permission-nya sudah siap.")
            ->success()
            ->actions([
                Action::make('open')
                    ->label('Buka Modul')
                    ->url($result['resource_url']),
            ])
            ->send();

        $this->form->fill([
            'icon' => 'heroicon-o-rectangle-stack',
            'fields' => [$this->blankField()],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function blankField(): array
    {
        return [
            'name' => '',
            'type' => 'string',
            'options' => null,
            'default' => null,
            'nullable' => false,
            'in_form' => true,
            'in_table' => true,
        ];
    }
}
