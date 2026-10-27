@extends('layouts.app')

@section('page_title', 'Add Default Email Template')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h5 class="fw-bold mb-0">New Email Template</h5>
            </div>
            <form method="POST" action="{{ route('masters.default_email_templates.store') }}">
                @csrf
                <div class="card-body px-4 pb-4">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea name="content" rows="14" class="form-control font-monospace @error('content') is-invalid @enderror">{{ old('content', '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: \'Arial\', sans-serif; margin: 0; padding: 0; background-color: #f7f7f7; color: #444;">

    <div style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);">

        <!-- Header Section -->
        <div style="background: linear-gradient(45deg, #ff9a9e, #fad0c4); padding: 20px; text-align: center; color: #fff;">
            <!-- Company logo with fallback -->
            <img src=\"[company_logo]\" alt=\"[company_name] Logo\" style=\"max-height: 50px;\">
            <h1 style=\"margin: 10px 0 0; font-size: 24px;\">Celebrate the Festival with [company_name]</h1>
        </div>

        <!-- Content Section -->
        <div style=\"padding: 20px;\">
            <h5 style=\"color: #ff6f61;\">Hello [user_name],</h5>
            <p style=\"font-size: 16px; margin: 15px 0;\">As the festival spirit fills the air, we at [company_name] wish you an abundance of joy, health, and prosperity.</p>
            <p style=\"font-size: 16px; margin: 15px 0;\">Here\'s to celebrating with laughter, love, and cherished memories. Enjoy this vibrant time to the fullest!</p>

            <!-- Festival Image -->
            <img src=\"[IMG1]\" alt=\"Festival Image\" style=\"max-width: 100%; margin: 10px 0; border-radius: 10px;\">

            <p style=\"font-weight: bold;\">Happy Festival from all of us at [company_name]!</p>
        </div>

        <!-- Footer Section -->
        <div style=\"background: linear-gradient(45deg, #fad0c4, #ff9a9e); color: #fff; text-align: center; padding: 15px 10px; font-size: 12px;\">
            <p style=\"margin: 5px 0;\">&copy; [company_name]. All rights reserved.</p>
        </div>
    </div>

</body>
</html>') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">You can use placeholders like <code>[company_name]</code>, <code>[user_name]</code>, <code>[IMG1]</code>, etc.</small>
                    </div>
                </div>
                <div class="card-footer bg-body-tertiary border-top px-4 py-3">
                    <a href="{{ route('masters.default_email_templates.index') }}" class="btn btn-light">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-dark-blue px-4 ms-2">
                        <i class="bi bi-save me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

