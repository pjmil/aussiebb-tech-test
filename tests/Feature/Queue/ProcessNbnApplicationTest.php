<?php

namespace Tests\Feature\Queue;

use Tests\TestCase;
use App\Models\Application;
use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnApplication;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProcessNbnApplicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the application processing success result.
     *
     * @return void
     */
    public function test_it_processes_an_application_successfully()
    {
        Http::fake([
            env('NBN_B2B_ENDPOINT') => Http::response(file_get_contents('tests/stubs/nbn-successful-response.json'), 200)
        ]);

        $application = Application::factory()->create();

        ProcessNbnApplication::dispatch($application->id);

        Http::assertSent(function (Request $request) use ($application) {
            return $request->url() == env('NBN_B2B_ENDPOINT') &&
                   $request['address_1'] == $application->address_1 &&
                   $request['address_2'] == $application->address_2 &&
                   $request['city'] == $application->city &&
                   $request['state'] == $application->state &&
                   $request['postcode'] == $application->postcode &&
                   $request['plan_name'] == $application->plan->name;
        });

        $application = $application->refresh();

        $this->assertEquals(ApplicationStatus::Complete, $application->status);
        $this->assertNotNull($application->order_id);
        $this->assertIsNumeric($application->order_id);
    }

    /**
     * Test the application processing failed result.
     *
     * @return void
     */
    public function test_it_processes_an_application_unsuccessfully()
    {
        Http::fake([
            env('NBN_B2B_ENDPOINT') => Http::response(file_get_contents('tests/stubs/nbn-fail-response.json'), 400)
        ]);

        $application = Application::factory()->create();

        ProcessNbnApplication::dispatch($application->id);

        Http::assertSent(function (Request $request) use ($application) {
            return $request->url() == env('NBN_B2B_ENDPOINT') &&
                   $request['address_1'] == $application->address_1 &&
                   $request['address_2'] == $application->address_2 &&
                   $request['city'] == $application->city &&
                   $request['state'] == $application->state &&
                   $request['postcode'] == $application->postcode &&
                   $request['plan_name'] == $application->plan->name;
        });

        $application = $application->refresh();

        $this->assertEquals(ApplicationStatus::OrderFailed, $application->status);
    }
}
