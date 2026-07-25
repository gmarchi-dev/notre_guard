@php
    $severities = \App\Models\Incident::SEVERITIES;
    $classifications = \App\Models\Incident::CLASSIFICATIONS;
    $deviationLabels = \App\Models\PatrolScan::DEVIATION_LABELS;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>RDO {{ $report->unit->code }} {{ $report->report_date->format('d/m/Y') }}</title>
    <style>
        @page { margin: 22mm 16mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 11px; margin: 18px 0 6px; text-transform: uppercase; letter-spacing: .06em;
             border-bottom: 1px solid #999; padding-bottom: 3px; }
        .sub { color: #555; font-size: 10px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { text-align: left; padding: 4px 5px; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background: #f2f2f2; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
        .numbers td { border: 0; padding: 2px 0; }
        .numbers .k { color: #555; width: 62%; }
        .numbers .v { text-align: right; font-weight: bold; }
        .cols { width: 100%; }
        .cols td { border: 0; vertical-align: top; padding: 0 8px 0 0; width: 33%; }
        .empty { color: #777; font-style: italic; padding: 6px 0; }
        .foot { margin-top: 22px; padding-top: 8px; border-top: 1px solid #999; font-size: 9px; color: #555; }
        .hash { font-family: DejaVu Sans Mono, monospace; font-size: 8px; word-break: break-all; }
        .badge { font-weight: bold; }
    </style>
</head>
<body>

<h1>Relatório Diário de Ocorrências</h1>
<p class="sub">
    {{ $report->unit->name }} ({{ $report->unit->code }}) —
    {{ $report->report_date->format('d/m/Y') }}
</p>

<h2>Resumo do dia</h2>
<table class="cols">
    <tr>
        <td>
            <table class="numbers">
                <tr><td class="k">Turnos</td><td class="v">{{ $summary['shifts']['total'] }}</td></tr>
                <tr><td class="k">Rondas realizadas</td><td class="v">{{ $summary['patrols']['total'] }}</td></tr>
                <tr><td class="k">Rondas incompletas</td><td class="v">{{ $summary['patrols']['incomplete'] }}</td></tr>
            </table>
        </td>
        <td>
            <table class="numbers">
                <tr><td class="k">Pontos lidos</td><td class="v">{{ $summary['patrols']['scanned_checkpoints'] }} / {{ $summary['patrols']['expected_checkpoints'] }}</td></tr>
                <tr><td class="k">Aderência</td><td class="v">{{ $summary['patrols']['adherence'] !== null ? $summary['patrols']['adherence'].'%' : '—' }}</td></tr>
                <tr><td class="k">Leituras com desvio</td><td class="v">{{ $summary['scans']['with_deviation'] }}</td></tr>
            </table>
        </td>
        <td>
            <table class="numbers">
                <tr><td class="k">Ocorrências</td><td class="v">{{ $summary['incidents']['total'] }}</td></tr>
                <tr><td class="k">Não conformidades</td><td class="v">{{ $summary['nonconformities']['total'] }}</td></tr>
                <tr><td class="k">Pontos não realizados</td><td class="v">{{ $summary['scans']['skipped'] }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<h2>Turnos</h2>
@if ($summary['shifts']['total'] === 0)
    <p class="empty">Nenhum turno registrado nesta data.</p>
@else
    <table>
        <thead>
        <tr><th>Vigilante</th><th>Matrícula</th><th>Posto</th><th>Início</th><th>Fim</th><th>Passagem de serviço</th></tr>
        </thead>
        <tbody>
        @foreach ($summary['shifts']['items'] as $shift)
            <tr>
                <td>{{ $shift['guard'] }}</td>
                <td>{{ $shift['registration'] ?? '—' }}</td>
                <td>{{ $shift['post'] }}</td>
                <td>{{ $shift['started_at'] }}</td>
                <td>{{ $shift['ended_at'] ?? 'em aberto' }}</td>
                <td>{{ $shift['handover_notes'] ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Rondas</h2>
@if ($summary['patrols']['total'] === 0)
    <p class="empty">Nenhuma ronda registrada nesta data.</p>
@else
    <table>
        <thead>
        <tr><th>Roteiro</th><th>Vigilante</th><th>Início</th><th>Fim</th><th>Pontos</th><th>Situação</th></tr>
        </thead>
        <tbody>
        @foreach ($summary['patrols']['items'] as $patrol)
            <tr>
                <td>{{ $patrol['route'] }}</td>
                <td>{{ $patrol['guard'] }}</td>
                <td>{{ $patrol['started_at'] }}</td>
                <td>{{ $patrol['ended_at'] ?? '—' }}</td>
                <td>{{ $patrol['scanned'] }} / {{ $patrol['expected'] }}</td>
                <td>{{ $patrol['status'] === 'completed' ? 'Concluída' : ($patrol['status'] === 'in_progress' ? 'Em andamento' : 'Abandonada') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if (! empty($summary['scans']['by_deviation']))
        <table style="margin-top:10px">
            <thead><tr><th>Desvio nas leituras</th><th style="width:60px">Qtde</th></tr></thead>
            <tbody>
            @foreach ($summary['scans']['by_deviation'] as $deviation => $count)
                <tr>
                    <td>{{ $deviationLabels[$deviation] ?? $deviation }}</td>
                    <td>{{ $count }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endif

<h2>Não conformidades de checklist</h2>
@if ($summary['nonconformities']['total'] === 0)
    <p class="empty">Nenhuma não conformidade registrada.</p>
@else
    <table>
        <thead><tr><th style="width:60px">Hora</th><th style="width:70px">Ponto</th><th>Item</th><th>Observação</th></tr></thead>
        <tbody>
        @foreach ($summary['nonconformities']['items'] as $item)
            <tr>
                <td>{{ $item['at'] ?? '—' }}</td>
                <td>{{ $item['checkpoint'] }}</td>
                <td>{{ $item['item'] }}</td>
                <td>{{ $item['note'] ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Ocorrências</h2>
@if ($summary['incidents']['total'] === 0)
    <p class="empty">Nenhuma ocorrência registrada nesta data.</p>
@else
    @foreach ($summary['incidents']['items'] as $incident)
        <table style="margin-bottom:10px">
            <tr>
                <th style="width:90px">{{ $incident['number'] }}</th>
                <th style="width:50px">{{ $incident['occurred_at'] }}</th>
                <th>{{ $incident['type'] }}</th>
                <th style="width:70px">{{ $severities[$incident['severity']] ?? $incident['severity'] }}</th>
                <th style="width:80px">{{ $classifications[$incident['classification']] ?? $incident['classification'] }}</th>
            </tr>
            <tr>
                <td colspan="5">
                    <strong>Local:</strong> {{ $incident['location'] ?: '—' }} &nbsp;·&nbsp;
                    <strong>Registrada por:</strong> {{ $incident['reported_by'] }}
                </td>
            </tr>
            <tr><td colspan="5">{{ $incident['description'] }}</td></tr>
            @if ($incident['actions_taken'])
                <tr><td colspan="5"><strong>Providências:</strong> {{ $incident['actions_taken'] }}</td></tr>
            @endif
        </table>
    @endforeach
@endif

@if ($report->notes)
    <h2>Observações da supervisão</h2>
    <p>{{ $report->notes }}</p>
@endif

<div class="foot">
    <p>
        <span class="badge">Fechado por:</span> {{ $report->closedBy?->name ?? '—' }} em
        {{ $report->closed_at?->format('d/m/Y H:i') ?? '—' }}
    </p>
    <p>
        Selo de integridade (SHA-256) — qualquer alteração posterior nos registros deste dia
        muda este valor:
    </p>
    <p class="hash">{{ $report->content_hash }}</p>
    <p>Notre Guard · documento gerado eletronicamente</p>
</div>

</body>
</html>
