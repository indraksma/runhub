<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BibRenumberingService
{
    /**
     * @return Collection<int, array{registration: Registration, old_bib: ?string, new_bib: string, prefix: string}>
     */
    public function preview(Event $event): Collection
    {
        $registrations = $this->verifiedRegistrations($event)->get();
        $plan = $this->buildPlan($registrations);

        $this->ensurePrefixesAreAvailable($plan);

        return $plan;
    }

    /**
     * @return Collection<int, array{registration: Registration, old_bib: ?string, new_bib: string, prefix: string}>
     */
    public function execute(Event $event): Collection
    {
        return DB::transaction(function () use ($event): Collection {
            $registrations = $this->verifiedRegistrations($event)
                ->lockForUpdate()
                ->get();
            $plan = $this->buildPlan($registrations);

            $this->ensurePrefixesAreAvailable($plan);

            if ($plan->isEmpty()) {
                return $plan;
            }

            $temporaryPrefix = '__bib_tmp_'.Str::lower(Str::random(20)).'_';

            // Free all old BIB values first so swapping or compacting numbers
            // cannot violate the global unique index midway through the update.
            foreach ($plan as $item) {
                DB::table('registrations')
                    ->where('id', $item['registration']->id)
                    ->update(['bib_number' => $temporaryPrefix.$item['registration']->id]);
            }

            foreach ($plan as $item) {
                DB::table('registrations')
                    ->where('id', $item['registration']->id)
                    ->update(['bib_number' => $item['new_bib']]);
            }

            return $plan;
        });
    }

    private function verifiedRegistrations(Event $event)
    {
        return Registration::query()
            ->where('status', 'verified')
            ->whereHas('raceCategory', fn ($query) => $query->where('event_id', $event->id))
            ->with('raceCategory.event');
    }

    /**
     * @param  EloquentCollection<int, Registration>  $registrations
     * @return Collection<int, array{registration: Registration, old_bib: ?string, new_bib: string, prefix: string}>
     */
    private function buildPlan(EloquentCollection $registrations): Collection
    {
        $registrations->each(function (Registration $registration): void {
            if ($this->effectivePrefix($registration) === '') {
                throw new RuntimeException(
                    "Kategori {$registration->raceCategory->name} tidak memiliki prefix BIB kategori maupun event."
                );
            }
        });

        return $registrations
            ->groupBy(fn (Registration $registration): string => $this->effectivePrefix($registration))
            ->sortKeys()
            ->flatMap(function (Collection $group, string $prefix): Collection {
                $ordered = $group->sort(function (Registration $left, Registration $right): int {
                    $leftTime = $left->verified_at?->getTimestamp() ?? PHP_INT_MAX;
                    $rightTime = $right->verified_at?->getTimestamp() ?? PHP_INT_MAX;

                    return [$leftTime, $left->id] <=> [$rightTime, $right->id];
                })->values();

                return $ordered->map(fn (Registration $registration, int $index): array => [
                    'registration' => $registration,
                    'old_bib' => $registration->bib_number,
                    'new_bib' => $prefix.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'prefix' => $prefix,
                ]);
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{registration: Registration, old_bib: ?string, new_bib: string, prefix: string}>  $plan
     */
    private function ensurePrefixesAreAvailable(Collection $plan): void
    {
        if ($plan->isEmpty()) {
            return;
        }

        $targetIds = $plan->pluck('registration.id');
        $prefixes = $plan->pluck('prefix')->unique()->values();

        $conflicts = Registration::query()
            ->whereNotIn('id', $targetIds)
            ->whereNotNull('bib_number')
            ->where(function ($query) use ($prefixes): void {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('bib_number', 'like', $prefix.'%');
                }
            })
            ->pluck('bib_number')
            ->filter(function (string $bib) use ($prefixes): bool {
                return $prefixes->contains(
                    fn (string $prefix): bool => preg_match('/^'.preg_quote($prefix, '/').'\d+$/i', $bib) === 1
                );
            })
            ->values();

        if ($conflicts->isNotEmpty()) {
            throw new RuntimeException(
                'Prefix target juga digunakan oleh pendaftaran di luar event ini: '.$conflicts->take(5)->join(', ').'.'
            );
        }
    }

    private function effectivePrefix(Registration $registration): string
    {
        return strtoupper((string) (
            $registration->raceCategory->bib_prefix
            ?: $registration->raceCategory->event->bib_prefix
        ));
    }
}
