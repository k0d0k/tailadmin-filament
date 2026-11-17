@extends('layouts.tailadmin')

@section('title', 'Ubah Password')
@section('subtitle', 'Perbarui kata sandi akun Anda')

@section('content')
    <form method="POST" action="{{ route('panel.password.update') }}" class="mx-auto max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div class="ta-card">
            <div class="ta-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Ubah Password</h2>
                    <p class="text-xs text-gray-400">Masukkan password saat ini lalu tentukan password baru.</p>
                </div>
            </div>

            <div class="space-y-5 p-5">
                <div>
                    <label for="current_password" class="ta-label">
                        Password Saat Ini <span class="text-error-500">*</span>
                    </label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                           class="ta-input @error('current_password') !border-error-500 @enderror">
                    @error('current_password')
                        <p class="ta-help !text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="ta-label">
                        Password Baru <span class="text-error-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" autocomplete="new-password"
                           class="ta-input @error('password') !border-error-500 @enderror">
                    <p class="ta-help">Minimal 8 karakter.</p>
                    @error('password')
                        <p class="ta-help !text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="ta-label">
                        Konfirmasi Password Baru <span class="text-error-500">*</span>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                           class="ta-input">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                <a href="{{ route('panel.dashboard') }}" class="ta-btn-ghost">Batal</a>
                <button type="submit" class="ta-btn-primary">
                    <x-panel-icon name="lock-closed" class="h-4 w-4" />
                    Simpan Password
                </button>
            </div>
        </div>
    </form>
@endsection
