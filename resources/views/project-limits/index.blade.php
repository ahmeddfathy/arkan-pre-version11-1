@extends('layouts.app')

@section('title', 'إدارة الحد الشهري للمشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/project-limits.css') }}">
@endpush

@section('content')
<div class="limits-container">
  <div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header-limits">
      <h1>📊 إدارة الحد الشهري للمشاريع</h1>
      <p>تحديد وإدارة الحد الأقصى للمشاريع الشهرية للشركة والأقسام والفرق والموظفين</p>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div></div>
      <div class="d-flex gap-2">
        <a href="{{ route('project-limits.report') }}" class="btn btn-custom btn-gradient-light">
          <i class="fas fa-chart-bar"></i>
          التقرير الشهري
        </a>
        <a href="{{ route('project-limits.create') }}" class="btn btn-custom btn-gradient-primary">
          <i class="fas fa-plus"></i>
          إضافة حد جديد
        </a>
      </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle fa-lg"></i>
      <span>{{ session('success') }}</span>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-circle fa-lg"></i>
      <span>{{ session('error') }}</span>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Statistics Row -->
    <div class="stats-row-limits">
      <div class="stat-card-modern" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <i class="fas fa-building fa-2x mb-3" style="opacity: 0.9;"></i>
        <div class="stat-number-modern">{{ $limits->where('limit_type', 'company')->count() }}</div>
        <div class="stat-label-modern" style="color: rgba(255,255,255,0.9);">حدود الشركة</div>
      </div>
      <div class="stat-card-modern" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white;">
        <i class="fas fa-sitemap fa-2x mb-3" style="opacity: 0.9;"></i>
        <div class="stat-number-modern">{{ $limits->where('limit_type', 'department')->count() }}</div>
        <div class="stat-label-modern" style="color: rgba(255,255,255,0.9);">حدود الأقسام</div>
      </div>
      <div class="stat-card-modern" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white;">
        <i class="fas fa-users fa-2x mb-3" style="opacity: 0.9;"></i>
        <div class="stat-number-modern">{{ $limits->where('limit_type', 'team')->count() }}</div>
        <div class="stat-label-modern" style="color: rgba(255,255,255,0.9);">حدود الفرق</div>
      </div>
      <div class="stat-card-modern" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white;">
        <i class="fas fa-user fa-2x mb-3" style="opacity: 0.9;"></i>
        <div class="stat-number-modern">{{ $limits->where('limit_type', 'user')->count() }}</div>
        <div class="stat-label-modern" style="color: rgba(255,255,255,0.9);">حدود الموظفين</div>
      </div>
    </div>

    <!-- Limits Table -->
    <div class="limits-table-container">
      <div class="limits-table-header">
        <h2>📋 قائمة الحدود الشهرية</h2>
      </div>

      @if($limits->count() > 0)
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead style="background: #f8fafc;">
            <tr>
              <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">#</th>
              <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">النوع</th>
              <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">الشهر</th>
              <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">الكيان</th>
              <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">الحد الشهري</th>
              <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">الحالة</th>
              <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">ملاحظات</th>
              <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600; text-align: center;">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            @foreach($limits as $limit)
            <tr style="transition: all 0.3s ease; border-bottom: 1px solid #f0f0f0;">
              <td style="padding: 1.5rem;">{{ $loop->iteration + ($limits->currentPage() - 1) * $limits->perPage() }}</td>
              <td style="padding: 1.5rem;">
                <span class="badge badge-modern" style="background: {{
                                            $limit->limit_type === 'company' ? 'linear-gradient(135deg, #667eea, #764ba2)' :
                                            ($limit->limit_type === 'department' ? 'linear-gradient(135deg, #17a2b8, #138496)' :
                                            ($limit->limit_type === 'team' ? 'linear-gradient(135deg, #28a745, #1e7e34)' : 'linear-gradient(135deg, #ffc107, #e0a800)'))
                                        }}; color: white;">
                  {{ $limit->limit_type_text }}
                </span>
              </td>
              <td style="padding: 1.5rem;">
                <span class="badge badge-modern" style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white;">
                  {{ $limit->month_name }}
                </span>
              </td>
              <td style="padding: 1.5rem;">
                @if($limit->limit_type === 'company')
                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                  <i class="fas fa-building" style="color: #667eea;"></i>
                  <span>الشركة بالكامل</span>
                </div>
                @elseif($limit->limit_type === 'user' && $limit->user)
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <i class="fas fa-user" style="color: #ffc107;"></i>
                  <span>{{ $limit->user->name }}</span>
                </div>
                @elseif($limit->limit_type === 'team' && $limit->team)
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <i class="fas fa-users" style="color: #28a745;"></i>
                  <span>{{ $limit->team->name }}</span>
                </div>
                @else
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <i class="fas fa-sitemap" style="color: #17a2b8;"></i>
                  <span>{{ $limit->entity_name }}</span>
                </div>
                @endif
              </td>
              <td style="padding: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <strong style="font-size: 1.25rem; color: #667eea;">{{ $limit->monthly_limit }}</strong>
                  <small style="color: #6b7280;">مشروع</small>
                </div>
              </td>
              <td style="padding: 1.5rem;">
                @if($limit->is_active)
                <span class="badge badge-modern" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white;">
                  <i class="fas fa-check-circle me-1"></i>
                  نشط
                </span>
                @else
                <span class="badge badge-modern" style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white;">
                  <i class="fas fa-times-circle me-1"></i>
                  غير نشط
                </span>
                @endif
              </td>
              <td style="padding: 1.5rem;">
                @if($limit->notes)
                <small style="color: #6b7280;">{{ Str::limit($limit->notes, 30) }}</small>
                @else
                <small style="color: #9ca3af;">-</small>
                @endif
              </td>
              <td style="padding: 1.5rem; text-align: center;">
                <div class="btn-group" role="group">
                  <a href="{{ route('project-limits.edit', $limit) }}"
                    class="btn btn-sm"
                    style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 8px 0 0 8px; padding: 0.5rem 1rem; transition: all 0.3s ease;"
                    title="تعديل"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.3)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <i class="fas fa-edit"></i>
                  </a>
                  <form action="{{ route('project-limits.destroy', $limit) }}"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('هل أنت متأكد من حذف هذا الحد؟')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                      class="btn btn-sm"
                      style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-radius: 0 8px 8px 0; padding: 0.5rem 1rem; transition: all 0.3s ease;"
                      title="حذف"
                      onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(220, 53, 69, 0.3)'"
                      onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-3">
        {{ $limits->links() }}
      </div>
      @else
      <div style="text-align: center; padding: 5rem 2rem;">
        <i class="fas fa-inbox" style="font-size: 5rem; color: #dee2e6; margin-bottom: 2rem;"></i>
        <h4 style="color: #6b7280; font-weight: 600; margin-bottom: 1rem;">لا توجد حدود شهرية محددة بعد</h4>
        <p style="color: #9ca3af; margin-bottom: 2rem;">ابدأ بإضافة أول حد شهري للمشاريع</p>
        <a href="{{ route('project-limits.create') }}" class="btn btn-custom btn-gradient-primary">
          <i class="fas fa-plus"></i>
          إضافة أول حد شهري
        </a>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection