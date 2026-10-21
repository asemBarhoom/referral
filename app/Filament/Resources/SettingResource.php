<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToDashboardAdmin;
use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    use RestrictsToDashboardAdmin;

    protected static ?string $model = Setting::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'إعداد';
    protected static ?string $pluralLabel = 'إعدادات النظام';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('key')
                ->label('المفتاح')
                ->disabled(),
            Forms\Components\Select::make('type')
                ->label('النوع')
                ->options(['string' => 'نص', 'integer' => 'رقم', 'boolean' => 'منطقي'])
                ->disabled(),
            Forms\Components\TextInput::make('value')
                ->label('القيمة')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('المفتاح')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn($s) => match($s) {
                        'integer' => 'رقم',
                        'boolean' => 'منطقي',
                        default   => 'نص',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('القيمة'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تعديل')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Actions\EditAction::make()->label('تعديل'),
            ])
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'edit'  => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
