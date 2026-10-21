<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\EntryResource\Pages;
use App\Models\Center;
use App\Models\Entry;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
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
        return $schema->columns(1)->schema([
            Section::make('بيانات الحالة')
                ->schema([
                    TextEntry::make('beneficiary.identity_number')
                        ->label('رقم الهوية')
                        ->copyable(),
                    TextEntry::make('beneficiary.name')
                        ->label('الاسم')
                        ->placeholder('—'),
                    TextEntry::make('entry_time')
                        ->label('وقت الإدخال')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('status')
                        ->label('الحالة')
                        ->state(fn (Entry $record) => $record->is_new ? 'جديدة' : 'متكررة')
                        ->badge()
                        ->color(fn (Entry $record) => $record->is_new ? 'success' : 'gray'),
                    TextEntry::make('center.name')
                        ->label('المركز'),
                    TextEntry::make('staff.name')
                        ->label('الموظف'),
                    TextEntry::make('type')
                        ->label('النوع')
                        ->badge()
                        ->color(fn ($state) => $state === Entry::TYPE_RECEPTION ? 'info' : 'warning')
                        ->formatStateUsing(fn ($state) => $state === Entry::TYPE_RECEPTION ? 'استقبال' : 'نقطة'),
                ])
                ->columns(['default' => 2, 'lg' => 4]),

            Section::make('المشاريع')
                ->description('المشاريع التي استفادت منها الحالة في هذا الإدخال.')
                ->schema([
                    Forms\Components\CheckboxList::make('projects')
                        ->hiddenLabel()
                        ->relationship('projects', 'name', fn (Builder $query) => $query->orderBy('display_order'))
                        ->columns(['default' => 2, 'lg' => 4])
                        ->gridDirection('row')
                        ->required(),
                ]),
        ]);
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
