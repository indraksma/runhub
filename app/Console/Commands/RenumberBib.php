<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Registration;
use App\Services\BibRenumberingService;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Collection;
use RuntimeException;

class RenumberBib extends Command
{
    use ConfirmableTrait;

    protected $signature = 'bib:renumber
        {event : ID atau slug event}
        {--commit : Simpan hasil penomoran ulang}
        {--show : Tampilkan perubahan setiap pendaftar}
        {--force : Jalankan tanpa konfirmasi pada environment production}';

    protected $description = 'Preview atau nomor ulang BIB terverifikasi berdasarkan prefix kategori/event';

    public function handle(BibRenumberingService $service): int
    {
        $event = $this->findEvent((string) $this->argument('event'));

        if (! $event) {
            $this->error('Event tidak ditemukan. Gunakan ID atau slug event yang valid.');

            return self::FAILURE;
        }

        try {
            $plan = $service->preview($event);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($plan->isEmpty()) {
            $this->info("Tidak ada pendaftar terverifikasi pada event {$event->name}.");

            return self::SUCCESS;
        }

        $this->info("Rencana penomoran ulang BIB: {$event->name}");
        $this->renderSummary($plan);

        if ($this->option('show')) {
            $this->renderDetails($plan);
        }

        if (! $this->option('commit')) {
            $this->warn('PREVIEW SAJA — database belum diubah. Tambahkan --commit untuk menyimpan.');

            return self::SUCCESS;
        }

        if (! $this->confirmToProceed('Penomoran BIB peserta terverifikasi akan diubah.')) {
            return self::FAILURE;
        }

        try {
            $appliedPlan = $service->execute($event);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Selesai. {$appliedPlan->count()} nomor BIB telah diperbarui.");

        return self::SUCCESS;
    }

    private function findEvent(string $identifier): ?Event
    {
        return Event::query()
            ->where('slug', $identifier)
            ->when(ctype_digit($identifier), fn ($query) => $query->orWhere('id', (int) $identifier))
            ->first();
    }

    /**
     * @param  Collection<int, array{registration: Registration, old_bib: ?string, new_bib: string, prefix: string}>  $plan
     */
    private function renderSummary(Collection $plan): void
    {
        $rows = $plan->groupBy('prefix')->map(function (Collection $group, string $prefix): array {
            $categories = $group
                ->pluck('registration.raceCategory.name')
                ->unique()
                ->join(', ');

            return [
                $prefix,
                $categories,
                $group->count(),
                $group->first()['new_bib'].' – '.$group->last()['new_bib'],
            ];
        })->values();

        $this->table(['Prefix', 'Kategori', 'Jumlah', 'Nomor baru'], $rows);
    }

    /**
     * @param  Collection<int, array{registration: Registration, old_bib: ?string, new_bib: string, prefix: string}>  $plan
     */
    private function renderDetails(Collection $plan): void
    {
        $this->table(
            ['ID', 'Peserta', 'Kategori', 'BIB lama', 'BIB baru'],
            $plan->map(fn (array $item): array => [
                $item['registration']->id,
                $item['registration']->participant_name,
                $item['registration']->raceCategory->name,
                $item['old_bib'] ?: '-',
                $item['new_bib'],
            ])
        );
    }
}
