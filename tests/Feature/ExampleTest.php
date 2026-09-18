<?php

use App\Models\User;

it('returns a successful response for the home route when logged in', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertStatus(200);
});
