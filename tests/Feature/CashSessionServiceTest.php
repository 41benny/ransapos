<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\User;
use App\Services\CashSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_a_second_open_session_for_the_same_cashier(): void
    {
        $outlet = Outlet::factory()->create();
        $user = User::factory()->create(['outlet_id' => $outlet->id]);
        $service = app(CashSessionService::class);
        $payload = [
            'outlet_id' => $outlet->id,
            'opening_balance' => 210000,
            'notes' => 'Shift pagi',
        ];

        $first = $service->openSession($payload, $user);

        try {
            $service->openSession($payload, $user);
            $this->fail('The duplicate open session was not rejected.');
        } catch (\Exception $exception) {
            $this->assertSame(
                'Anda sudah memiliki shift yang aktif. Tutup shift terlebih dahulu.',
                $exception->getMessage()
            );
        }

        $this->assertSame(1, CashSession::where('user_id', $user->id)->where('status', 'open')->count());
        $this->assertDatabaseHas('cash_sessions', ['id' => $first->id]);
    }
}
