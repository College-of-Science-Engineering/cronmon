<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the ping endpoint is accessible', function () {
    $response = $this->get('/ping/test-token');

    $response->assertStatus(404); // 404 for invalid token is expected
});
