<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Set PIN absensi untuk karyawan
     */
    public function setAttendancePin(Request $request, User $user)
    {
        $request->validate([
            'pin' => 'required|numeric|digits:6'
        ], [
            'pin.required' => 'PIN wajib diisi',
            'pin.numeric' => 'PIN harus berupa angka',
            'pin.digits' => 'PIN harus 6 digit'
        ]);

        $user->update([
            'attendance_pin' => Hash::make($request->pin)
        ]);

        return back()->with('success', "PIN absensi berhasil diset untuk {$user->name}");
    }
}
