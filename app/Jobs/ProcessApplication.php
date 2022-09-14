<?php

namespace App\Jobs;

use App\Models\Application;
use App\Enums\ApplicationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($application_id)
    {
        $this->id = $application_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $application = Application::findOrFail($this->id);

        $response = Http::post(env('NBN_B2B_ENDPOINT'), [
            "address_1" => $application->address_1,
            "address_2" => $application->address_2,
            "city" => $application->city,
            "state" => $application->state,
            "postcode" => $application->postcode,
            "plan_name" => $application->plan->name,
        ]);

        if ($response->status() === 200) {
            $application->order_id = random_int(1,99999);
            $application->status = ApplicationStatus::Complete;
        } else {
            $application->status = ApplicationStatus::OrderFailed;
        }

        $application->save();
    }
}
