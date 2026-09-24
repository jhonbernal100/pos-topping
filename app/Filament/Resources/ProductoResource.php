<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Models\Producto;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Productos';
    protected static ?string $modelLabel = 'Producto';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del producto')->schema([
                Forms\Components\Select::make('tenant_id')
                    ->label('Negocio')
                    ->options(Tenant::where('activo', true)->pluck('nombre', 'id'))
                    ->required()->searchable(),
                Forms\Components\TextInput::make('nombre')
                    ->required()->maxLength(191),
                Forms\Components\TextInput::make('referencia')
                    ->maxLength(100),
                Forms\Components\TextInput::make('marca')
                    ->maxLength(100),
                Forms\Components\Select::make('categoria')
                    ->options([
                        'Tornillería'  => 'Tornillería',
                        'Herramientas' => 'Herramientas',
                        'Construcción' => 'Construcción',
                        'Eléctrico'    => 'Eléctrico',
                        'Plomería'     => 'Plomería',
                        'Pintura'      => 'Pintura',
                        'Seguridad'    => 'Seguridad',
                        'General'      => 'General',
                    ]),
                Forms\Components\Select::make('unidad')
                    ->options([
                        'unidad' => 'Unidad',
                        'metro'  => 'Metro',
                        'kilo'   => 'Kilo',
                        'litro'  => 'Litro',
                        'bolsa'  => 'Bolsa',
                        'caja'   => 'Caja',
                    ])->default('unidad'),
            ])->columns(2),

            Forms\Components\Section::make('Precios e inventario')->schema([
                Forms\Components\TextInput::make('precio_compra')
                    ->label('Precio compra (COP)')
                    ->numeric()->default(0),
                Forms\Components\TextInput::make('precio_venta')
                    ->label('Precio venta (COP)')
                    ->numeric()->default(0),
                Forms\Components\TextInput::make('stock')
                    ->numeric()->default(0),
                Forms\Components\TextInput::make('stock_minimo')
                    ->label('Stock mínimo')
                    ->numeric()->default(5),
                Forms\Components\Toggle::make('activo')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.nombre')
                    ->label('Negocio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('categoria')->badge(),
                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Precio venta')
                    ->formatStateUsing(fn($state) => '$ ' . number_format($state, 0, ',', '.')),
                Tables\Columns\TextColumn::make('stock')
                    ->badge()
                    ->color(fn($state, $record) =>
                        $state <= 0 ? 'danger' :
                        ($state <= $record->stock_minimo ? 'warning' : 'success')
                    ),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Negocio')
                    ->options(Tenant::pluck('nombre', 'id')),
                Tables\Filters\SelectFilter::make('categoria')
                    ->options([
                        'Tornillería'  => 'Tornillería',
                        'Herramientas' => 'Herramientas',
                        'Construcción' => 'Construcción',
                        'Eléctrico'    => 'Eléctrico',
                        'Plomería'     => 'Plomería',
                        'Pintura'      => 'Pintura',
                        'Seguridad'    => 'Seguridad',
                        'General'      => 'General',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'edit'   => Pages\EditProducto::route('/{record}/edit'),
        ];
    }
}