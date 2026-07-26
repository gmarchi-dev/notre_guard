<?php

namespace App\Filament\Portaria\Resources\KeyItems\Tables;

use App\Models\KeyHolder;
use App\Models\KeyItem;
use App\Services\KeyCustody;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class KeyItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('active', true)
                ->with(['currentLoan.holder', 'unit']))
            ->columns([
                TextColumn::make('code')
                    ->label('Gancho')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Abre')
                    ->description(fn (KeyItem $record) => $record->storage_location)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (KeyItem $record) => $record->currentLoan
                        ? 'Com '.$record->currentLoan->holder->name
                        : 'No quadro')
                    ->color(fn (KeyItem $record) => match (true) {
                        ! $record->currentLoan => 'success',
                        $record->currentLoan->isOverdue() => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('desde')
                    ->label('Retirada')
                    ->state(fn (KeyItem $record) => $record->currentLoan?->released_at->format('d/m H:i') ?? '—')
                    ->description(fn (KeyItem $record) => $record->currentLoan
                        ? ($record->currentLoan->isOverdue()
                            ? 'venceu '.$record->currentLoan->due_at->format('d/m H:i')
                            : 'até '.$record->currentLoan->due_at->format('d/m H:i'))
                        : null)
                    ->color(fn (KeyItem $record) => $record->currentLoan?->isOverdue() ? 'danger' : null),

                TextColumn::make('unit.name')
                    ->label('Unidade')
                    ->toggleable(),
            ])
            ->defaultSort('code')
            ->filters([
                Filter::make('out')
                    ->label('Somente fora do quadro')
                    ->query(fn (Builder $query) => $query->out()),
                Filter::make('overdue')
                    ->label('Somente atrasadas')
                    ->query(fn (Builder $query) => $query->whereHas(
                        'loans',
                        fn (Builder $q) => $q->overdue(),
                    )),
                SelectFilter::make('unit')
                    ->label('Unidade')
                    ->relationship('unit', 'name'),
            ])
            ->recordActions([
                Action::make('release')
                    ->label('Entregar')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->visible(fn (KeyItem $record) => ! $record->currentLoan)
                    ->schema([
                        Select::make('key_holder_id')
                            ->label('Quem está retirando')
                            ->options(fn () => KeyHolder::query()
                                ->where('active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (KeyHolder $h) => [$h->id => $h->label()]))
                            ->searchable()
                            ->required()
                            // Cadastro na hora: quem chega no balcão e não está
                            // na lista não pode virar motivo para anotar no papel.
                            ->createOptionForm([
                                TextInput::make('name')->label('Nome')->required(),
                                Select::make('kind')
                                    ->label('Vínculo')
                                    ->options(KeyHolder::KINDS)
                                    ->default('staff')
                                    ->required(),
                                TextInput::make('department')->label('Setor'),
                                TextInput::make('phone')->label('Telefone')->tel(),
                            ])
                            ->createOptionUsing(fn (array $data) => KeyHolder::create($data)->getKey()),

                        DateTimePicker::make('due_at')
                            ->label('Devolver até')
                            ->seconds(false)
                            ->default(fn () => now()->endOfDay()->subHours(2))
                            ->minDate(now())
                            ->required()
                            ->helperText('O atraso é contado a partir deste horário.'),

                        TextInput::make('purpose')
                            ->label('Motivo')
                            ->placeholder('Aula, manutenção, limpeza…'),
                    ])
                    ->action(function (KeyItem $record, array $data, KeyCustody $custody) {
                        try {
                            $custody->release(
                                $record,
                                KeyHolder::findOrFail($data['key_holder_id']),
                                auth()->user(),
                                \Illuminate\Support\Carbon::parse($data['due_at']),
                                $data['purpose'] ?? null,
                            );
                        } catch (RuntimeException $e) {
                            Notification::make()->title('Não foi possível entregar')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Chave entregue.')->success()->send();
                    }),

                Action::make('receive')
                    ->label('Receber')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (KeyItem $record) => (bool) $record->currentLoan)
                    ->modalHeading(fn (KeyItem $record) => 'Receber a chave de '.$record->currentLoan->holder->name)
                    ->schema([
                        Textarea::make('notes')
                            ->label('Observação')
                            ->placeholder('Só se houver algo a registrar')
                            ->rows(2),
                    ])
                    ->action(function (KeyItem $record, array $data, KeyCustody $custody) {
                        try {
                            $loan = $custody->receive($record->currentLoan, auth()->user(), $data['notes'] ?? null);
                        } catch (RuntimeException $e) {
                            Notification::make()->title('Não foi possível receber')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title('Chave devolvida.')
                            ->body($loan->overdueMinutes() > 0
                                ? 'Devolvida com '.$loan->overdueMinutes().' min de atraso.'
                                : 'No prazo.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Nenhuma chave cadastrada')
            ->emptyStateDescription('O cadastro do quadro é feito pela supervisão.')
            ->poll('60s');
    }
}
