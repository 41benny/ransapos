<?php

namespace Tests\Unit;

use App\Models\Shift;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ShiftTest extends TestCase
{
    private function makeShift(array $attributes): Shift
    {
        return new Shift(array_merge([
            'name' => 'Test',
            'late_tolerance_minutes' => 0,
            'is_overnight' => false,
        ], $attributes));
    }

    public function test_start_and_end_for_normal_shift(): void
    {
        $shift = $this->makeShift(['start_time' => '08:00', 'end_time' => '16:00']);
        $date = Carbon::parse('2026-06-05 03:30:00');

        $this->assertSame('2026-06-05 08:00:00', $shift->startFor($date)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-05 16:00:00', $shift->endFor($date)->format('Y-m-d H:i:s'));
    }

    public function test_end_for_overnight_shift_falls_on_next_day(): void
    {
        $shift = $this->makeShift(['start_time' => '22:00', 'end_time' => '06:00', 'is_overnight' => true]);
        $date = Carbon::parse('2026-06-05 23:10:00');

        $this->assertSame('2026-06-05 22:00:00', $shift->startFor($date)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-06 06:00:00', $shift->endFor($date)->format('Y-m-d H:i:s'));
    }

    public function test_late_threshold_applies_tolerance(): void
    {
        $shift = $this->makeShift(['start_time' => '08:00', 'end_time' => '16:00', 'late_tolerance_minutes' => 15]);
        $date = Carbon::parse('2026-06-05');

        $this->assertSame('2026-06-05 08:15:00', $shift->lateThresholdFor($date)->format('Y-m-d H:i:s'));
    }

    public function test_time_range_label(): void
    {
        $shift = $this->makeShift(['start_time' => '08:00:00', 'end_time' => '16:30:00']);

        $this->assertSame('08:00–16:30', $shift->timeRangeLabel());
    }
}
