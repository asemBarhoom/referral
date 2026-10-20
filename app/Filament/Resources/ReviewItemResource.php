<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewItemResource\Pages;
use App\Models\Center;
use App\Models\ReviewItem;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewItemResource extends Resource
{
    protected static ?string $model = ReviewItem::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|\UnitEnum|null $navigationGroup = 'المراجعة';
    protected static ?string $label = 'بند مراجعة';
    protected static ?string $pluralLabel = 'قائمة المراجعة';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('center_id')
                ->label('المركز')
                ->options(Center::pluck('name', 'id'))
                ->required(),
            Forms\Components\TextInput::make('type')->label('النوع')->required(),
            Forms\Components\Select::make('priority')
                ->label('الأولوية')
                ->options(['normal' => 'عادية', 'high' => 'عالية'])
                ->required(),
            Forms\Components\Select::make('decision')
                ->label('القرار')
                ->options([
                    'approved'    => 'معتمد',
                    'rejected'    => 'مرفوض',
                    'transferred' => 'محوّل',
                    'merged'      => 'مدموج',
                ])
                ->nullable(),
            Forms\Components\Textarea::make('notes')->label('ملاحظات')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('النوع')->searchable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->color(fn($state) => $state === 'high' ? 'danger' : 'warning')
                    ->formatStateUsing(fn($s) => $s === 'high' ? 'عالية' : 'عادية'),
                Tables\Columns\TextColumn::make('center.name')->label('المركز'),
                Tables\Columns\TextColumn::make('beneficiary.name')->label('المستفيد')->searchable(),
                Tables\Columns\TextColumn::make('decision')
                    ->label('القرار')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'approved'    => 'success',
                        'rejected'    => 'danger',
                        'transferred' => 'warning',
                        'merged'      => 'info',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn($s) => match ($s) {
                        'approved'    => 'معتمد',
                        'rejected'    => 'مرفوض',
                        'transferred' => 'محوّل',
                        'merged'      => 'مدموج',
                        default       => 'بانتظار',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options(['normal' => 'عادية', 'high' => 'عالية']),
                Tables\Filters\SelectFilter::make('decision')
                    ->label('القرار')
                    ->options([
                        'approved'    => 'معتمد',
                        'rejected'    => 'مرفوض',
                        'transferred' => 'محوّل',
                        'merged'      => 'مدموج',
                    ])
                    ->placeholder('بانتظار القرار'),
            ])
            ->actions([Actions\EditAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewItems::route('/'),
            'edit'  => Pages\EditReviewItem::route('/{record}/edit'),
        ];
    }
}
