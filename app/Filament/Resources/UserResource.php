<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToAdminRoles;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            Section::make('الدور')->schema([
                Forms\Components\Select::make('role')
                    ->label('النوع')
                    ->options([
                        User::ROLE_RECEPTION => 'موظف استقبال',
                        User::ROLE_POINT     => 'موظف نقطة',
                        User::ROLE_ADMIN     => 'مدير النظام',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true)
                    ->inline(false),
            ])->columns(2),
            Section::make('المراكز والمشاريع')->schema([
                Forms\Components\CheckboxList::make('centers')
                    ->label('المراكز')
                    ->helperText('إذا ارتبط بأكثر من مركز، يختار مركزه مرة واحدة عند بداية العمل.')
                    ->relationship('centers', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('id'))
                    ->columns(2)
                    ->gridDirection('row')
                    ->required(),
                Forms\Components\CheckboxList::make('projects')
                    ->label('المشاريع')
                    ->helperText('اتركها فارغة ليظهر له كل مشاريع المركز.')
                    ->relationship('projects', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('display_order'))
                    ->columns(['default' => 2, 'lg' => 4])
                    ->gridDirection('row')
                    ->bulkToggleable(),
            ]),
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
                Tables\Columns\TextColumn::make('centers.name')
                    ->label('المراكز')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('projects.name')
                    ->label('المشاريع')
                    ->badge()
                    ->color('gray')
                    ->limitList(3)
                    ->placeholder('كل مشاريع المركز'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('النوع')
                    ->options([
                        User::ROLE_RECEPTION => 'استقبال',
                        User::ROLE_POINT     => 'نقطة',
                        User::ROLE_ADMIN     => 'أدمن',
                    ]),
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
