<?php

namespace App\Jobs;

use App\Actions\RichMenu\DeleteRichMenuLineAction;
use App\Models\RichMenu;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteRichMenuLineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RichMenu $richMenu;

    /**
     * Create a new job instance.
     */
    public function __construct(RichMenu $richMenu)
    {
        //
        $this->richMenu = $richMenu;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(DeleteRichMenuLineAction::class)->execute($this->richMenu);
    }
}
