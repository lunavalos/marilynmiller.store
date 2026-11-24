<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Access Control';
    protected static ?string $modelLabel = 'Role';
    protected static ?string $pluralModelLabel = 'Roles';

    public static function form(Form $form): Form
    {
        $resources = \Filament\Facades\Filament::getPanel('admin')->getResources();
        $permissionBlocks = [];

        foreach ($resources as $resource) {
            $modelLabel = str($resource::getModelLabel())->plural()->title()->toString();
            $slug = str($resource::getModelLabel())->plural()->slug('_')->toString();

            $permissionBlocks[] = Forms\Components\Fieldset::make($modelLabel)
                ->schema([
                    Forms\Components\CheckboxList::make("permissions_{$slug}")
                        ->label('')
                        ->options([
                            "view_any_{$slug}" => 'View Any',
                            "view_{$slug}" => 'View',
                            "create_{$slug}" => 'Create',
                            "update_{$slug}" => 'Update',
                            "delete_{$slug}" => 'Delete',
                            "delete_any_{$slug}" => 'Delete Any',
                        ])
                        ->columns(2)
                        ->bulkToggleable()
                        ->default([])
                        ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?Role $record) use ($slug) {
                            if ($record) {
                                $permissions = $record->permissions()
                                    ->pluck('name')
                                    ->filter(fn ($name) => str_ends_with($name, "_{$slug}"))
                                    ->values()
                                    ->toArray();

                                $component->state($permissions);
                            } else {
                                $component->state([]);
                            }
                        })
                ])
                ->columnSpan(1);
        }

        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema($permissionBlocks)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('permissions.name')
                    ->label('Permissions')
                    ->badge()
                    ->separator(',')
                    ->limitList(3),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, Role $record): array {
                        $rolePermissions = $record->permissions()->pluck('name')->toArray();
                        $resources = \Filament\Facades\Filament::getPanel('admin')->getResources();

                        foreach ($resources as $resource) {
                            $slug = str($resource::getModelLabel())->plural()->slug('_')->toString();
                            $data["permissions_{$slug}"] = array_values(
                                array_filter($rolePermissions, fn ($name) => str_ends_with($name, "_{$slug}"))
                            );
                        }

                        return $data;
                    })
                    ->using(function (array $data, Role $record) {
                        $permissions = [];
                        foreach ($data as $key => $value) {
                            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                                $permissions = array_merge($permissions, array_values($value));
                            }
                        }

                        $record->update(['name' => $data['name']]);
                        $record->syncPermissions(array_values(array_unique(array_filter($permissions))));
                        $record->unsetRelation('permissions');

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
