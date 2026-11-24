<?php

namespace App\Filament\Resources\SessionResource\RelationManagers;

use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';
    protected static ?string $title = 'Booked Attendees & Spots';
    protected static ?string $icon = 'heroicon-o-user-group';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Client / Member'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending Payment',
                        'paid' => 'Paid / Confirmed',
                        'attended' => 'Attended',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('paid')
                    ->label('Status'),
                Forms\Components\TextInput::make('amount_paid')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->label('Amount Paid (USD)'),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client Name')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => fn ($state) => in_array($state, ['paid', 'attended', 'confirmed']),
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->money('usd')
                    ->label('Amount Paid'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y · g:i A')
                    ->label('Booked On')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Attendee')
                    ->icon('heroicon-m-user-plus')
                    ->before(function (Tables\Actions\CreateAction $action) {
                        $session = $this->getOwnerRecord();
                        if ($session->is_full) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Session is Full')
                                ->body("This session has already reached maximum capacity ({$session->max_seats} seats).")
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Cancel Booking'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
