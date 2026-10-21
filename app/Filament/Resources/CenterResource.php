<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToDashboardAdmin;
use App\Filament\Resources\CenterResource\Pages;
use App\Models\Center;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CenterResource extends Resource
{
    use RestrictsToDashboardAdmin;

    protected static ?string $model = Center::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'مركز';
    protected static ?string $pluralLabel = 'المراكز';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('code')
                ->label('الرمز')
                ->required()
                ->maxLength(10)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->maxLength(100),
            Forms\Components\Select::make('color')
                ->label('لون الشريط')
                ->options([
                    'blue'   => 'أزرق',
                    'green'  => 'أخضر',
                    'orange' => 'برتقالي',
                    'red'    => 'أحمر',
                    'purple' => 'بنفسجي',
                ])
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('نشط')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الرمز')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('color')->label('اللون'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('المشاريع')
                    ->counts('projects'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->actions([
                Actions\EditAction::make(),
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
            'index'  => Pages\ListCenters::route('/'),
            'create' => Pages\CreateCenter::route('/create'),
            'edit'   => Pages\EditCenter::route('/{record}/edit'),
        ];
    }
}
