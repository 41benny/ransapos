@if($employees->isEmpty())
    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
        Tidak ada karyawan outlet lain dengan nama "{{ $keyword }}".
    </div>
@else
    @foreach($employees as $employee)
        @include('pos.attendance._employee-card', [
            'employee' => $employee,
            'shifts' => $shifts,
            'currentOutletId' => $currentOutletId,
        ])
    @endforeach
@endif
