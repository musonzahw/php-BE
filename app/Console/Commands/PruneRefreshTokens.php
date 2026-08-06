<?php

namespace App\Console\Commands;

use App\Models\RefreshToken;
use Illuminate\Console\Command;

class PruneRefreshTokens extends Command
{
    protected $signature   = 'tokens:prune';
    protected $description = 'Delete all revoked and expired refresh tokens from the database';

    public function handle(): void
    {
        $deleted = RefreshToken::where('revoked', true)
            ->orWhere('expires_at', '<', now())
            ->delete();

        $this->info("Pruned {$deleted} stale refresh token(s).");
    }
}
