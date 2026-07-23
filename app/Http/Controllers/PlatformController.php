<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsOrganizations;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The BespokeLMS platform-owner console.
 *
 * Access is gated twice: the "platform.owner" route middleware (404s non-owners)
 * and Supabase RLS on every table the console reads with a user token. The
 * cross-tenant estate view is read with the service-role key (see
 * {@see ReadsOrganizations}); the route middleware is its authorisation boundary.
 */
final class PlatformController extends Controller
{
    public function index(Request $request, ReadsOrganizations $organizations): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $estate = null;
        $estateError = null;

        try {
            $estate = $this->buildEstate($organizations->all());
        } catch (SupabaseAuthException $e) {
            report($e);
            $estateError = 'The tenant list could not be loaded right now. Please try again shortly.';
        }

        return view('platform.home', [
            'user' => $user,
            'estate' => $estate,
            'estateError' => $estateError,
        ]);
    }

    /**
     * Shape the flat organisation rows into the operator → clients hierarchy the
     * console renders, with derived per-tenant user counts and estate totals.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<string,mixed>
     */
    private function buildEstate(array $rows): array
    {
        $operators = [];
        $clientsByParent = [];
        $clientCount = 0;
        $userTotal = 0;

        // First pass: split rows by role and index client orgs under their operator.
        foreach ($rows as $row) {
            $type = (string) ($row['type'] ?? '');
            $users = (int) ($row['user_count'] ?? 0);

            if ($type === 'client') {
                $parentId = (string) ($row['parent_id'] ?? '');
                $clientsByParent[$parentId][] = [
                    'name' => (string) ($row['name'] ?? ''),
                    'slug' => $row['slug'] ?? null,
                    'subtype' => $row['subtype'] ?? null,
                    'location' => $row['location'] ?? null,
                    'user_count' => $users,
                ];
                $clientCount++;
                $userTotal += $users;
            }
        }

        // Second pass: build the operator list with their nested clients.
        foreach ($rows as $row) {
            if ((string) ($row['type'] ?? '') !== 'operator') {
                continue;
            }

            $id = (string) ($row['id'] ?? '');
            $users = (int) ($row['user_count'] ?? 0);
            $clients = $clientsByParent[$id] ?? [];
            $userTotal += $users;

            $operators[] = [
                'id' => $id,
                'name' => (string) ($row['name'] ?? ''),
                'slug' => $row['slug'] ?? null,
                'operator_subtype' => $row['operator_subtype'] ?? null,
                'has_client_layer' => (bool) ($row['has_client_layer'] ?? false),
                'location' => $row['location'] ?? null,
                'created_at' => $row['created_at'] ?? null,
                'user_count' => $users,
                'clients' => $clients,
                'client_count' => count($clients),
            ];
        }

        // Stable, human-friendly ordering.
        usort($operators, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return [
            'operators' => $operators,
            'summary' => [
                'operators' => count($operators),
                'clients' => $clientCount,
                'users' => $userTotal,
            ],
        ];
    }
}
