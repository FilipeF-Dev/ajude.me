<?php

namespace App\Filament\Resources\Proficiencias\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProficienciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subcategoria_id')
                    ->relationship('subcategoria', 'id')
                    ->required(),
                TextInput::make('nome')
                    ->required(),
                Toggle::make('ativa')
                    ->required(),
                Toggle::make('restrita')
                    ->required(),
                TextInput::make('motivo_restricao'),
            ]);
    }
}
