<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\BeneficiaryResource\Pages;
use App\Models\Beneficiary;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class BeneficiaryResource extends Resource
{
    use RestrictsToAdminRoles;

    protected static ?string $model = Beneficiary::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'البيانات';
    protected static ?string $label = 'مستفيد';
    protected static ?string $pluralLabel = 'المستفيدون';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Forms\Components\TextInput::make('identity_number')
                    ->label('رقم الهوية')
                    ->required()
                    ->maxLength(9)
                    ->minLength(9)
                    ->numeric()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')
                    ->label('الاسم (اختياري)')
                    ->maxLength(200)
                    ->nullable(),
                Forms\Components\DatePicker::make('first_entry_date')
                    ->label('تاريخ أول إدخال')
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('identity_number')
                    ->label('رقم الهوية')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('first_entry_date')
                    ->label('تاريخ أول إدخال')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('entries_count')
                    ->label('الإدخالات')
                    ->counts('entries')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBeneficiaries::route('/'),
            'create' => Pages\CreateBeneficiary::route('/create'),
            'edit'   => Pages\EditBeneficiary::route('/{record}/edit'),
            'view'   => Pages\ViewBeneficiary::route('/{record}'),
        ];
    }
}
