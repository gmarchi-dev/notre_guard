<?php

namespace App\Filament\Resources\DailyReports\Schemas;

use App\Models\DailyReport;
use App\Models\Incident;
use App\Models\PatrolScan;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DailyReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumo do dia')
                ->columns(4)
                ->schema([
                    TextEntry::make('unit.name')->label('Unidade'),
                    TextEntry::make('report_date')->label('Data')->date('d/m/Y'),
                    TextEntry::make('status')
                        ->label('Situação')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => DailyReport::STATUSES[$state] ?? $state)
                        ->color(fn (string $state) => $state === 'closed' ? 'success' : 'warning'),
                    TextEntry::make('closed_at')
                        ->label('Fechado em')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('shifts_total')
                        ->label('Turnos')
                        ->state(fn (DailyReport $r) => $r->summary['shifts']['total'] ?? 0),
                    TextEntry::make('patrols_total')
                        ->label('Rondas')
                        ->state(fn (DailyReport $r) => sprintf(
                            '%d (%d incompleta(s))',
                            $r->summary['patrols']['total'] ?? 0,
                            $r->summary['patrols']['incomplete'] ?? 0,
                        )),
                    TextEntry::make('adherence')
                        ->label('Aderência')
                        ->state(fn (DailyReport $r) => $r->summary['patrols']['adherence'] !== null
                            ? $r->summary['patrols']['adherence'].'%'
                            : '—')
                        ->badge()
                        ->color(fn (DailyReport $r) => match (true) {
                            ($r->summary['patrols']['adherence'] ?? null) === null => 'gray',
                            $r->summary['patrols']['adherence'] >= 95 => 'success',
                            $r->summary['patrols']['adherence'] >= 80 => 'warning',
                            default => 'danger',
                        }),
                    TextEntry::make('incidents_total')
                        ->label('Ocorrências')
                        ->state(fn (DailyReport $r) => $r->summary['incidents']['total'] ?? 0),
                ]),

            Section::make('Qualidade das leituras')
                ->visible(fn (DailyReport $r) => filled($r->summary['scans']['by_deviation'] ?? []))
                ->schema([
                    TextEntry::make('deviations')
                        ->label('Desvios registrados')
                        ->badge()
                        ->color('danger')
                        ->state(fn (DailyReport $r) => collect($r->summary['scans']['by_deviation'] ?? [])
                            ->map(fn (int $count, string $key) => (PatrolScan::DEVIATION_LABELS[$key] ?? $key).": {$count}")
                            ->values()
                            ->all()),
                ]),

            Section::make('Não conformidades')
                ->visible(fn (DailyReport $r) => ($r->summary['nonconformities']['total'] ?? 0) > 0)
                ->schema([
                    RepeatableEntry::make('nonconformities')
                        ->label('Itens apontados em ronda')
                        ->state(fn (DailyReport $r) => $r->summary['nonconformities']['items'] ?? [])
                        ->columns(4)
                        ->schema([
                            TextEntry::make('at')->label('Hora'),
                            TextEntry::make('checkpoint')->label('Ponto'),
                            TextEntry::make('item')->label('Item'),
                            TextEntry::make('note')->label('Observação')->placeholder('—'),
                        ]),
                ]),

            Section::make('Ocorrências do dia')
                ->visible(fn (DailyReport $r) => ($r->summary['incidents']['total'] ?? 0) > 0)
                ->schema([
                    RepeatableEntry::make('incidents')
                        ->label('Registros do dia')
                        ->state(fn (DailyReport $r) => $r->summary['incidents']['items'] ?? [])
                        ->columns(4)
                        ->schema([
                            TextEntry::make('number')->label('Número')->weight('bold'),
                            TextEntry::make('occurred_at')->label('Hora'),
                            TextEntry::make('type')->label('Tipo'),
                            TextEntry::make('severity')
                                ->label('Gravidade')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => Incident::SEVERITIES[$state] ?? $state),
                            TextEntry::make('description')
                                ->label('Relato')
                                ->columnSpanFull(),
                        ]),
                ]),

            Section::make('Observações da supervisão')
                ->schema([
                    Textarea::make('notes')
                        ->label('Anotações que entram no PDF')
                        ->rows(3)
                        ->disabled(fn (DailyReport $r) => $r->isClosed())
                        ->helperText(fn (DailyReport $r) => $r->isClosed()
                            ? 'RDO fechado: as observações fazem parte do documento selado.'
                            : 'Entra no PDF no fechamento.'),
                ]),

            Section::make('Integridade')
                ->collapsed()
                ->visible(fn (DailyReport $r) => $r->isClosed())
                ->schema([
                    TextEntry::make('closedBy.name')->label('Fechado por')->placeholder('—'),
                    TextEntry::make('content_hash')
                        ->label('Selo SHA-256')
                        ->copyable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
