<?php

use App\Services\PhoneNormalizer;

it('normalizes Russian phones', function (string $raw) {
    expect((new PhoneNormalizer)->normalize($raw))->toBe('+79171234567');
})->with([
    '89171234567',
    '8 917 123-45-67',
    '+7 917 123-45-67',
    '7 (917) 123-45-67',
]);

it('rejects invalid phones', function (?string $raw) {
    $normalizer = new PhoneNormalizer;

    expect($normalizer->normalize($raw))->toBeNull()
        ->and($normalizer->isValid($raw))->toBeFalse();
})->with([
    'ten digits' => '9171234567',
    'twelve digits' => '791712345678',
    'first digit 9' => '99171234567',
    'foreign' => '+380 44 123 45 67',
    'letters' => 'abcdef',
    'empty' => '',
    'null' => null,
]);

it('reports valid phones', function () {
    expect((new PhoneNormalizer)->isValid('8 917 123-45-67'))->toBeTrue();
});
