<?php

/*
|--------------------------------------------------------------------------
| Retenção de dados (LGPD)
|--------------------------------------------------------------------------
|
| Prazos definidos no plano de implantação. O sistema trata dado pessoal de
| colaborador - localização durante a ronda, foto, nome - e de terceiros
| citados em ocorrência. Guardar além do necessário é tratamento sem base.
|
| Alterar prazo aqui é decisão de negócio: registre o motivo em
| docs/10-lgpd-e-retencao.md antes de mudar.
|
*/

return [
    /*
     * Evidências (foto, vídeo, áudio). O binário é apagado; a linha permanece
     * com o hash, provando o que existiu sem guardar o conteúdo.
     */
    'evidence_months' => (int) env('RETENTION_EVIDENCE_MONTHS', 12),

    /*
     * Turnos, rondas e leituras. É onde mora a localização do vigilante - o
     * dado mais sensível do sistema e o de menor valor histórico.
     */
    'patrol_months' => (int) env('RETENTION_PATROL_MONTHS', 12),

    /*
     * Ocorrências e RDO. Prazo maior: são os documentos que podem ser exigidos
     * em processo administrativo ou judicial.
     */
    'incident_years' => (int) env('RETENTION_INCIDENT_YEARS', 5),

    /*
     * Logs técnicos de sincronização (lotes recebidos, aparelho, erros).
     */
    'sync_log_months' => (int) env('RETENTION_SYNC_LOG_MONTHS', 6),

    /*
     * Notificações lidas ou não no painel - carregam resumo de ocorrência.
     */
    'notification_months' => (int) env('RETENTION_NOTIFICATION_MONTHS', 12),
];
