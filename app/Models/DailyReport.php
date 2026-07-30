<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RDO - Relatório Diário de Ocorrências, por unidade e data.
 */
class DailyReport extends Model
{
    public const STATUSES = [
        'draft' => 'Rascunho',
        'closed' => 'Fechado',
    ];

    protected $fillable = [
        'unit_id', 'report_date', 'status', 'summary', 'notes',
        'closed_by_user_id', 'closed_at', 'pdf_path', 'content_hash', 'data_purged_at',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'closed_at' => 'datetime',
            'data_purged_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    /**
     * Os dados de campo da data já foram eliminados pela política de retenção.
     * O documento continua válido; o que não dá mais é recalculá-lo.
     */
    public function dataWasPurged(): bool
    {
        return $this->data_purged_at !== null;
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
