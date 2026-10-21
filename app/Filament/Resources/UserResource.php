<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToDashboardAdmin;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use RestrictsToDashboardAdmin;

    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'مستخدم';
    protected static ?string $pluralLabel = 'المستخدمون';
    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('بيانات الحساب')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->minLength(8)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $operation): bool => $operation === 'create'),
            ])->columns(2),
            Section::make('الدور والمراكز')->schema([
                Forms\Components\Select::make('role')
                    ->label('الدور')
                    ->options([
                        User::ROLE_RECEPTION       => 'موظف استقبال',
                        User::ROLE_PROJECT_STAFF   => 'موظف نقطة مشروع',
                        User::ROLE_CENTER_ADMIN    => 'أدمن مركز',
                        User::ROLE_DASHBOARD_ADMIN => 'أدمن لوحة التحكم',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                Forms\Components\Select::make('centers')
                    ->label('المراكز')
                    ->multiple()
                    ->relationship('centers', 'name')
                    ->preload(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('البريد')->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn($s) => match ($s) {
                        User::ROLE_RECEPTION       => 'استقبال',
                        User::ROLE_PROJECT_STAFF   => 'نقطة مشروع',
                        User::ROLE_CENTER_ADMIN    => 'أدمن مركز',
                        User::ROLE_DASHBOARD_ADMIN => 'أدمن لوحة التحكم',
                        default => $s,
                    }),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
                Tables\Columns\TextColumn::make('centers.name')
                    ->label('المراكز')
                    ->listWithLineBreaks()
                    ->limitList(2),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('الدور')
                    ->options([
                        User::ROLE_RECEPTION       => 'استقبال',
                        User::ROLE_PROJECT_STAFF   => 'نقطة مشروع',
                        User::ROLE_CENTER_ADMIN    => 'أدمن مركز',
                        User::ROLE_DASHBOARD_ADMIN => 'أدمن لوحة التحكم',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
