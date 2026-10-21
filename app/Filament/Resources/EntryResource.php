<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\EntryResource\Pages;
use App\Models\Center;
use App\Models\Entry;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EntryResource extends Resource
{
    use RestrictsToAdminRoles;

    protected static ?string $model = Entry::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';
    protected static string|\UnitEnum|null $navigationGroup = 'البيانات';
    protected static ?string $label = 'إدخال';
    protected static ?string $pluralLabel = 'الإدخالات';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_time')
                    ->label('الوقت')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('beneficiary.identity_number')
                    ->label('رقم الهوية')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('beneficiary.name')
                    ->label('الاسم')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('center.name')
                    ->label('المركز'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn($state) => $state === Entry::TYPE_RECEPTION ? 'info' : 'warning')
                    ->formatStateUsing(fn($state) => $state === Entry::TYPE_RECEPTION ? 'استقبال' : 'نقطة'),
                Tables\Columns\IconColumn::make('is_new')
                    ->label('جديد')
                    ->boolean(),
                Tables\Columns\TextColumn::make('projects.name')
                    ->label('المشاريع')
                    ->listWithLineBreaks()
                    ->limitList(3),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('الموظف'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        Entry::TYPE_RECEPTION => 'استقبال',
                        Entry::TYPE_POINT     => 'نقطة',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label('اليوم فقط')
                    ->query(fn($query) => $query->whereDate('entry_date', today()))
                    ->default(),
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
            ->defaultSort('entry_time', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntries::route('/'),
            'edit'  => Pages\EditEntry::route('/{record}/edit'),
        ];
    }
}
