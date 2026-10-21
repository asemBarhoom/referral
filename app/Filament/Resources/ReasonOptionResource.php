<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToDashboardAdmin;
use App\Filament\Resources\ReasonOptionResource\Pages;
use App\Models\ReasonOption;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReasonOptionResource extends Resource
{
    use RestrictsToDashboardAdmin;

    protected static ?string $model = ReasonOption::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'سبب';
    protected static ?string $pluralLabel = 'خيارات الأسباب';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('category')
                ->label('التصنيف')
                ->options([
                    ReasonOption::CATEGORY_EXCEPTION       => 'خدمة استثنائية',
                    ReasonOption::CATEGORY_NEW_PROJECT     => 'مشروع جديد',
                    ReasonOption::CATEGORY_CENTER_TRANSFER => 'نقل مركز',
                    ReasonOption::CATEGORY_CANCEL_VISIT    => 'إلغاء زيارة',
                ])
                ->required(),
            Forms\Components\TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->maxLength(200),
            Forms\Components\Toggle::make('is_active')
                ->label('فعّال')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn($s) => match($s) {
                        ReasonOption::CATEGORY_EXCEPTION       => 'استثنائي',
                        ReasonOption::CATEGORY_NEW_PROJECT     => 'مشروع جديد',
                        ReasonOption::CATEGORY_CENTER_TRANSFER => 'نقل مركز',
                        ReasonOption::CATEGORY_CANCEL_VISIT    => 'إلغاء زيارة',
                        default                                => $s,
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        ReasonOption::CATEGORY_EXCEPTION       => 'استثنائي',
                        ReasonOption::CATEGORY_NEW_PROJECT     => 'مشروع جديد',
                        ReasonOption::CATEGORY_CENTER_TRANSFER => 'نقل مركز',
                        ReasonOption::CATEGORY_CANCEL_VISIT    => 'إلغاء زيارة',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('فعّال'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('category');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReasonOptions::route('/'),
            'create' => Pages\CreateReasonOption::route('/create'),
            'edit'   => Pages\EditReasonOption::route('/{record}/edit'),
        ];
    }
}
