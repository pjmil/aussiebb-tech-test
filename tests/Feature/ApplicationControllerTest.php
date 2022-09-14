<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Application;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Hit the endpoint without being authenticated
     *
     * @return void
     */
    public function test_it_denies_unauthorized_access()
    {
        Application::factory(10)->create();

        $response = $this->get('/api/applications');

        $response->assertStatus(403);
    }

    /**
     * Hit the endpoint
     *
     * @return void
     */
    public function test_it_returns_data()
    {
        $user = User::factory()->create();

        Application::factory(100)->create();

        $response = $this->actingAs($user)->get('/api/applications');

        $json = json_decode($response->getContent());

        $response->assertStatus(200);

        $this->assertEquals(100, $json->pagination->total);
        $this->assertLessThanOrEqual(15, $json->pagination->count);

        $applications = Application::orderBy('created_at', 'asc')->limit(15)->get();

        $this->assertEquals(collect($json->data)->pluck('id'), $applications->pluck('id'));
        $this->assertMatchesRegularExpression('/A\$\d{1,3}\.\d{1,2}/', $json->data[0]->plan_monthly_cost);
    }

    /**
     * Hit the endpoint with a parameter
     *
     * @return void
     */
    public function test_it_returns_filtered_data()
    {
        $user = User::factory()->create();

        Application::factory(100)->create();

        $response = $this->actingAs($user)->get('/api/applications?filter=nbn');

        $json = json_decode($response->getContent());

        $response->assertStatus(200);

        $nbnCount = Application::whereHas('plan', function ($query) {
            $query->where('type', 'nbn');
        })
        ->count();

        $this->assertEquals($nbnCount, $json->pagination->total);
        $this->assertLessThanOrEqual(15, $json->pagination->count);

        $applications = Application::whereHas('plan', function ($query) {
            $query->where('type', 'nbn');
        })
        ->orderBy('created_at', 'asc')
        ->limit(15)
        ->get();

        $this->assertEquals(collect($json->data)->pluck('id'), $applications->pluck('id'));
        $this->assertMatchesRegularExpression('/A\$\d{1,3}\.\d{1,2}/', $json->data[0]->plan_monthly_cost);
    }
}
