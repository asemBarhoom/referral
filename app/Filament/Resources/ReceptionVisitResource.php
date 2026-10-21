<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\ReceptionVisitResource\Pages;
use App\Models\Center;
use App\Models\ReasonOption;
use App\Models\ReceptionVisit;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReceptionVisitResource extends Resource
{
    use RestrictsToAdminRoles;

    protected static ?string $model = ReceptionVisit::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = 'المراجعة';
    protected static ?string $label = 'زيارة';
    protected static ?string $pluralLabel = 'سجل الزيارات';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('center_id')
                ->label('المركز')
                ->options(Center::pluck('name', 'id'))
                ->disabled(),
            Forms\Components\TextInput::make('type')
                ->label('النوع')
                ->disabled(),
            Forms\Components\TextInput::make('status')
                ->label('الحالة')
                ->disabled(),
            Forms\Components\Textarea::make('cancel_reason')
                ->label('سبب الإلغاء')
                ->nullable(),
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
                Tables\Columns\TextColumn::make('center.name')
                    ->label('المركز'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn($s) => $s === 'new' ? 'جديد' : 'متابعة'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn($s) => $s === 'valid' ? 'success' : 'danger')
                    ->formatStateUsing(fn($s) => $s === 'valid' ? 'صالحة' : 'ملغاة'),
                Tables\Columns\TextColumn::make('visit_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('الموظف'),
                Tables\Columns\TextColumn::make('cancel_reason')
                    ->label('سبب الإلغاء')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(['valid' => 'صالحة', 'cancelled' => 'ملغاة']),
                Tables\Filters\Filter::make('today')
                    ->label('زيارات اليوم')
                    ->query(fn($query) => $query->where('visit_date', now()->timezone('Asia/Gaza')->toDateString()))
                    ->default(),
            ])
            ->actions([
                Actions\Action::make('cancel')
                    ->label('إلغاء الزيارة')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إلغاء الزيارة')
                    ->modalDescription('سيتم إلغاء الزيارة وجميع التحويلات المرتبطة بها لهذا اليوم.')
                    ->form([
                        Forms\Components\Select::make('reason_option_id')
                            ->label('سبب الإلغاء')
                            ->options(
                                ReasonOption::where('category', ReasonOption::CATEGORY_CANCEL_VISIT)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->nullable(),
                        Forms\Components\Textarea::make('cancel_reason')
                            ->label('تفاصيل إضافية')
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data) {
                        $reason = '';
                        if (!empty($data['reason_option_id'])) {
                            $opt = ReasonOption::find($data['reason_option_id']);
                            $reason = $opt ? $opt->name : '';
                        }
                        if (!empty($data['cancel_reason'])) {
                            $reason .= ($reason ? ' — ' : '') . $data['cancel_reason'];
                        }

                        $record->update([
                            'status'       => ReceptionVisit::STATUS_CANCELLED,
                            'cancel_reason'=> $reason ?: 'إلغاء يدوي',
                            'cancelled_by' => auth()->id(),
                        ]);

                        Notification::make()->title('تم إلغاء الزيارة')->success()->send();
                    })
                    ->visible(fn($record) => $record->status === ReceptionVisit::STATUS_VALID),
            ])
            ->defaultSort('visit_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceptionVisits::route('/'),
        ];
    }
}
