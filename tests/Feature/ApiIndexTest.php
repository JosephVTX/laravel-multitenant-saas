<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiIndexTest extends TestCase
{
    public function test_the_root_returns_api_information_as_json(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 'v1')
            ->assertJsonPath('data.base_url', url('/api/v1'));
    }
}
