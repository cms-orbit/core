<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'email', 'email_verified_at', 'created_at', 'updated_at'])
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $email = is_string($user->email) ? trim($user->email) : null;

                    if ($email === null || $email === '') {
                        continue;
                    }

                    $now = now();

                    DB::table('user_accounts')->updateOrInsert(
                        [
                            'provider'              => 'email',
                            'normalized_identifier' => Str::lower($email),
                        ],
                        [
                            'user_id'          => $user->id,
                            'identifier'       => $email,
                            'provider_user_id' => null,
                            'is_primary'       => true,
                            'verified_at'      => $user->email_verified_at,
                            'meta'             => json_encode(['backfilled' => true], JSON_THROW_ON_ERROR),
                            'updated_at'       => $user->updated_at ?? $now,
                            'created_at'       => $user->created_at ?? $now,
                        ],
                    );
                }
            });
    }

    public function down(): void
    {
        DB::table('user_accounts')
            ->where('provider', 'email')
            ->whereJsonContains('meta->backfilled', true)
            ->delete();
    }
};
