<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    /**
     * Daftar master shift.
     */
    public function index(): View
    {
        $shifts = Shift::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.shifts.index', compact('shifts'));
    }

    /**
     * Simpan shift baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateShift($request);

        Shift::create($this->payload($validated, $request));

        return redirect()
            ->route('admin.shifts.index')
            ->with('success', 'Shift berhasil ditambahkan.');
    }

    /**
     * Perbarui shift.
     */
    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $this->validateShift($request);

        $shift->update($this->payload($validated, $request));

        return redirect()
            ->route('admin.shifts.index')
            ->with('success', 'Shift berhasil diperbarui.');
    }

    /**
     * Hapus shift (diblokir bila sudah dipakai absensi).
     */
    public function destroy(Shift $shift): RedirectResponse
    {
        $usedCount = $shift->attendances()->count();

        if ($usedCount > 0) {
            return back()->with('error', "Shift tidak bisa dihapus karena sudah dipakai di {$usedCount} record absensi. Nonaktifkan saja bila tidak dipakai lagi.");
        }

        $shift->delete();

        return redirect()
            ->route('admin.shifts.index')
            ->with('success', 'Shift berhasil dihapus.');
    }

    /**
     * Aturan validasi shift.
     *
     * @return array<string, mixed>
     */
    private function validateShift(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'late_tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'start_time.date_format' => 'Format jam masuk harus HH:MM (mis. 08:00).',
            'end_time.date_format' => 'Format jam pulang harus HH:MM (mis. 16:00).',
        ]);
    }

    /**
     * Susun payload simpan + auto-deteksi shift lintas tengah malam.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, Request $request): array
    {
        return [
            'name' => $validated['name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'late_tolerance_minutes' => (int) ($validated['late_tolerance_minutes'] ?? 0),
            // Shift dianggap melewati tengah malam bila jam pulang <= jam masuk.
            'is_overnight' => strtotime($validated['end_time']) <= strtotime($validated['start_time']),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}
