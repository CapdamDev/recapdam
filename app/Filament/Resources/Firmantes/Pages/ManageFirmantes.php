<?php

namespace App\Filament\Resources\Firmantes\Pages;

use App\Enums\RolFirma;
use App\Filament\Resources\Firmantes\FirmanteResource;
use App\Models\Firmante;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFirmantes extends ManageRecords
{
    protected static string $resource = FirmanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // Only one firmante per fixed role.
                ->visible(fn () => Firmante::count() < count(RolFirma::fijos())),
        ];
    }
}
