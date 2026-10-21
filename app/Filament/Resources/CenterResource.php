<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\CenterResource\Pages;
use App\Models\Center;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CenterResource extends Resource
{
    use RestrictsToAdminRoles;

    protected static ?string $model = Center::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'مركز';
    protected static ?string $pluralLabel = 'المراكز';
    protected static ?int $navigationSort = 10;

    // Keys must match the colors registered in AdminPanelProvider.
    public const COLORS = [
        'blue'   => 'أزرق',
        'green'  => 'أخضر',
        'orange' => 'برتقالي',
        'red'    => 'أحمر',
        'purple' => 'بنفسجي',
    ];

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
                ->label('اللون')
                ->options(self::COLORS)
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('نشط')
                ->default(true),
            Forms\Components\CheckboxList::make('projects')
                ->label('النقاط في هذا المركز')
                ->helperText('المشاريع التي تظهر لموظفي هذا المركز في شاشة الإدخال.')
                ->relationship('projects', 'name', fn (Builder $query) => $query->orderBy('display_order'))
                ->columns(['default' => 2, 'lg' => 4])
                ->gridDirection('row')
                ->bulkToggleable()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الرمز')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('color')
                    ->label('اللون')
                    ->badge()
                    ->color(fn ($state) => $state)
                    ->formatStateUsing(fn ($state) => self::COLORS[$state] ?? $state),
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
