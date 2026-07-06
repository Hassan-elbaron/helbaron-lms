<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Exceptions\SeatNotAssignedException;
use App\Domains\Crm\Exceptions\SeatPoolExhaustedException;
use App\Domains\Crm\Models\OrganizationMember;
use App\Domains\Crm\Models\SeatAssignment;
use App\Domains\Crm\Models\SeatPool;
use App\Shared\Services\BaseService;

/**
 * Seat allocation with pool locking to prevent over-allocation. Assign, revoke, and transfer
 * are atomic; transfer holds the lock across revoke+assign so no phantom free seat appears.
 */
class SeatService extends BaseService
{
    public function assign(SeatPool $pool, OrganizationMember $member): SeatAssignment
    {
        return $this->transaction(function () use ($pool, $member): SeatAssignment {
            $locked = SeatPool::whereKey($pool->id)->lockForUpdate()->first();

            $existing = SeatAssignment::where('seat_pool_id', $locked->id)
                ->where('member_id', $member->id)
                ->whereNull('revoked_at')
                ->first();

            if ($existing !== null) {
                return $existing; // idempotent
            }

            if ($locked->available() < 1) {
                throw new SeatPoolExhaustedException;
            }

            $assignment = SeatAssignment::create([
                'seat_pool_id' => $locked->id,
                'member_id' => $member->id,
                'assigned_at' => now(),
            ]);

            $locked->increment('used_seats');

            return $assignment;
        });
    }

    public function revoke(SeatPool $pool, OrganizationMember $member): void
    {
        $this->transaction(function () use ($pool, $member): void {
            $locked = SeatPool::whereKey($pool->id)->lockForUpdate()->first();

            $assignment = SeatAssignment::where('seat_pool_id', $locked->id)
                ->where('member_id', $member->id)
                ->whereNull('revoked_at')
                ->first();

            if ($assignment === null) {
                throw new SeatNotAssignedException;
            }

            $assignment->forceFill(['revoked_at' => now()])->save();
            $locked->decrement('used_seats');
        });
    }

    public function transfer(SeatPool $pool, OrganizationMember $from, OrganizationMember $to): SeatAssignment
    {
        return $this->transaction(function () use ($pool, $from, $to): SeatAssignment {
            $locked = SeatPool::whereKey($pool->id)->lockForUpdate()->first();

            $source = SeatAssignment::where('seat_pool_id', $locked->id)
                ->where('member_id', $from->id)
                ->whereNull('revoked_at')
                ->first();

            if ($source === null) {
                throw new SeatNotAssignedException;
            }

            $source->forceFill(['revoked_at' => now()])->save();

            return SeatAssignment::create([
                'seat_pool_id' => $locked->id,
                'member_id' => $to->id,
                'assigned_at' => now(),
            ]);
            // used_seats unchanged: one revoked, one assigned.
        });
    }
}
