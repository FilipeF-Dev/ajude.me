<?php

namespace App\Filament\Resources\TermoPopulars\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TermoPopularForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('proficiencia_id')
                    ->relationship('proficiencia', 'id')
                    ->required(),
                TextInput::make('termo')
                    ->required(),
                TextInput::make('termo_normalizado')
                    ->required(),
                TextInput::make('criado_por')
                    ->numeric(),
            ]);
    }
}
