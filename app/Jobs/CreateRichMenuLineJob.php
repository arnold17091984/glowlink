<?php

namespace App\Jobs;

use App\Actions\RichMenu\CreateRichMenuLineAction;
use App\Models\RichMenu;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateRichMenuLineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RichMenu $richMenu;

    protected ?string $image;

    /**
     * Create a new job instance.
     */
    public function __construct(RichMenu $richMenu, ?string $image)
    {
        //
        $this->richMenu = $richMenu;
        $this->image = $image;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(CreateRichMenuLineAction::class)->execute($this->richMenu, $this->image);
    }
}
