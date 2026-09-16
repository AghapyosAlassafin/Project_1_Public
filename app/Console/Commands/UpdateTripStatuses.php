<?php

namespace App\Console\Commands;

use App\Models\Trip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTripStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trips:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update trip statuses from published→ongoing and ongoing→completed based on dates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        DB::transaction(function () use ($now) {
            // Published trips that have started → ongoing
            Trip::where('status', Trip::STATUS_PUBLISHED)
                ->where('start_date', '<=', $now)
                ->update(['status' => Trip::STATUS_ONGOING]);

            // Ongoing trips that have ended → completed
            Trip::where('status', Trip::STATUS_ONGOING)
                ->where('end_date', '<=', $now)
                ->update(['status' => Trip::STATUS_COMPLETED]);
        });

        $this->info('Trip statuses updated successfully.');
        return Command::SUCCESS;
    }
}
