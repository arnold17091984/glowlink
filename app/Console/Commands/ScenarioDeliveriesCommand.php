<?php

namespace App\Console\Commands;

use App\Enums\ScenarioStatusEnum;
use App\Jobs\ScenarioDeliveriesJob;
use App\Models\ScenarioDelivery;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScenarioDeliveriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scenario-delivery-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scenarios = ScenarioDelivery::all();

        foreach ($scenarios as $scenario) {
            $this->scenarioUpdate($scenario);
        }
    }

    public function scenarioUpdate($scenario): void
    {
        $lastKey = array_key_last($scenario->messageDeliveries->toArray());

        foreach ($scenario->messageDeliveries as $key => $message) {
            $deliveryDate = Carbon::parse($message->delivery_date)->startOfMinute();
            $dateNow = Carbon::parse(now())->startOfMinute();

            if ($dateNow->equalTo($deliveryDate)) {
                try {
                    ScenarioDeliveriesJob::dispatch($message->message, $scenario->send_to);
                    if ($key === $lastKey) {
                        $scenario->update([
                            'status' => ScenarioStatusEnum::COMPLETED,
                        ]);

                        return;
                    }
                    $scenario->update([
                        'status' => ScenarioStatusEnum::ONGOING,
                    ]);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
    }
}
