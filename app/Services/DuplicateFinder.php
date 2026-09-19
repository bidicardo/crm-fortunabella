<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DuplicateFinder
{
    /**
     * Не архивные клиенты с тем же нормализованным телефоном и/или email.
     * Пустые значения ничего не находят. Используется формой клиента и (в Фазе 5) приёмом заявок.
     *
     * @return Collection<int, array{client: Client, matched: list<string>}>
     */
    public function find(?string $phone, ?string $email, ?int $exceptId = null): Collection
    {
        $phone = (new PhoneNormalizer)->normalize($phone);
        $email = trim((string) $email);
        $email = $email === '' ? null : mb_strtolower($email);

        if ($phone === null && $email === null) {
            return collect();
        }

        return Client::query()
            ->active()
            ->when($exceptId, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->where(function (Builder $q) use ($phone, $email) {
                $q->when($phone, fn (Builder $q) => $q->orWhere('phone', $phone))
                    ->when($email, fn (Builder $q) => $q->orWhereRaw('lower(trim(email)) = ?', [$email]));
            })
            ->orderBy('id')
            ->get()
            ->map(fn (Client $client) => [
                'client' => $client,
                'matched' => array_values(array_filter([
                    $phone !== null && $client->phone === $phone ? 'phone' : null,
                    $email !== null && mb_strtolower(trim((string) $client->email)) === $email ? 'email' : null,
                ])),
            ]);
    }
}
