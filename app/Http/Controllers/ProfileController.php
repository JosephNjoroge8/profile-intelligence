<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\ProfileEnrichmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileEnrichmentService $enrichment
    ) {}

    // ── POST /api/profiles ─────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        // 400 — missing or empty name field
        // Note: Laravel's ConvertEmptyStringsToNull middleware converts "" to null
        if (! $request->has('name') || $request->input('name') === null || $request->input('name') === '') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Missing or empty name',
            ], 400);
        }

        // 422 — name must be a string (catches numeric, array, boolean inputs)
        if (! is_string($request->input('name'))) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Name must be a string',
            ], 422);
        }

        $name = strtolower(trim($request->input('name')));

        // Idempotency — if name already stored, return the existing record
        // Note: name comparison is case-insensitive via strtolower normalization
        $existing = Profile::where('name', $name)->first();

        if ($existing) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Profile already exists',
                'data'    => $existing->toFullArray(),
            ], 200);
        }

        // Enrich via external APIs (concurrent HTTP::pool calls)
        try {
            $enriched = $this->enrichment->enrich($name);
        } catch (RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 502);
        }

        // Persist — UUID v7 gives time-sortable IDs (requires Laravel >= 11.4)
        $profile = Profile::create(array_merge(
            [
                'id'   => (string) Str::uuid7(),
                'name' => $name,
            ],
            $enriched
        ));

        return response()->json([
            'status' => 'success',
            'data'   => $profile->toFullArray(),
        ], 201);
    }

    // ── GET /api/profiles/{id} ─────────────────────────────────────────────────

    public function show(string $id): JsonResponse
    {
        $profile = Profile::find($id);

        if (! $profile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Profile not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $profile->toFullArray(),
        ]);
    }

    // ── GET /api/profiles ──────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Profile::query();

        // All filters are case-insensitive per spec
        // Using LOWER() ensures we match regardless of stored case
        if ($request->filled('gender')) {
            $query->whereRaw('LOWER(gender) = ?', [strtolower($request->input('gender'))]);
        }

        if ($request->filled('country_id')) {
            $query->whereRaw('LOWER(country_id) = ?', [strtolower($request->input('country_id'))]);
        }

        if ($request->filled('age_group')) {
            $query->whereRaw('LOWER(age_group) = ?', [strtolower($request->input('age_group'))]);
        }

        $profiles = $query->get();

        return response()->json([
            'status' => 'success',
            'count'  => $profiles->count(),
            'data'   => $profiles->map->toListArray()->values(),
        ]);
    }

    // ── DELETE /api/profiles/{id} ──────────────────────────────────────────────

    public function destroy(string $id): JsonResponse
    {
        $profile = Profile::find($id);

        if (! $profile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Profile not found',
            ], 404);
        }

        $profile->delete();

        // 204 No Content — empty body is correct per spec
        return response()->json(null, 204);
    }
}
