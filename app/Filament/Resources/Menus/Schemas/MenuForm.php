<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Models\Permission;
use App\Support\Icons;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Menu')
                    ->schema([
                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sort')
                            ->label('Urutan')
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->helperText('Angka kecil tampil lebih dahulu.'),

                        Select::make('parent_id')
                            ->label('Induk Menu (opsional)')
                            ->relationship('parent', 'label')
                            ->searchable()
                            ->preload()
                            ->placeholder('Tanpa induk (menu utama)')
                            ->helperText('Pilih induk untuk menjadikan menu ini sebagai submenu.'),

                        Select::make('icon')
                            ->label('Ikon')
                            ->options(fn (): array => Icons::options())
                            ->searchable()
                            ->native(false),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Toggle::make('target_blank')
                            ->label('Buka di tab baru'),
                    ])
                    ->columns(2),

                Section::make('Tujuan')
                    ->description('Isi salah satu: pilih Filament Resource, atau isi URL manual.')
                    ->schema([
                        Select::make('resource')
                            ->label('Filament Resource')
                            ->options(fn (): array => static::resourceOptions())
                            ->searchable()
                            ->placeholder('— tidak menggunakan resource —')
                            ->columnSpanFull(),

                        TextInput::make('url')
                            ->label('URL Manual')
                            ->placeholder('/admin/contoh atau https://contoh.com')
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Hak Akses')
                    ->description('Kosongkan bila menu boleh dilihat semua role.')
                    ->schema([
                        Select::make('permission')
                            ->label('Permission')
                            ->options(fn (): array => Permission::query()->orderBy('name')->pluck('name', 'name')->all())
                            ->searchable()
                            ->placeholder('— semua role —')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Filament resources available on the current panel, keyed by class name.
     *
     * @return array<string, string>
     */
    protected static function resourceOptions(): array
    {
        $options = [];

        foreach (Filament::getCurrentPanel()?->getResources() ?? [] as $resource) {
            if (! class_exists($resource)) {
                continue;
            }

            $label = $resource::getNavigationLabel();

            $options[$resource] = $label instanceof Htmlable ? $label->toHtml() : $label;
        }

        ksort($options);

        return $options;
    }
}
