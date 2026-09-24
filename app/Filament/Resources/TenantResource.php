<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Negocios';
    protected static ?string $modelLabel = 'Negocio';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información básica')->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()->maxLength(191),
                Forms\Components\TextInput::make('nit')
                    ->required()->maxLength(20)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('telefono')
                    ->maxLength(20),
                Forms\Components\TextInput::make('direccion')
                    ->maxLength(191),
                Forms\Components\TextInput::make('ciudad')
                    ->default('Bogotá')->maxLength(100),
                Forms\Components\Select::make('plan')
                    ->options([
                        'basico'       => 'Básico',
                        'profesional'  => 'Profesional',
                        'premium'      => 'Premium',
                    ])->default('basico')->required(),
                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Facturación electrónica (Alegra)')->schema([
                Forms\Components\TextInput::make('alegra_user')
                    ->label('Usuario Alegra')->maxLength(191),
                Forms\Components\TextInput::make('alegra_token')
                    ->label('Token Alegra')->maxLength(191)->password(),
                Forms\Components\TextInput::make('alegra_resolucion_id')
                    ->label('ID Resolución DIAN')->maxLength(50),
                Forms\Components\Toggle::make('facturacion_electronica')
                    ->label('Facturación electrónica activa'),
            ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nit')->searchable(),
                Tables\Columns\TextColumn::make('ciudad'),
                Tables\Columns\TextColumn::make('plan')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'basico'      => 'gray',
                        'profesional' => 'info',
                        'premium'     => 'success',
                    }),
                Tables\Columns\IconColumn::make('activo')->boolean(),
                Tables\Columns\IconColumn::make('facturacion_electronica')
                    ->label('DIAN')->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'basico'      => 'Básico',
                        'profesional' => 'Profesional',
                        'premium'     => 'Premium',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit'   => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}