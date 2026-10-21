<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToDashboardAdmin;
use App\Filament\Resources\IdentityTypeResource\Pages;
use App\Models\IdentityType;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class IdentityTypeResource extends Resource
{
    use RestrictsToDashboardAdmin;

    protected static ?string $model = IdentityType::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'نوع هوية';
    protected static ?string $pluralLabel = 'أنواع الهوية';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->label('الاسم البرمجي')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('printed_name')
                ->label('الاسم المطبوع')
                ->required()
                ->maxLength(200),
            Forms\Components\Toggle::make('is_system')
                ->label('نظامي (لا يمكن حذفه)')
                ->disabled(),
            Forms\Components\Toggle::make('is_active')
                ->label('فعّال')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم البرمجي')
                    ->searchable(),
                Tables\Columns\TextColumn::make('printed_name')
                    ->label('الاسم المطبوع')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('نظامي')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->visible(fn($record) => !$record->is_system),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIdentityTypes::route('/'),
            'create' => Pages\CreateIdentityType::route('/create'),
            'edit'   => Pages\EditIdentityType::route('/{record}/edit'),
        ];
    }
}
