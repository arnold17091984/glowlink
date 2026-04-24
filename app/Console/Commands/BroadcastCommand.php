<?php

namespace App\Console\Commands;

use App\Enums\RepeatEnum;
use App\Jobs\BroadcastingJob;
use App\Models\Broadcast;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BroadcastCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:broadcast-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    protected Carbon $startDate;

    protected Carbon $dateNow;

    public function handle()
    {
        $broadcasts = Broadcast::all();

        foreach ($broadcasts as $broadcast) {
            $this->startDate = Carbon::parse($broadcast->start_date)->startOfMinute();
            $this->dateNow = Carbon::parse(now())->startOfMinute();

            if ($this->dateNow->greaterThanOrEqualTo($this->startDate)) {
                $this->checkRepeat($broadcast);
            }
        }
    }

    public function checkRepeat(Broadcast $broadcast): void
    {
        $message = $broadcast->messageDelivery->message;

        if (! $broadcast->is_active) {
            return;
        }

        if (is_null($broadcast->next_date)) {
            BroadcastingJob::dispatch($message, $broadcast->send_to);

            if ($broadcast->repeat === RepeatEnum::ONCE) {
                $broadcast->update([
                    'is_active' => false,
                ]);

                $broadcast->update([
                    'last_date' => now()->toDateTimeString(),
                ]);

                return;
            }

            $this->updateNextDate($broadcast);

            return;
        }

        $nextDate = Carbon::parse($broadcast->next_date)->startOfMinute();

        if ($nextDate->equalTo($this->dateNow)) {
            BroadcastingJob::dispatch($message, $broadcast->send_to);
            $this->updateNextDate($broadcast);
        }
    }

    public function updateNextDate($broadcast)
    {
        if ($broadcast->repeat === RepeatEnum::MINUTES) {
            $broadcast->update([
                'next_date' => now()->addMinutes($broadcast->every),
            ]);
        }
        if ($broadcast->repeat === RepeatEnum::HOUR) {
            $broadcast->update([
                'next_date' => now()->addHours($broadcast->every),
            ]);
        }
        if ($broadcast->repeat === RepeatEnum::DAY) {
            $broadcast->update([
                'next_date' => now()->addDays($broadcast->every),
            ]);
        }
        if ($broadcast->repeat === RepeatEnum::WEEK) {
            $broadcast->update([
                'next_date' => now()->addWeeks($broadcast->every),
            ]);
        }
        if ($broadcast->repeat === RepeatEnum::MONTH) {
            $broadcast->update([
                'next_date' => now()->addMonths($broadcast->every),
            ]);
        }
    }
}
