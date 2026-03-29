<?php

namespace Tests\Unit\Console;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class KernelTest extends TestCase
{
    // ✅ Memastikan schedule() bisa dipanggil tanpa error
    // (method kosong, tapi tetap perlu di-cover agar tidak ada kode
    // tersembunyi yang tidak terdeteksi saat ada perubahan di masa depan)
    public function test_schedule_runs_without_errors(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $events   = $schedule->events();

        // Schedule kosong, tidak ada event terdaftar
        $this->assertEmpty($events);
    }
}