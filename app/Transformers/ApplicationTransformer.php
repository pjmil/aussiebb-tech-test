<?php

namespace App\Transformers;

use NumberFormatter;
use App\Models\Application;
use Flugg\Responder\Transformers\Transformer;

class ApplicationTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['customer', 'plan'];

    /**
     * A list of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
         * Application id
        * Customer full name
        * Address
        * Plan type
        * Plan name
        * State
        * Plan monthly cost
        * Order Id (only show this field on applications with the complete status)
     */
    public function transform(Application $application)
    {
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $response = [
            'id' => (int) $application->id,
            'customer_name' => (string) "{$application->customer->first_name} {$application->customer->last_name}",
            'address' => (string) "{$application->address_1} {$application->address_2} {$application->city} {$application->postcode}",
            'state' => (string) $application->state,
            'plan_type' => (string) $application->plan->type,
            'plan_name' => (string) $application->plan->name,
            'plan_monthly_cost' => (string) $fmt->formatCurrency($application->plan->monthly_cost/100, 'AUD'),
        ];

        if ($application->status->value === 'complete') {
            $response['order_id'] = (int) $application->order_id;
        }

        return $response;
    }
}
