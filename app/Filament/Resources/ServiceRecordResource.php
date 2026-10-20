<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRecordResource\Pages;
use App\Models\Center;
use App\Models\Project;
use App\Models\ServiceRecord;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceRecordResource extends Resource
{
    protected static ?string $model = ServiceRecord::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';
    protected static string|\UnitEnum|null $navigationGroup = 'المراجعة';
    protected static ?string $label = 'سجل خدمة';
    protected static ?string $pluralLabel = 'سجلات الخدمات';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('beneficiary.identity_number')
                    ->label('رقم الهوية')
                    ->searchable(),
                Tables\Columns\TextColumn::make('beneficiary.name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.code')->label('المشروع'),
                Tables\Columns\TextColumn::make('center.name')->label('المركز'),
                Tables\Columns\TextColumn::make('match_code')->label('كود المطابقة'),
                Tables\Columns\IconColumn::make('is_exceptional')->label('استثنائي')->boolean(),
                Tables\Columns\TextColumn::make('review_status')
                    ->label('حالة المراجعة')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn($s) => match ($s) {
                        'pending'  => 'بانتظار',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                        default    => $s,
                    }),
                Tables\Columns\TextColumn::make('service_time')
                    ->label('وقت الخدمة')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->options(Project::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('review_status')
                    ->label('حالة المراجعة')
                    ->options(['pending' => 'بانتظار', 'approved' => 'معتمد', 'rejected' => 'مرفوض']),
                Tables\Filters\TernaryFilter::make('is_exceptional')->label('استثنائي'),
                Tables\Filters\Filter::make('service_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('من'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('حتى'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $d) => $q->whereDate('service_date', '>=', $d))
                            ->when($data['until'], fn($q, $d) => $q->whereDate('service_date', '<=', $d));
                    }),
            ])
            ->actions([Actions\ViewAction::make()])
            ->defaultSort('service_time', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRecords::route('/'),
        ];
    }
}
