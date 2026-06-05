<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q'        => trim((string) $request->query('q', '')),
            'event'    => (string) $request->query('event', ''),
            'user_id'  => (string) $request->query('user_id', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to'  => (string) $request->query('date_to', ''),
        ];

        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $term = '%' . $filters['q'] . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('description', 'like', $term)
                        ->orWhere('user_name', 'like', $term)
                        ->orWhere('ip_address', 'like', $term);
                });
            })
            ->when($filters['event'] !== '', fn ($query) => $query->where('event', $filters['event']))
            ->when($filters['user_id'] !== '', fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $events = [
            'created'      => 'Tambah',
            'updated'      => 'Ubah',
            'deleted'      => 'Hapus',
            'login'        => 'Login',
            'logout'       => 'Logout',
            'login_failed' => 'Login Gagal',
        ];

        return view('admin.activity-logs.index', [
            'logs'    => $logs,
            'users'   => $users,
            'events'  => $events,
            'filters' => $filters,
        ]);
    }
}
