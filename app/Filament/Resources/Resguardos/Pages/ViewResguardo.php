<?php

namespace App\Filament\Resources\Resguardos\Pages;

use App\Filament\Resources\Resguardos\Actions\DescargarPdfAction;
use App\Filament\Resources\Resguardos\Actions\RegistrarDevolucionAction;
use App\Filament\Resources\Resguardos\ResguardoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewResguardo extends ViewRecord
{
    protected static string $resource = ResguardoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DescargarPdfAction::make(),
            RegistrarDevolucionAction::make(),
            EditAction::make(),
        ];
    }
}
