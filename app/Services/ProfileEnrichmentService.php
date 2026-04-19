<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use RuntimeException;

class ProfileEnrichmentService
{
    // Parallel fetch all 3 APIs — total time = slowest single call (not sum of all 3)
    public function enrich(string $name): array
    {
        $responses = Http::pool(fn (Pool $pool) => [
            $pool->as('gender')
                 ->timeout(10)
                 ->get('https://api.genderize.io', ['name' => $name]),

            $pool->as('age')
                 ->timeout(10)
                 ->get('https://api.agify.io', ['name' => $name]),

            $pool->as('country')
                 ->timeout(10)
                 ->get('https://api.nationalize.io', ['name' => $name]),
        ]);

        // Each parse method throws RuntimeException with the exact API name
        // the controller catches this and maps it to a 502 response
        return array_merge(
            $this->parseGender($responses['gender']),
            $this->parseAge($responses['age']),
            $this->parseCountry($responses['country']),
            [
                'created_at' => Carbon::now('UTC'),
            ],
        );
    }

    // ── Genderize ──────────────────────────────────────────────────────────────

    private function parseGender($response): array
    {
        if ($response->failed()) {
            throw new RuntimeException('Genderize returned an invalid response');
        }

        $body = $response->json();

        // Spec: gender null OR count 0 → 502, do not store
        if (empty($body['gender']) || empty($body['count'])) {
            throw new RuntimeException('Genderize returned an invalid response');
        }

        return [
            'gender'             => $body['gender'],
            'gender_probability' => $body['probability'] ?? null,
            'sample_size'        => (int) $body['count'],   // renamed from count per spec
        ];
    }

    // ── Agify ──────────────────────────────────────────────────────────────────

    private function parseAge($response): array
    {
        if ($response->failed()) {
            throw new RuntimeException('Agify returned an invalid response');
        }

        $body = $response->json();

        // Spec: age null → 502, do not store
        if (! isset($body['age']) || is_null($body['age'])) {
            throw new RuntimeException('Agify returned an invalid response');
        }

        $age = (int) $body['age'];

        return [
            'age'       => $age,
            'age_group' => $this->classifyAge($age),
        ];
    }

    // ── Nationalize ────────────────────────────────────────────────────────────

    private function parseCountry($response): array
    {
        if ($response->failed()) {
            throw new RuntimeException('Nationalize returned an invalid response');
        }

        $body = $response->json();

        // Spec: no country data → 502, do not store
        if (empty($body['country'])) {
            throw new RuntimeException('Nationalize returned an invalid response');
        }

        // Pick the country with the highest probability
        $top = collect($body['country'])
            ->sortByDesc('probability')
            ->first();

        return [
            'country_id'          => $top['country_id'],
            'country_probability' => (float) $top['probability'],
        ];
    }

    // ── Age classifier ─────────────────────────────────────────────────────────

    private function classifyAge(int $age): string
    {
        return match (true) {
            $age <= 12  => 'child',
            $age <= 19  => 'teenager',
            $age <= 59  => 'adult',
            default     => 'senior',
        };
    }
}
