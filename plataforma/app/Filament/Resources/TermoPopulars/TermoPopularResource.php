<?php

namespace App\Filament\Resources\TermoPopulars;

use App\Filament\Resources\TermoPopulars\Pages\CreateTermoPopular;
use App\Filament\Resources\TermoPopulars\Pages\EditTermoPopular;
use App\Filament\Resources\TermoPopulars\Pages\ListTermoPopulars;
use App\Filament\Resources\TermoPopulars\Schemas\TermoPopularForm;
use App\Filament\Resources\TermoPopulars\Tables\TermoPopularsTable;
use App\Models\TermoPopular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TermoPopularResource extends Resource
{
    protected static ?string $model = TermoPopular::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TermoPopularForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TermoPopularsTable::configure($table);
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
            'index' => ListTermoPopulars::route('/'),
            'create' => CreateTermoPopular::route('/create'),
            'edit' => EditTermoPopular::route('/{record}/edit'),
        ];
    }
}
