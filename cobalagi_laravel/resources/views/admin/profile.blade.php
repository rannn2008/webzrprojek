@extends('layouts.admin')

@section('header_title', 'Pengaturan Profil')
@section('header_subtitle', 'Kelola keamanan dan identitas digital Anda')

@section('content')
    <div style="max-width: 650px; margin: 0 auto;">
        @if(session('success'))
            <div class="alert alert-success"
                style="background: #E8F5E9; color: #2E7D32; padding: 20px; border-radius: 15px; margin-bottom: 25px; border-left: 5px solid #4CAF50;">
                <i class="fas fa-check-circle" style="margin-right: 10px;"></i> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger"
                style="background: #FFEBEE; color: #C62828; padding: 20px; border-radius: 15px; margin-bottom: 25px; border-left: 5px solid #F44336;">
                @foreach($errors->all() as $error)
                    <div style="margin-bottom: 5px;"><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i>
                        {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="admin-card" style="padding: 40px; background: white; box-shadow: var(--shadow);">
            <div style="text-align: center; margin-bottom: 40px;">
                <div
                    style="width: 100px; height: 100px; border-radius: 50%; background: #fdfbf7; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 3px solid var(--primary-light); color: var(--primary); font-size: 2.5rem; box-shadow: var(--shadow);">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2 style="font-family: 'Playfair Display', serif; font-size: 1.8rem; color: var(--dark);">Konfigurasi Akun
                </h2>
            </div>

            <form action="{{ route('admin.profile.update') }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom: 30px;">
                    <label
                        style="display: block; margin-bottom: 10px; font-weight: 700; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Username
                        Admin</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username', $admin->username) }}"
                        required
                        style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; font-weight: 600; font-size: 1.1rem; color: var(--dark); outline: none; transition: var(--transition);">
                </div>

                <div style="border-top: 1px dashed #ddd; margin: 40px 0; position: relative; text-align: center;">
                    <span
                        style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 0 20px; color: var(--gray); font-size: 0.75rem; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;">
                        Ubah Password Keamanan
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px;">
                    <div class="form-group">
                        <label
                            style="display: block; margin-bottom: 10px; font-weight: 700; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Password
                            Baru</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••"
                            style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                    </div>

                    <div class="form-group">
                        <label
                            style="display: block; margin-bottom: 10px; font-weight: 700; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Konfirmasi</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••"
                            style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full"
                    style="padding: 18px; font-size: 1.1rem; border-radius: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-check-double" style="margin-right: 10px;"></i> Simpan Perubahan Akun
                </button>
            </form>
        </div>

        <p style="text-align: center; margin-top: 30px; font-size: 0.85rem; color: var(--gray);">
            Terakhir diperbarui: {{ $admin->updated_at ? $admin->updated_at->format('d M Y, H:i') : 'Baru saja' }}
        </p>
    </div>
@endsection