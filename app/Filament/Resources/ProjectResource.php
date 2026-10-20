<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'مشروع';
    protected static ?string $pluralLabel = 'المشاريع';
    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('code')
                ->label('الرمز')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->maxLength(100),
            Forms\Components\Toggle::make('allow_exceptional_service')
                ->label('السماح بالخدمة الاستثنائية')
                ->helperText('يُفعَّل لكل مشروع على حدة')
                ->default(false),
            Forms\Components\Toggle::make('is_active')
                ->label('نشط')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الرمز')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\IconColumn::make('allow_exceptional_service')
                    ->label('استثنائي')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
                Tables\Filters\TernaryFilter::make('allow_exceptional_service')->label('استثنائي'),
            ])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
