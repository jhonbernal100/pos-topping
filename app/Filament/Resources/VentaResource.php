<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Models\Venta;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Ventas';
    protected static ?string $modelLabel = 'Venta';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de la venta')->schema([
                Forms\Components\Select::make('tenant_id')
                    ->label('Negocio')
                    ->options(Tenant::where('activo', true)->pluck('nombre', 'id'))
                    ->required(),
                Forms\Components\Select::make('estado')
                    ->options([
                        'completada' => 'Completada',
                        'anulada'    => 'Anulada',
                    ])->required(),
                Tables\Columns\TextColumn::make('total'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.nombre')
                    ->label('Negocio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->formatStateUsing(fn($state) => str_pad($state, 6, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')->default('Consumidor final'),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn($state) => '$ ' . number_format($state, 0, ',', '.')),
                Tables\Columns\TextColumn::make('metodo_pago')->label('Pago'),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'completada' => 'success',
                        'anulada'    => 'danger',
                        default      => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Negocio')
                    ->options(Tenant::pluck('nombre', 'id')),
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'completada' => 'Completada',
                        'anulada'    => 'Anulada',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentas::route('/'),
        ];
    }
}