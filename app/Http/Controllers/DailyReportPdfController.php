<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyReportPdfController extends Controller
{
    public function __invoke(DailyReport $dailyReport): StreamedResponse
    {
        $user = auth()->user();

        // O escopo do painel não protege um link direto: o gestor de unidade não
        // pode baixar o RDO de outra unidade digitando o id na URL.
        abort_if(
            $user->isScopedToUnit() && $dailyReport->unit_id !== $user->unit_id,
            403,
        );

        abort_unless(filled($dailyReport->pdf_path) && Storage::exists($dailyReport->pdf_path), 404);

        return Storage::download(
            $dailyReport->pdf_path,
            sprintf(
                'RDO-%s-%s.pdf',
                $dailyReport->unit->code,
                $dailyReport->report_date->format('Y-m-d'),
            ),
        );
    }
}
