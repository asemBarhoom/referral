<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Center;
use App\Models\User;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use RestrictsToAdminRoles;

    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';
    protected static ?string $label = 'مستخدم';
    protected static ?string $pluralLabel = 'المستخدمون';
    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->schema([
            Section::make('بيانات الحساب')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('username')
                    ->label('اسم المستخدم')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->ascii(),
                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني (للدخول)')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->minLength(8)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $operation): bool => $operation === 'create'),
            ])->columns(2),
            Section::make('العمل')->schema([
                Forms\Components\Select::make('role')
                    ->label('النوع')
                    ->options([
                        User::ROLE_RECEPTION => 'موظف استقبال',
                        User::ROLE_POINT     => 'موظف نقطة',
                        User::ROLE_ADMIN     => 'مدير النظام',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if ($state === User::ROLE_ADMIN) {
                            $set('center_id', null);
                        }
                        if ($state !== User::ROLE_POINT) {
                            $set('project_id', null);
                        }
                    }),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true)
                    ->inline(false),
                Forms\Components\Select::make('center_id')
                    ->label('المركز')
                    ->options(fn () => Center::where('is_active', true)->orderBy('id')->pluck('name', 'id'))
                    ->visible(fn (Get $get) => in_array($get('role'), [User::ROLE_RECEPTION, User::ROLE_POINT], true))
                    ->required(fn (Get $get) => in_array($get('role'), [User::ROLE_RECEPTION, User::ROLE_POINT], true))
                    ->dehydratedWhenHidden()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('project_id', null)),
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->helperText('النقاط الموجودة في المركز المختار.')
                    ->options(fn (Get $get) => Center::find($get('center_id'))?->projects()
                        ->where('projects.is_active', true)
                        ->wherePivot('is_active', true)
                        ->orderBy('projects.display_order')
                        ->pluck('projects.name', 'projects.id') ?? [])
                    ->visible(fn (Get $get) => $get('role') === User::ROLE_POINT)
                    ->required(fn (Get $get) => $get('role') === User::ROLE_POINT)
                    ->dehydratedWhenHidden(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('username')->label('اسم المستخدم')->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('النوع')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        User::ROLE_RECEPTION => 'info',
                        User::ROLE_POINT     => 'warning',
                        User::ROLE_ADMIN     => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        User::ROLE_RECEPTION => 'استقبال',
                        User::ROLE_POINT     => 'نقطة',
                        User::ROLE_ADMIN     => 'أدمن',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
                Tables\Columns\TextColumn::make('center.name')
                    ->label('المركز')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('النوع')
                    ->options([
                        User::ROLE_RECEPTION => 'استقبال',
                        User::ROLE_POINT     => 'نقطة',
                        User::ROLE_ADMIN     => 'أدمن',
                    ]),
                Tables\Filters\SelectFilter::make('center_id')
                    ->label('المركز')
                    ->relationship('center', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->actions([Actions\EditAction::make()]);
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
