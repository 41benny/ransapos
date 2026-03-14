<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Sale;
use Tests\TestCase;

class SaleCustomerNameTest extends TestCase
{
    public function test_resolved_customer_name_prefers_snapshot_name(): void
    {
        $sale = new Sale([
            'customer_name' => 'Customer Snapshot',
        ]);
        $sale->setRelation('customer', new Customer([
            'name' => 'Customer Relation',
        ]));

        $this->assertSame('Customer Snapshot', $sale->resolved_customer_name);
    }

    public function test_resolved_customer_name_falls_back_to_related_customer_name(): void
    {
        $sale = new Sale([
            'customer_name' => '   ',
        ]);
        $sale->setRelation('customer', new Customer([
            'name' => 'Customer Relation',
        ]));

        $this->assertSame('Customer Relation', $sale->resolved_customer_name);
    }

    public function test_resolved_customer_name_returns_walk_in_when_customer_is_missing(): void
    {
        $sale = new Sale([
            'customer_name' => null,
        ]);
        $sale->setRelation('customer', null);

        $this->assertSame('Walk-in', $sale->resolved_customer_name);
    }
}
