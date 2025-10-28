<!-- Modal التفاصيل -->
@include('employee-statistics.partials.details-modal')

<div class="modal fade" id="detailsDataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsDataModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailsDataContent"></div>
            </div>
        </div>
    </div>
</div>
