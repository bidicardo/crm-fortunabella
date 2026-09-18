<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateCreator extends Command
{
    protected $signature = 'crm:create-creator {--name=} {--email=} {--password=}';

    protected $description = 'Создать первого создателя CRM (только если создателя ещё нет)';

    public function handle(): int
    {
        if (User::where('role', Role::Creator)->exists()) {
            $this->error('Создатель уже существует.');

            return self::FAILURE;
        }

        // Пароль лучше вводить интерактивно: опция попадёт в историю команд оболочки.
        $data = [
            'name' => $this->option('name') ?? $this->ask('Имя'),
            'email' => $this->option('email') ?? $this->ask('Email'),
            'password' => $this->option('password') ?? $this->secret('Пароль (минимум 8 символов)'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User($data);
        $user->role = Role::Creator;
        $user->save();

        $this->info("Создатель {$user->email} создан.");

        return self::SUCCESS;
    }
}
