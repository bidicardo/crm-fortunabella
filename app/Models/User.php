<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'blocked_at' => 'datetime',
        ];
    }

    public function isCreator(): bool
    {
        return $this->role === Role::Creator;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * Блокирует учётную запись и сразу завершает все её сессии, включая «запомнить меня».
     */
    public function block(): void
    {
        $this->forceFill(['blocked_at' => now(), 'remember_token' => Str::random(60)])->save();

        DB::table(config('session.table', 'sessions'))->where('user_id', $this->id)->delete();
    }

    public function unblock(): void
    {
        $this->forceFill(['blocked_at' => null])->save();
    }
}
