@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/my-tasks.css') }}">
<!-- Tasks Kanban CSS -->
<link rel="stylesheet" href="{{ asset('css/tasks/tasks-kanban.css') }}">
<!-- Projects Kanban CSS for Notes Indicator -->
<link rel="stylesheet" href="{{ asset('css/projects/projects-kanban.css') }}">
<!-- Tasks Calendar CSS -->
<link rel="stylesheet" href="{{ asset('css/tasks/my-tasks-calendar.css') }}">
<!-- Tasks Statistics CSS -->
<link rel="stylesheet" href="{{ asset('css/tasks/my-tasks-stats.css') }}">
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Transfer Sidebar CSS -->
<link rel="stylesheet" href="{{ asset('css/projects/transfer-sidebar.css') }}">
<!-- Task Revisions CSS -->
<link rel="stylesheet" href="{{ asset('css/task-revisions.css') }}">
<!-- Add user ID for JavaScript -->
<meta name="user-id" content="{{ Auth::id() }}">

@include('tasks.partials-index.styles')
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                @include('tasks.partials-index.header')

                <div class="card-body">
                    @include('tasks.partials-index.statistics')

                    @include('tasks.partials-index.filters')

                    @include('tasks.partials-index.table-view')

                    @include('tasks.partials-index.kanban-view')

                    @include('tasks.partials-index.calendar-view')

                    <div class="mt-4">
                                                    @unless(request('show_all') == '1')
                                {{ $tasks->links() }}
                            @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('tasks.partials-index.modals.create-task')

<!-- View Task Modal - HIDDEN: Now using Sidebar instead -->
<div class="modal fade" id="viewTaskModal" tabindex="-1" aria-labelledby="viewTaskModalLabel" aria-hidden="true" style="display: none !important;">
    <!-- Modal content removed - using sidebar instead -->
</div>

@include('tasks.partials-index.modals.edit-task')

@include('tasks.partials-index.modals.delete-task')

@include('tasks.partials-index.transfer-sidebar')

@include('tasks.partials-index.task-sidebar')

@push('scripts')
@include('tasks.partials-index.scripts')
@endpush
@endsection
