<?php

namespace App\Filament\Resources\RetentionRuns\Tables;

use App\Models\RetentionRun;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RetentionRunsTable
{
    private const LABELS = [
        'incidents' => 'ocorrências',
        'shifts' => 'turnos',
        'patrols' => 'rondas',
        'evidence_files' => 'evidências',
        'orphan_attachments' => 'órfãs',
        'sync_batches' => 'logs de sync',
        'notifications' => 'notificações',
        'reports_marked' => 'RDOs marcados',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('started_at')
                    ->label('Execução')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('dry_run')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Simulação' : 'Expurgo')
                    ->color(fn (bool $state) => $state ? 'gray' : 'warning'),
                TextColumn::make('total')
                    ->label('Registros')
                    ->state(fn (RetentionRun $record) => $record->totalRemoved())
                    ->alignCenter(),
                TextColumn::make('summary')
                    ->label('Detalhe')
                    ->badge()
                    ->state(fn (RetentionRun $record) => collect($record->summary ?? [])
                        ->filter(fn ($value) => is_int($value) && $value > 0)
                        ->map(fn (int $value, string $key) => (self::LABELS[$key] ?? $key).": {$value}")
                        ->values()
                        ->all() ?: ['nada vencido'])
                    ->color(fn (RetentionRun $record) => $record->totalRemoved() > 0 ? 'warning' : 'success'),
                TextColumn::make('policy')
                    ->label('Prazos vigentes')
                    ->state(fn (RetentionRun $record) => sprintf(
                        'evidência %dm · ronda %dm · ocorrência %da',
                        $record->policy['evidence_months'] ?? 0,
                        $record->policy['patrol_months'] ?? 0,
                        $record->policy['incident_years'] ?? 0,
                    ))
                    ->toggleable(),
                TextColumn::make('error')
                    ->label('Falha')
                    ->placeholder('—')
                    ->color('danger')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                TernaryFilter::make('dry_run')
                    ->label('Simulações')
                    ->placeholder('Todas as execuções')
                    ->trueLabel('Somente simulações')
                    ->falseLabel('Somente expurgos reais'),
            ])
            ->emptyStateHeading('Nenhum expurgo registrado')
            ->emptyStateDescription('O expurgo roda diariamente às 03:30. Exige o agendador do Laravel ativo.');
    }
}
