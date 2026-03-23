<?php

namespace Tests\Unit;

use App\Services\StripeApiService;
use Tests\TestCase;

class StripeApiServiceTest extends TestCase
{
    private StripeApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StripeApiService();
    }

    public function test_calculate_amount_in_cents_converts_dollars_correctly(): void
    {
        $this->assertEquals(8995, $this->service->calculateAmountInCents(89.95));
        $this->assertEquals(12995, $this->service->calculateAmountInCents(129.95));
        $this->assertEquals(100, $this->service->calculateAmountInCents(1.00));
        $this->assertEquals(24995, $this->service->calculateAmountInCents(249.95));
    }

    public function test_calculate_amount_in_cents_handles_zero(): void
    {
        $this->assertEquals(0, $this->service->calculateAmountInCents(0.0));
    }

    public function test_calculate_amount_in_cents_rounds_correctly(): void
    {
        $this->assertEquals(100, $this->service->calculateAmountInCents(0.999));
        $this->assertEquals(101, $this->service->calculateAmountInCents(1.009));
    }

    public function test_format_amount_from_cents_converts_correctly(): void
    {
        $this->assertEquals(89.95, $this->service->formatAmountFromCents(8995));
        $this->assertEquals(129.95, $this->service->formatAmountFromCents(12995));
        $this->assertEquals(1.00, $this->service->formatAmountFromCents(100));
    }

    public function test_format_amount_from_cents_handles_zero(): void
    {
        $this->assertEquals(0.0, $this->service->formatAmountFromCents(0));
    }

    public function test_calculate_and_format_are_inverse_operations(): void
    {
        $originalAmount = 89.95;
        $cents = $this->service->calculateAmountInCents($originalAmount);
        $result = $this->service->formatAmountFromCents($cents);

        $this->assertEquals($originalAmount, $result);
    }
}
