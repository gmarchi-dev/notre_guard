<?php

namespace Tests\Feature;

use App\Filament\Portaria\Widgets\KeyBoardOverview;
use App\Models\KeyHolder;
use App\Models\KeyItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\KeyCustody;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Resumo do quadro de chaves.
 *
 * A pergunta da portaria na troca de turno é sempre a mesma — o que está fora e
 * o que está atrasado. Antes a resposta exigia ler a tabela linha a linha.
 */
class KeyBoardOverviewTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    private User $porteiro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create(['name' => 'Matriz', 'code' => 'MTZ']);

        $this->porteiro = User::factory()->create([
            'role' => User::ROLE_GUARD,
            'permissions' => [User::PERMISSION_KEYS],
        ]);
    }

    private function key(string $code): KeyItem
    {
        return KeyItem::create([
            'unit_id' => $this->unit->id,
            'code' => $code,
            'name' => "Sala {$code}",
            'storage_location' => 'Quadro da portaria',
        ]);
    }

    private function holder(): KeyHolder
    {
        return KeyHolder::create(['name' => 'Maria Souza', 'kind' => 'teacher']);
    }

    private function stats(): array
    {
        $widget = Livewire::actingAs($this->porteiro)->test(KeyBoardOverview::class);

        return array_map(
            fn ($stat) => ['label' => $stat->getLabel(), 'value' => $stat->getValue()],
            invade($widget->instance())->getStats(),
        );
    }

    public function test_an_untouched_board_reports_everything_in_place(): void
    {
        $this->key('01');
        $this->key('02');

        $this->assertSame(
            [['label' => 'No quadro', 'value' => '2'],
                ['label' => 'Fora do quadro', 'value' => '0'],
                ['label' => 'Atrasadas', 'value' => '0']],
            $this->stats(),
        );
    }

    public function test_a_released_key_moves_from_the_board_to_out(): void
    {
        $key = $this->key('01');
        $this->key('02');

        app(KeyCustody::class)->release($key, $this->holder(), $this->porteiro, now()->addHours(4));

        $stats = $this->stats();

        $this->assertSame('1', $stats[0]['value'], 'uma chave continua no quadro');
        $this->assertSame('1', $stats[1]['value'], 'a outra saiu');
        $this->assertSame('0', $stats[2]['value'], 'ainda dentro do prazo');
    }

    public function test_passing_the_deadline_is_what_makes_a_key_late(): void
    {
        // Fora do quadro e atrasada são coisas diferentes: quase toda chave
        // passa o dia fora, e isso é normal.
        $key = $this->key('01');

        app(KeyCustody::class)->release($key, $this->holder(), $this->porteiro, now()->addHour());

        $this->assertSame('0', $this->stats()[2]['value']);

        $this->travel(2)->hours();

        $this->assertSame('1', $this->stats()[2]['value']);
    }

    public function test_returning_a_late_key_clears_the_count(): void
    {
        $key = $this->key('01');
        $custody = app(KeyCustody::class);

        $loan = $custody->release($key, $this->holder(), $this->porteiro, now()->addHour());

        $this->travel(2)->hours();
        $this->assertSame('1', $this->stats()[2]['value']);

        $custody->receive($loan, $this->porteiro);

        $stats = $this->stats();
        $this->assertSame('1', $stats[0]['value'], 'voltou para o quadro');
        $this->assertSame('0', $stats[2]['value'], 'atraso deixa de ser pendência ao devolver');
    }

    public function test_inactive_keys_do_not_inflate_the_board(): void
    {
        $this->key('01');
        $this->key('02')->update(['active' => false]);

        $this->assertSame('1', $this->stats()[0]['value']);
    }

    public function test_the_widget_stays_behind_the_key_permission(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_GUARD]));
        $this->assertFalse(KeyBoardOverview::canView());

        $this->actingAs($this->porteiro);
        $this->assertTrue(KeyBoardOverview::canView());
    }

    public function test_the_board_page_carries_the_summary_and_the_overdue_highlight(): void
    {
        $key = $this->key('01');

        app(KeyCustody::class)->release($key, $this->holder(), $this->porteiro, now()->subHour());

        $html = $this->actingAs($this->porteiro)->get('/portaria/key-items')->assertOk()->getContent();

        // O widget é lazy: no HTML inicial vem o invólucro, e o conteúdo chega
        // por Livewire. Os números em si estão cobertos pelos testes acima.
        $this->assertStringContainsString(
            'key-board-overview',
            str_replace('\\', '-', strtolower($html)),
            'o resumo não foi montado na página do quadro',
        );

        // Sem o <style>, senão a asserção passaria só por causa da própria
        // folha de estilo, sem nenhuma linha ter recebido a classe.
        $body = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);

        $this->assertStringContainsString(
            'ng-row-overdue',
            $body,
            'a linha da chave atrasada não recebeu o realce',
        );
    }

    public function test_a_key_within_its_deadline_gets_no_highlight(): void
    {
        // Quase toda chave passa o dia fora, e isso é o normal: se a linha se
        // destacasse sempre, o destaque não significaria nada.
        $key = $this->key('01');

        app(KeyCustody::class)->release($key, $this->holder(), $this->porteiro, now()->addHours(4));

        $html = $this->actingAs($this->porteiro)->get('/portaria/key-items')->assertOk()->getContent();
        $body = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);

        $this->assertStringNotContainsString('ng-row-overdue', $body);
    }
}
