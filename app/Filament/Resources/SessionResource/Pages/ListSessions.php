<?php

namespace App\Filament\Resources\SessionResource\Pages;

use App\Filament\Pages\SessionsCalendar;
use App\Filament\Resources\SessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSessions extends ListRecords
{
    protected static string $resource = SessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendarView')
                ->label('Switch to Calendar View')
                ->icon('heroicon-m-calendar')
                ->color('primary')
                ->url(fn (): string => SessionsCalendar::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
