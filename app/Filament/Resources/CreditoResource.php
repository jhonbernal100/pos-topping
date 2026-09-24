<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreditoResource\Pages;
use App\Models\Credito;
use App\Models\Tenant;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CreditoResource extends Resource
{
    protected static ?string $model = Credito::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Créditos';
    protected static ?string $modelLabel = 'Crédito';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del crédito')->schema([
                Forms\Components\Select::make('tenant_id')
                    ->label('Negocio')
                    ->options(Tenant::where('activo', true)->pluck('nombre', 'id'))
                    ->required()
                    ->searchable()
                    ->reactive(),
                Forms\Components\Select::make('cliente_id')
                    ->label('Cliente')
                    ->options(fn($get) =>
                        Cliente::where('tenant_id', $get('tenant_id'))
                            ->where('activo', true)
                            ->pluck('nombre', 'id')
                    )
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('tope_credito')
                    ->label('Tope de crédito (COP)')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->hint('Monto máximo que puede deber el cliente'),
                Forms\Components\TextInput::make('saldo_usado')
                    ->label('Saldo usado (COP)')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->hint('Se actualiza automáticamente con cada venta'),
                Forms\Components\Select::make('estado')
                    ->options([
                        'activo'   => 'Activo',
                        'bloqueado'=> 'Bloqueado',
                        'pagado'   => 'Pagado',
                    ])
                    ->default('activo')
                    ->required(),
                Forms\Components\Textarea::make('notas')
                    ->label('Notas')
                    ->maxLength(500),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.nombre')
                    ->label('Negocio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tope_credito')
                    ->label('Tope')
                    ->formatStateUsing(fn($state) => '$ ' . number_format($state, 0, ',', '.')),
                Tables\Columns\TextColumn::make('saldo_usado')
                    ->label('Usado')
                    ->formatStateUsing(fn($state) => '$ ' . number_format($state, 0, ',', '.')),
                Tables\Columns\TextColumn::make('saldo_disponible')
                    ->label('Disponible')
                    ->getStateUsing(fn($record) =>
                        '$ ' . number_format($record->saldoDisponible(), 0, ',', '.')
                    )
                    ->color(fn($record) =>
                        $record->saldoDisponible() <= 0 ? 'danger' : 'success'
                    ),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'activo'    => 'success',
                        'bloqueado' => 'danger',
                        'pagado'    => 'info',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Negocio')
                    ->options(Tenant::pluck('nombre', 'id')),
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'activo'    => 'Activo',
                        'bloqueado' => 'Bloqueado',
                        'pagado'    => 'Pagado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCreditos::route('/'),
            'create' => Pages\CreateCredito::route('/create'),
            'edit'   => Pages\EditCredito::route('/{record}/edit'),
        ];
    }
}