<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Center;
use App\Models\Device;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-tablet';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'جهاز';
    protected static ?string $pluralLabel = 'الأجهزة';
    protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('center_id')
                ->label('المركز')
                ->options(Center::where('is_active', true)->pluck('name', 'id'))
                ->required(),
            Forms\Components\TextInput::make('name')
                ->label('اسم الجهاز')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('device_token')
                ->label('رمز الجهاز')
                ->required()
                ->default(fn() => Str::random(32))
                ->unique(ignoreRecord: true)
                ->maxLength(64),
            Forms\Components\Toggle::make('is_disabled')
                ->label('معطّل')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('center.name')->label('المركز'),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('آخر ظهور')
                    ->dateTime()
                    ->since(),
                Tables\Columns\IconColumn::make('is_disabled')->label('معطّل')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_disabled')->label('المعطّل'),
            ])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit'   => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}
