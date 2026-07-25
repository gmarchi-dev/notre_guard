<?php

namespace App\Http\Controllers;

use App\Models\Checkpoint;
use App\Models\Post;
use App\Models\Unit;
use Illuminate\Contracts\View\View;

/**
 * Folhas de QR Code prontas para impressão e plastificação.
 */
class QrCodeController extends Controller
{
    public function post(Post $post): View
    {
        $post->load('unit');

        return view('qr.sheet', [
            'title' => 'Posto — '.$post->name,
            'tags' => [[
                'heading' => $post->unit->name,
                'title' => $post->name,
                'subtitle' => Post::KINDS[$post->kind] ?? $post->kind,
                'payload' => $post->qrPayload(),
            ]],
        ]);
    }

    public function checkpoint(Checkpoint $checkpoint): View
    {
        $checkpoint->load('unit');

        return view('qr.sheet', [
            'title' => 'Ponto — '.$checkpoint->code,
            'tags' => [$this->checkpointTag($checkpoint)],
        ]);
    }

    /**
     * Todos os pontos ativos de uma unidade, um por folha — o caso real de uso
     * na implantação de um site novo.
     */
    public function unitCheckpoints(Unit $unit): View
    {
        $checkpoints = $unit->checkpoints()->where('active', true)->orderBy('code')->get();

        return view('qr.sheet', [
            'title' => 'Pontos de controle — '.$unit->name,
            'tags' => $checkpoints->map(fn (Checkpoint $c) => $this->checkpointTag($c, $unit))->all(),
        ]);
    }

    private function checkpointTag(Checkpoint $checkpoint, ?Unit $unit = null): array
    {
        return [
            'heading' => ($unit ?? $checkpoint->unit)->name,
            'title' => $checkpoint->code,
            'subtitle' => $checkpoint->name,
            'payload' => $checkpoint->qrPayload(),
        ];
    }
}
