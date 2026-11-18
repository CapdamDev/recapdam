<?php

namespace App\Filament\Resources\Resguardos\Pages;

use App\Filament\Resources\Resguardos\ResguardoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateResguardo extends CreateRecord
{
    protected static string $resource = ResguardoResource::class;

    // Go straight to the view page so staff can upload evidencias.
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
