<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Document;

use CmsOrbit\Core\Document\Models\CounterLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Read / vote counter behaviour backed by the counter_logs table.
 */
trait HasCounters
{
    public function counterLogs(): MorphMany
    {
        return $this->morphMany(CounterLog::class, 'countable');
    }

    /**
     * Record a unique daily view for the current user / IP.
     */
    public function recordView(): static
    {
        $query = $this->counterLogs()->where('action', 'view');

        if (Auth::check()) {
            $query->where('causer_id', Auth::id())
                ->where('causer_type', Auth::user()->getMorphClass());
        } else {
            $query->whereNull('causer_id')->where('ip_address', request()->ip());
        }

        if ($query->whereDate('created_at', today())->exists()) {
            return $this;
        }

        $this->counterLogs()->create([
            'action' => 'view',
            'causer_id' => Auth::id(),
            'causer_type' => Auth::check() ? Auth::user()->getMorphClass() : null,
            'ip_address' => request()->ip(),
        ]);

        $this->increment('read_count');

        return $this;
    }

    public function toggleAssent(): void
    {
        $this->toggleVote('assent');
    }

    public function toggleDissent(): void
    {
        $this->toggleVote('dissent');
    }

    protected function toggleVote(string $type): void
    {
        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $userType = Auth::user()->getMorphClass();

        $scope = fn (Builder $query) => $query->where('causer_id', $userId)->where('causer_type', $userType);

        $existing = $this->counterLogs()
            ->whereIn('action', ['assent', 'dissent'])
            ->where($scope)
            ->first();

        if ($existing !== null) {
            $existing->delete();

            if ($existing->action !== $type) {
                $this->createVote($type, $userId, $userType);
            }

            return;
        }

        $this->createVote($type, $userId, $userType);
    }

    protected function createVote(string $type, int|string $userId, string $userType): void
    {
        $this->counterLogs()->create([
            'action' => $type,
            'causer_id' => $userId,
            'causer_type' => $userType,
            'ip_address' => request()->ip(),
        ]);
    }
}
