<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\ReviewItemResource\Pages;
use App\Models\BeneficiaryProject;
use App\Models\Center;
use App\Models\ReviewItem;
use App\Models\ServiceRecord;
use Filament\Forms;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ReviewItemResource extends Resource
{
    use RestrictsToAdminRoles;

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
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn($s) => match($s) {
                        'exceptional_service'       => 'خدمة استثنائية',
                        'pending_enrollment_service'=> 'اعتماد ارتباط',
                        default                     => $s,
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->color(fn($state) => $state === 'high' ? 'danger' : 'warning')
                    ->formatStateUsing(fn($s) => $s === 'high' ? 'عالية' : 'عادية'),
                Tables\Columns\TextColumn::make('center.name')->label('المركز'),
                Tables\Columns\TextColumn::make('beneficiary.name')
                    ->label('المستفيد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('beneficiary.identity_number')
                    ->label('رقم الهوية')
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('decisionMaker.name')
                    ->label('المقرر')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
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
            ->actions([
                Actions\Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الاعتماد')
                    ->modalDescription(fn($record) => 'هل تريد اعتماد هذا البند؟' . ($record->notes ? ' — ' . $record->notes : ''))
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظة الاعتماد (اختياري)')
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'decision'          => 'approved',
                                'decision_maker_id' => auth()->id(),
                                'decided_at'        => now(),
                                'notes'             => $data['notes'] ?? $record->notes,
                            ]);

                            // If linked to a pending ServiceRecord, approve it too
                            if ($record->source_type === ServiceRecord::class && $record->source_id) {
                                $sr = ServiceRecord::find($record->source_id);
                                if ($sr && $sr->review_status === ServiceRecord::REVIEW_PENDING) {
                                    $sr->update(['review_status' => ServiceRecord::REVIEW_APPROVED]);
                                }
                            }

                            // If type = pending_enrollment_service, approve the BeneficiaryProject
                            if ($record->type === 'pending_enrollment_service' && $record->beneficiary_id) {
                                BeneficiaryProject::where('beneficiary_id', $record->beneficiary_id)
                                    ->where('center_id', $record->center_id)
                                    ->where('status', BeneficiaryProject::STATUS_PENDING)
                                    ->update([
                                        'status'      => BeneficiaryProject::STATUS_ACTIVE,
                                        'approved_by' => auth()->id(),
                                    ]);
                            }
                        });

                        Notification::make()->title('تم الاعتماد')->success()->send();
                    })
                    ->visible(fn($record) => $record->decision === null),

                Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الرفض')
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('سبب الرفض')
                            ->required()
                            ->minLength(5),
                    ])
                    ->action(function ($record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'decision'          => 'rejected',
                                'decision_maker_id' => auth()->id(),
                                'decided_at'        => now(),
                                'notes'             => $data['notes'],
                            ]);

                            // Reject the linked ServiceRecord if any
                            if ($record->source_type === ServiceRecord::class && $record->source_id) {
                                $sr = ServiceRecord::find($record->source_id);
                                if ($sr && $sr->review_status === ServiceRecord::REVIEW_PENDING) {
                                    $sr->update(['review_status' => ServiceRecord::REVIEW_REJECTED]);
                                }
                            }
                        });

                        Notification::make()->title('تم الرفض')->danger()->send();
                    })
                    ->visible(fn($record) => $record->decision === null),

                Actions\EditAction::make()->label('تعديل'),
            ])
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
