<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeSheetTest extends TestCase
{
    use RefreshDatabase;

    private function unitWithCheckpoints(int $count = 3): Unit
    {
        $unit = Unit::create(['name' => 'Unidade Teste', 'code' => 'TST']);

        for ($i = 1; $i <= $count; $i++) {
            Checkpoint::create([
                'unit_id' => $unit->id,
                'code' => sprintf('PC-%02d', $i),
                'name' => "Ponto {$i}",
            ]);
        }

        return $unit;
    }

    public function test_sheet_is_not_public(): void
    {
        // O token impresso é o que autentica a passagem pelo ponto: se a folha
        // vazasse, qualquer um poderia "bater" a ronda sem estar no local.
        $unit = $this->unitWithCheckpoints(1);

        $this->get(route('qr.unit.checkpoints', $unit))->assertRedirect();
    }

    public function test_sheet_lists_every_active_checkpoint(): void
    {
        $unit = $this->unitWithCheckpoints(3);
        $unit->checkpoints()->first()->update(['active' => false]);

        $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('qr.unit.checkpoints', $unit));

        $response->assertOk();
        $response->assertSee('PC-02');
        $response->assertSee('PC-03');
        $response->assertDontSee('PC-01');
    }

    public function test_checkpoint_receives_an_unpredictable_token(): void
    {
        $unit = $this->unitWithCheckpoints(2);
        [$first, $second] = $unit->checkpoints()->orderBy('code')->get()->all();

        $this->assertNotEmpty($first->qr_token);
        $this->assertNotSame($first->qr_token, $second->qr_token);
        $this->assertSame(24, strlen($first->qr_token));

        // O payload carrega o prefixo do tipo para o app saber o que leu.
        $this->assertSame("CP:{$first->qr_token}", $first->qrPayload());
    }
}
