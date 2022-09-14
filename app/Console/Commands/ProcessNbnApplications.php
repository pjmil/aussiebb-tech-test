<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Jobs\ProcessApplication;
use Illuminate\Console\Command;

class ProcessNbnApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nbn-applications:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all valid NBN applications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $applications = Application::with(['customer', 'plan'])
            ->whereHas('plan', function ($query) {
                $query->where('type', 'nbn');
            })
            ->where('status', 'order')
            ->orderBy('created_at', 'asc')
            ->get()
            ->pluck('id');

        foreach ($applications as $application_id) {
            dispatch(new ProcessApplication($application_id));
        }

        return 0;
    }
}
