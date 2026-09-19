<?php

use App\Models\Client;
use App\Rules\RussianPhone;
use Illuminate\Support\Facades\Validator;

it('validates phones with RussianPhone', function (?string $phone, bool $passes) {
    $validator = Validator::make(['phone' => $phone], ['phone' => ['nullable', new RussianPhone]]);

    expect($validator->passes())->toBe($passes);
})->with([
    'valid' => ['8 917 123-45-67', true],
    'empty' => ['', true],
    'null' => [null, true],
    'invalid' => ['12345', false],
    'foreign' => ['+380 44 123 45 67', false],
]);

it('shows a Russian error message', function () {
    $validator = Validator::make(['phone' => '12345'], ['phone' => [new RussianPhone]]);

    expect($validator->errors()->first('phone'))->toContain('российский номер');
});

it('stores a normalized phone', function () {
    $client = Client::create(['name' => 'Иван', 'phone' => '8 917 123-45-67']);

    expect($client->fresh()->phone)->toBe('+79171234567');
});

it('stores null for an invalid phone assigned directly', function () {
    $client = Client::create(['name' => 'Иван', 'phone' => '12345']);

    expect($client->fresh()->phone)->toBeNull();
});
