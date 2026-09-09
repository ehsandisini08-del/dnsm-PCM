<?php

namespace Database\Factories;

use App\Models\DnsServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DnsServer>
 */
class DnsServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'NS-'.fake()->numberBetween(1, 99),
            'hostname' => 'ns'.fake()->numberBetween(1, 99).'.example.com',
            'ip_address' => fake()->ipv4(),
            'type' => 'authoritative',
            'status' => 'online',
            'port' => 53,
            'api_url' => 'http://127.0.0.1:8081',
            'api_key' => fake()->uuid(),
            'description' => fake()->sentence(),
            'last_check_at' => now(),
        ];
    }
}
