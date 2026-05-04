@extends('layouts.admin')

@section('title', 'Log Aktivitas')
@section('header_title', 'Catatan Aktivitas')
@section('header_subtitle', 'Pantau tindakan yang dilakukan oleh admin')

@section('content')
    <div style="background: white; border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow)">
        <table style="width: 100%; border-collapse: collapse">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #eee">
                    <th style="padding: 10px">Waktu</th>
                    <th style="padding: 10px">Admin</th>
                    <th style="padding: 10px">Aksi</th>
                    <th style="padding: 10px">Detail</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr style="border-bottom: 1px solid #f9f9f9">
                        <td style="padding: 10px; color: var(--gray)">{{ $log->created_at->format('d/m/y H:i') }}</td>
                        <td style="padding: 10px; font-weight: 600">{{ $log->admin_user }}</td>
                        <td style="padding: 10px">
                            <span
                                style="background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td style="padding: 10px; font-size: 0.9rem">{{ $log->details }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 20px">
            @if(isset($logs) && method_exists($logs, 'links'))
                {{ $logs->links() }}
            @endif
        </div>
    </div>
@endsection