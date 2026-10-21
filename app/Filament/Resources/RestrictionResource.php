<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\RestrictionResource\Pages;
use App\Models\Beneficiary;
use App\Models\ReasonOption;
use App\Models\Restriction;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RestrictionResource extends Resource
{
    use RestrictsToAdminRoles;

    protected static ?string $model = Restriction::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-no-symbol';
    protected static string|\UnitEnum|null $navigationGroup = 'المستفيدون';
    protected static ?string $label = 'قيد';
    protected static ?string $pluralLabel = 'القيود';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('beneficiary_id')
                ->label('المستفيد')
                ->relationship('beneficiary', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('level')
                ->label('المستوى')
                ->options([
                    Restriction::LEVEL_WARNING          => 'تحذير',
                    Restriction::LEVEL_UNDER_REVIEW      => 'تحت المراجعة',
                    Restriction::LEVEL_TEMPORARY_BLOCK   => 'منع مؤقت',
                    Restriction::LEVEL_PERMANENT_BLOCK   => 'منع دائم',
                ])
                ->required(),
            Forms\Components\Select::make('reason_option_id')
                ->label('سبب محدد')
                ->options(ReasonOption::where('is_active', true)->pluck('name', 'id'))
                ->nullable(),
            Forms\Components\Textarea::make('reason')
                ->label('تفاصيل السبب')
                ->rows(3)
                ->nullable(),
            Forms\Components\DateTimePicker::make('starts_at')
                ->label('يبدأ من')
                ->required()
                ->default(now()),
            Forms\Components\DateTimePicker::make('ends_at')
                ->label('ينتهي في (فارغ = دائم)')
                ->nullable(),
            Forms\Components\Toggle::make('is_active')
                ->label('فعّال')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('beneficiary.name')
                    ->label('المستفيد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('beneficiary.identity_number')
                    ->label('رقم الهوية')
                    ->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('المستوى')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'warning'          => 'warning',
                        'under_review'     => 'info',
                        'temporary_block'  => 'danger',
                        'permanent_block'  => 'danger',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn($s) => match($s) {
                        'warning'          => 'تحذير',
                        'under_review'     => 'تحت المراجعة',
                        'temporary_block'  => 'منع مؤقت',
                        'permanent_block'  => 'منع دائم',
                        default            => $s,
                    }),
                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(50),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('يبدأ')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('ينتهي')
                    ->dateTime()
                    ->placeholder('دائم'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('أُضيف')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('المستوى')
                    ->options([
                        'warning'          => 'تحذير',
                        'under_review'     => 'تحت المراجعة',
                        'temporary_block'  => 'منع مؤقت',
                        'permanent_block'  => 'منع دائم',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('فعّال'),
            ])
            ->actions([
                Actions\EditAction::make()->label('تعديل'),
                Actions\Action::make('deactivate')
                    ->label('إلغاء تفعيل')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد إلغاء التفعيل')
                    ->action(fn($record) => $record->update(['is_active' => false]))
                    ->visible(fn($record) => $record->is_active),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRestrictions::route('/'),
            'create' => Pages\CreateRestriction::route('/create'),
            'edit'   => Pages\EditRestriction::route('/{record}/edit'),
        ];
    }
}
