<?php

namespace Tests\Feature;

use App\Models\KeyHolder;
use App\Models\KeyItem;
use App\Models\KeyLoan;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\KeysOverdue;
use App\Services\DailyReportBuilder;
use App\Services\KeyCustody;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * Controle de chaves da portaria. O que se protege aqui é a integridade do
 * livro: uma chave não pode estar com duas pessoas, e uma devolução não pode
 * sumir do histórico.
 */
class KeyControlTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    private KeyItem $key;

    private KeyHolder $holder;

    private User $porteiro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create(['name' => 'Matriz', 'code' => 'MTZ']);

        $this->key = KeyItem::create([
            'unit_id' => $this->unit->id,
            'code' => '12A',
            'name' => 'Sala 203',
            'storage_location' => 'Quadro da portaria',
        ]);

        $this->holder = KeyHolder::create([
            'name' => 'Maria Souza',
            'kind' => 'teacher',
            'department' => 'Ensino Fundamental',
        ]);

        $this->porteiro = User::factory()->create(['role' => User::ROLE_GUARD]);
    }

    private function custody(): KeyCustody
    {
        return app(KeyCustody::class);
    }

    // ------------------------------------------------------------- retirada

    public function test_releasing_a_key_records_who_when_and_the_deadline(): void
    {
        $due = now()->addHours(4);

        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, $due, 'Aula de reforço');

        $this->assertTrue($loan->isOpen());
        $this->assertSame($this->holder->id, $loan->key_holder_id);
        $this->assertSame($this->porteiro->id, $loan->released_by_user_id);
        $this->assertSame('Aula de reforço', $loan->purpose);
        $this->assertSame($due->format('Y-m-d H:i'), $loan->due_at->format('Y-m-d H:i'));
        $this->assertTrue($this->key->refresh()->isOut());
    }

    public function test_a_key_cannot_be_in_two_hands_at_once(): void
    {
        // A garantia central do livro. Sem isto, um segundo registro faria a
        // chave constar com duas pessoas e o quadro deixaria de ser confiável.
        $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHours(2));

        $outro = KeyHolder::create(['name' => 'João Lima']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Maria Souza');

        $this->custody()->release($this->key, $outro, $this->porteiro, now()->addHours(2));
    }

    public function test_inactive_key_and_inactive_holder_are_refused(): void
    {
        $this->key->update(['active' => false]);

        try {
            $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHour());
            $this->fail('chave inativa deveria ser recusada');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('inativa', $e->getMessage());
        }

        $this->key->update(['active' => true]);
        $this->holder->update(['active' => false]);

        $this->expectException(RuntimeException::class);
        $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHour());
    }

    // ------------------------------------------------------------ devolução

    public function test_returning_frees_the_key_and_keeps_the_history(): void
    {
        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHours(2));

        $outroPorteiro = User::factory()->create(['role' => User::ROLE_GUARD]);
        $returned = $this->custody()->receive($loan, $outroPorteiro, 'Sem observações');

        $this->assertFalse($returned->isOpen());
        // Quem entregou e quem recebeu podem ser pessoas diferentes: a chave
        // atravessa o turno.
        $this->assertSame($this->porteiro->id, $returned->released_by_user_id);
        $this->assertSame($outroPorteiro->id, $returned->received_by_user_id);

        $this->assertFalse($this->key->refresh()->isOut());
        $this->assertSame(1, KeyLoan::count(), 'a devolução não apaga a retirada');
    }

    public function test_the_same_key_can_be_released_again_after_return(): void
    {
        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHour());
        $this->custody()->receive($loan, $this->porteiro);

        $again = $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHours(3));

        $this->assertTrue($again->isOpen());
        $this->assertSame(2, KeyLoan::count());
    }

    public function test_returning_twice_is_refused(): void
    {
        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHour());
        $this->custody()->receive($loan, $this->porteiro);

        $this->expectException(RuntimeException::class);
        $this->custody()->receive($loan->refresh(), $this->porteiro);
    }

    // --------------------------------------------------------------- atraso

    public function test_overdue_is_measured_against_the_declared_deadline(): void
    {
        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHour());

        $this->assertFalse($loan->isOverdue());
        $this->assertSame(0, $loan->overdueMinutes());

        $loan->update(['due_at' => now()->subMinutes(90)]);

        $this->assertTrue($loan->refresh()->isOverdue());
        $this->assertEqualsWithDelta(90, $loan->overdueMinutes(), 1);
    }

    public function test_a_returned_key_keeps_the_delay_that_happened(): void
    {
        // Devolvida com atraso continua sendo devolvida com atraso: é o que se
        // usa para conversar com quem sempre devolve fora do prazo.
        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->subHours(2));
        $this->custody()->receive($loan, $this->porteiro);

        $this->assertFalse($loan->refresh()->isOverdue(), 'já foi devolvida');
        $this->assertEqualsWithDelta(120, $loan->overdueMinutes(), 2);
    }

    public function test_overdue_notification_lists_the_keys_per_unit(): void
    {
        Notification::fake();

        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHour());
        $loan->update(['due_at' => now()->subHour()]);

        $this->artisan('notre-guard:overdue-keys')->assertSuccessful();

        Notification::assertSentTo(
            $supervisor,
            KeysOverdue::class,
            fn (KeysOverdue $n) => $n->loans->count() === 1
                && $n->loans->first()->keyItem->code === '12A',
        );
    }

    public function test_no_notification_when_everything_is_on_time(): void
    {
        Notification::fake();

        User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
        $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHours(5));

        $this->artisan('notre-guard:overdue-keys')->assertSuccessful();

        Notification::assertNothingSent();
    }

    // ------------------------------------------------------------------ RDO

    public function test_daily_report_carries_the_keys_still_out(): void
    {
        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, now()->addHour());
        $loan->update(['due_at' => now()->subHour()]);

        $summary = app(DailyReportBuilder::class)
            ->buildOrUpdate($this->unit, Carbon::today())
            ->summary;

        $this->assertSame(1, $summary['keys']['released']);
        $this->assertSame(1, $summary['keys']['outstanding']);
        $this->assertSame(1, $summary['keys']['overdue']);
        $this->assertSame('Maria Souza', $summary['keys']['items'][0]['holder']);
    }

    public function test_yesterdays_report_is_not_changed_by_a_return_today(): void
    {
        // "Em aberto" é medido no fim do dia do relatório. Um RDO de ontem não
        // pode mudar porque a chave voltou hoje de manhã.
        $yesterday = Carbon::yesterday();

        $loan = $this->custody()->release($this->key, $this->holder, $this->porteiro, $yesterday->copy()->addHours(2));
        $loan->update(['released_at' => $yesterday->copy()->setHour(8)]);

        $this->custody()->receive($loan->refresh(), $this->porteiro);

        $summary = app(DailyReportBuilder::class)
            ->buildOrUpdate($this->unit, $yesterday)
            ->summary;

        $this->assertSame(1, $summary['keys']['outstanding'], 'ontem a chave estava fora');
    }

    public function test_report_without_key_movement_stays_empty(): void
    {
        $summary = app(DailyReportBuilder::class)
            ->buildOrUpdate($this->unit, Carbon::today())
            ->summary;

        $this->assertSame(0, $summary['keys']['released']);
        $this->assertSame([], $summary['keys']['items']);
    }
}
