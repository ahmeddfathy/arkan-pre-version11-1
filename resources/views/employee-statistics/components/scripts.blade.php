@push('scripts')
<script src="{{ asset('js/employee-statistics/ui-controls.js') }}?t={{ time() }}"></script>
<script src="{{ asset('js/employee-statistics/date-utils.js') }}?t={{ time() }}"></script>
<script src="{{ asset('js/employee-statistics/performance-calculations.js') }}?t={{ time() }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js" integrity="sha512-H+rglffZ6f5gF7UJgvH4Naa+fGCgjrHKMgoFOGmcPTRwR6oILo5R+gtzNrpDp7iMV3udbymBVjkeZGNz1Em4rQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('js/employee-statistics/data-fetching.js') }}?t={{ time() }}"></script>
<script src="{{ asset('js/employee-statistics/charts-init.js') }}?t={{ time() }}"></script>
@endpush
