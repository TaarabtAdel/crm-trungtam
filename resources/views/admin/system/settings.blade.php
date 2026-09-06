@extends('layouts.admin')

@section('title', 'Cài đặt')

@section('content')
<div class="page-card" style="max-width:640px">
    <div class="card-header-custom"><h5 class="mb-0 font-weight-bold">Cài đặt hệ thống</h5></div>
    <div class="card-body-custom">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Tên trung tâm</label>
                <input name="center_name" class="form-control" value="{{ old('center_name', $settings['center_name']) }}" required>
            </div>
            <div class="form-group">
                <label>Logo text (sidebar)</label>
                <input name="logo_text" class="form-control" value="{{ old('logo_text', $settings['logo_text']) }}" required>
            </div>
            <button class="btn btn-primary">Lưu thay đổi</button>
        </form>
    </div>
</div>
@endsection
