<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_responde_ok_com_o_banco_conectado(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()->assertJson(['status' => 'ok']);
    }
}
