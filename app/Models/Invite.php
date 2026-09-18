<?php

namespace App\Models;

use Database\Factories\InviteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invite extends Model
{
    /** @use HasFactory<InviteFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Создаёт приглашение. Открытый токен возвращается только здесь, в БД хранится его хеш.
     *
     * @return array{0: self, 1: string} [приглашение, открытый токен]
     */
    public static function issue(User $creator): array
    {
        $token = Str::random(48);

        $invite = static::create([
            'token_hash' => static::hashToken($token),
            'created_by' => $creator->id,
            'expires_at' => now()->addHours(config('crm.invite_ttl_hours')),
        ]);

        return [$invite, $token];
    }

    public static function findValid(string $token): ?self
    {
        return static::where('token_hash', static::hashToken($token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Атомарно помечает приглашение использованным; true — только для первого успешного вызова.
     */
    public function markUsed(User $user): bool
    {
        $updated = static::whereKey($this->getKey())
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now(), 'user_id' => $user->id]);

        if ($updated === 1) {
            $this->refresh();
        }

        return $updated === 1;
    }

    private static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
