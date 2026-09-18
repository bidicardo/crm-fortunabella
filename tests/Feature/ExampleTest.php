<?php

it('returns a successful response for the home route', function () {
    $this->get('/')->assertStatus(200);
});
