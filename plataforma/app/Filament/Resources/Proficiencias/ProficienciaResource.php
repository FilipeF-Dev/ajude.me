<?php

namespace App\Filament\Resources\Proficiencias;

use App\Filament\Resources\Proficiencias\Pages\CreateProficiencia;
use App\Filament\Resources\Proficiencias\Pages\EditProficiencia;
use App\Filament\Resources\Proficiencias\Pages\ListProficiencias;
use App\Filament\Resources\Proficiencias\Schemas\ProficienciaForm;
use App\Filament\Resources\Proficiencias\Tables\ProficienciasTable;
use App\Models\Proficiencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProficienciaResource extends Resource
{
    protected static ?string $model = Proficiencia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProficienciaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProficienciasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProficiencias::route('/'),
            'create' => CreateProficiencia::route('/create'),
            'edit' => EditProficiencia::route('/{record}/edit'),
        ];
    }
}
