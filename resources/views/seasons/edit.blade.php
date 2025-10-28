@extends('layouts.app')

@section('title', 'تعديل السيزون')

@push('styles')
<style>
    .image-preview {
        max-width: 100%;
        height: 150px;
        border-radius: 5px;
        margin-top: 10px;
        object-fit: cover;
    }
    .color-preview {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 10px;
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit ml-2"></i>
                        تعديل السيزون: {{ $season->name }}
                    </h5>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('seasons.update', $season) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group row">
                            <label for="name" class="col-md-3 col-form-label text-md-right">اسم السيزون <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $season->name) }}" required autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="description" class="col-md-3 col-form-label text-md-right">الوصف</label>
                            <div class="col-md-9">
                                <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description', $season->description) }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="start_date" class="col-md-3 col-form-label text-md-right">تاريخ البداية <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input id="start_date" type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date', $season->start_date->format('Y-m-d\TH:i')) }}" required>
                                @error('start_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="end_date" class="col-md-3 col-form-label text-md-right">تاريخ النهاية <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input id="end_date" type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date', $season->end_date->format('Y-m-d\TH:i')) }}" required>
                                @error('end_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="color_theme" class="col-md-3 col-form-label text-md-right">لون السيزون</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="color-preview" id="colorPreview" style="background-color: {{ $season->color_theme }};"></span>
                                    </div>
                                    <input id="color_theme" type="color" class="form-control @error('color_theme') is-invalid @enderror" name="color_theme" value="{{ old('color_theme', $season->color_theme) }}">
                                </div>
                                @error('color_theme')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="image" class="col-md-3 col-form-label text-md-right">صورة السيزون</label>
                            <div class="col-md-9">
                                <input id="image" type="file" class="form-control-file @error('image') is-invalid @enderror" name="image" accept="image/*">
                                @error('image')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="form-text text-muted">صورة أيقونة السيزون (اختياري). اترك فارغاً للاحتفاظ بالصورة الحالية.</small>
                                @if($season->image)
                                    <img id="imagePreview" class="image-preview" src="{{ asset('storage/'.$season->image) }}" alt="{{ $season->name }}">
                                @else
                                    <img id="imagePreview" class="image-preview" src="#" alt="معاينة الصورة" style="display: none;">
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="banner_image" class="col-md-3 col-form-label text-md-right">صورة الغلاف</label>
                            <div class="col-md-9">
                                <input id="banner_image" type="file" class="form-control-file @error('banner_image') is-invalid @enderror" name="banner_image" accept="image/*">
                                @error('banner_image')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="form-text text-muted">صورة غلاف السيزون (اختياري). اترك فارغاً للاحتفاظ بالصورة الحالية.</small>
                                @if($season->banner_image)
                                    <img id="bannerPreview" class="image-preview" src="{{ asset('storage/'.$season->banner_image) }}" alt="{{ $season->name }}">
                                @else
                                    <img id="bannerPreview" class="image-preview" src="#" alt="معاينة الغلاف" style="display: none;">
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-9 offset-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $season->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        نشط
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-9 offset-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save ml-1"></i>
                                    حفظ التغييرات
                                </button>
                                <a href="{{ route('seasons.index') }}" class="btn btn-secondary mr-2">
                                    <i class="fas fa-times ml-1"></i>
                                    إلغاء
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // معاينة صورة السيزون
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }

                reader.readAsDataURL(this.files[0]);
            }
        });

        // معاينة صورة الغلاف
        const bannerInput = document.getElementById('banner_image');
        const bannerPreview = document.getElementById('bannerPreview');

        bannerInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    bannerPreview.src = e.target.result;
                    bannerPreview.style.display = 'block';
                }

                reader.readAsDataURL(this.files[0]);
            }
        });

        // معاينة اللون
        const colorInput = document.getElementById('color_theme');
        const colorPreview = document.getElementById('colorPreview');

        colorInput.addEventListener('input', function() {
            colorPreview.style.backgroundColor = this.value;
        });
    });
</script>
@endpush
