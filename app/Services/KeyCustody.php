<?php

namespace App\Services;

use App\Models\KeyHolder;
use App\Models\KeyItem;
use App\Models\KeyLoan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Guarda e liberação de chaves pela portaria.
 */
class KeyCustody
{
    /**
     * @throws RuntimeException quando a chave já está fora ou está inativa
     */
    public function release(
        KeyItem $key,
        KeyHolder $holder,
        User $releasedBy,
        Carbon $dueAt,
        ?string $purpose = null,
    ): KeyLoan {
        return DB::transaction(function () use ($key, $holder, $releasedBy, $dueAt, $purpose) {
            // Bloqueio na linha da chave: duas portarias registrando a mesma
            // retirada ao mesmo tempo criariam dois empréstimos abertos, e a
            // chave passaria a estar "com duas pessoas".
            $key = KeyItem::whereKey($key->getKey())->lockForUpdate()->firstOrFail();

            if (! $key->active) {
                throw new RuntimeException('Esta chave está inativa e não pode ser liberada.');
            }

            $open = $key->loans()->open()->with('holder')->first();

            if ($open) {
                throw new RuntimeException(
                    "Esta chave já está com {$open->holder->name} desde ".$open->released_at->format('d/m H:i').'.',
                );
            }

            if (! $holder->active) {
                throw new RuntimeException('Este solicitante está inativo.');
            }

            return KeyLoan::create([
                'key_item_id' => $key->id,
                'key_holder_id' => $holder->id,
                'unit_id' => $key->unit_id,
                'released_by_user_id' => $releasedBy->id,
                'shift_id' => $releasedBy->securityGuard?->openShift()?->id,
                'released_at' => now(),
                'due_at' => $dueAt,
                'purpose' => $purpose,
            ]);
        });
    }

    /**
     * @throws RuntimeException quando o empréstimo já foi encerrado
     */
    public function receive(KeyLoan $loan, User $receivedBy, ?string $notes = null): KeyLoan
    {
        if (! $loan->isOpen()) {
            throw new RuntimeException('Esta chave já foi devolvida em '.$loan->returned_at->format('d/m/Y H:i').'.');
        }

        $loan->update([
            'returned_at' => now(),
            'received_by_user_id' => $receivedBy->id,
            'notes' => $notes,
        ]);

        return $loan->refresh();
    }

    /**
     * Chaves fora do prazo. É o que a portaria cobra e o que entra no RDO.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, KeyLoan>
     */
    public function overdue(?int $unitId = null)
    {
        return KeyLoan::query()
            ->overdue()
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->with(['keyItem', 'holder'])
            ->orderBy('due_at')
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, KeyLoan> */
    public function outstanding(?int $unitId = null)
    {
        return KeyLoan::query()
            ->open()
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->with(['keyItem', 'holder'])
            ->orderBy('due_at')
            ->get();
    }
}
