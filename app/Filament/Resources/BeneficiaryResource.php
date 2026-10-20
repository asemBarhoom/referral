<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BeneficiaryResource\Pages;
use App\Models\Beneficiary;
use App\Models\Center;
use App\Models\IdentityType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class BeneficiaryResource extends Resource
{
    protected static ?string $model = Beneficiary::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'المستفيدون';
    protected static ?string $label = 'مستفيد';
    protected static ?string $pluralLabel = 'المستفيدون';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('البيانات الأساسية')->schema([
                Forms\Components\TextInput::make('identity_number')
                    ->label('رقم الهوية')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('identity_type_id')
                    ->label('نوع الهوية')
                    ->options(IdentityType::where('is_active', true)->pluck('printed_name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(200),
                Forms\Components\TextInput::make('phone')
                    ->label('الجوال')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\DatePicker::make('dob')
                    ->label('تاريخ الميلاد')
                    ->nullable(),
                Forms\Components\Select::make('registration_center_id')
                    ->label('مركز التسجيل')
                    ->options(Center::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
            ])->columns(2),
            Section::make('الحالة')->schema([
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'merged' => 'مدموج',
                    ])
                    ->required()
                    ->default('active'),
            ]),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('identityType.printed_name')
                    ->label('النوع'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الجوال')
                    ->searchable(),
                Tables\Columns\TextColumn::make('registrationCenter.name')
                    ->label('المركز'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'merged' => 'danger',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'active' => 'نشط',
                        'merged' => 'مدموج',
                        default  => $state,
                    }),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('المشاريع')
                    ->counts('projects'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('registration_center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(['active' => 'نشط', 'merged' => 'مدموج']),
                Tables\Filters\SelectFilter::make('identity_type_id')
                    ->label('نوع الهوية')
                    ->options(IdentityType::pluck('printed_name', 'id')),
            ])
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
