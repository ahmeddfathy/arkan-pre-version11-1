$(document).ready(function() {
    initializeGraphicTasksHandlers();
});

function initializeGraphicTasksHandlers() {
    $('#service_id').change(function() {
        handleServiceChange(false);
    });

    $('#graphic_task_type_id').change(function() {
        handleGraphicTaskTypeChange(false);
    });

    $('#edit_service_id').change(function() {
        handleServiceChange(true);
    });

    $('#edit_graphic_task_type_id').change(function() {
        handleGraphicTaskTypeChange(true);
    });

    $('#is_flexible_time').change(function() {
        handleFlexibleTimeForGraphic();
    });
}

function handleServiceChange(isEdit = false) {
    const serviceSelector = isEdit ? '#edit_service_id' : '#service_id';
    const graphicSectionSelector = isEdit ? '#edit_graphic_task_type_section' : '#graphic_task_type_section';
    const graphicTypeSelector = isEdit ? '#edit_graphic_task_type_id' : '#graphic_task_type_id';
    const graphicInfoSelector = isEdit ? '#edit_graphic_task_info' : '#graphic_task_info';
    const timeSectionSelector = isEdit ? '' : '#time_estimation_section';
    const hoursSelector = isEdit ? '#edit_estimated_hours' : '#estimated_hours';
    const minutesSelector = isEdit ? '#edit_estimated_minutes' : '#estimated_minutes';
    const flexibleTimeSelector = isEdit ? '' : '#is_flexible_time';
    const flexibleNoticeId = isEdit ? 'edit_flexible_time_notice' : 'flexible_time_notice';

    const selectedOption = $(serviceSelector).find('option:selected');
    const serviceName = selectedOption.data('service-name');

    if (serviceName && (serviceName.includes('جرافيك') || serviceName.includes('تصميم') || serviceName.includes('graphic') || serviceName.includes('design'))) {
        $(graphicSectionSelector).slideDown();
        $(graphicTypeSelector).attr('required', !isEdit ? true : false);

        if (!isEdit) {
            $(timeSectionSelector).slideUp();
            $(hoursSelector + ', ' + minutesSelector).attr('required', false).val(0);

            $(flexibleTimeSelector).prop('checked', false).prop('disabled', true);
            $(flexibleTimeSelector).closest('.form-check').slideUp();
        } else {
            $(hoursSelector + ', ' + minutesSelector).closest('.col-md-6').slideUp();
            $(hoursSelector + ', ' + minutesSelector).attr('required', false).val(0);
            $(hoursSelector + ', ' + minutesSelector).attr('readonly', true);
            $(hoursSelector + ', ' + minutesSelector).addClass('bg-light text-muted');
        }

        if ($('#' + flexibleNoticeId).length === 0) {
            const insertAfterElement = isEdit ? $(graphicSectionSelector) : $(flexibleTimeSelector).closest('.form-check');
            insertAfterElement.after('<div id="' + flexibleNoticeId + '" class="alert alert-info mt-2"><i class="fas fa-info-circle"></i> الوقت المقدر سيتم تحديده تلقائياً من نوع المهمة الجرافيكية المحدد</div>');
        }
    } else {
        $(graphicSectionSelector).slideUp();
        $(graphicTypeSelector).attr('required', false).val('');
        $(graphicInfoSelector).hide();

        if (!isEdit) {
            $(timeSectionSelector).slideDown();
            $(hoursSelector + ', ' + minutesSelector).attr('required', true);

            $(flexibleTimeSelector).prop('disabled', false);
            $(flexibleTimeSelector).closest('.form-check').slideDown();
        } else {
            $(hoursSelector + ', ' + minutesSelector).closest('.col-md-6').slideDown();
            $(hoursSelector + ', ' + minutesSelector).attr('required', true);
            $(hoursSelector + ', ' + minutesSelector).attr('readonly', false);
            $(hoursSelector + ', ' + minutesSelector).removeClass('bg-light text-muted');
        }

        $('#' + flexibleNoticeId).remove();
    }
}

function handleGraphicTaskTypeChange(isEdit = false) {
    const graphicTypeSelector = isEdit ? '#edit_graphic_task_type_id' : '#graphic_task_type_id';
    const graphicInfoSelector = isEdit ? '#edit_graphic_task_info' : '#graphic_task_info';
    const taskDetailsSelector = isEdit ? '#edit_task_details' : '#task_details';
    const timeSectionSelector = isEdit ? '' : '#time_estimation_section';
    const hoursSelector = isEdit ? '#edit_estimated_hours' : '#estimated_hours';
    const minutesSelector = isEdit ? '#edit_estimated_minutes' : '#estimated_minutes';
    const graphicSectionSelector = isEdit ? '#edit_graphic_task_type_section' : '#graphic_task_type_section';
    const flexibleNoticeId = isEdit ? 'edit_flexible_time_notice' : 'flexible_time_notice';

    const selectedOption = $(graphicTypeSelector).find('option:selected');

    if (selectedOption.val()) {
        const points = selectedOption.data('points');
        const minTime = selectedOption.data('min-time');
        const maxTime = selectedOption.data('max-time');
        const avgTime = selectedOption.data('avg-time');

        const details = `النقاط: ${points} | الوقت المتوقع: ${minTime}-${maxTime} دقيقة | المتوسط: ${avgTime} دقيقة`;
        $(taskDetailsSelector).text(details);
        $(graphicInfoSelector).slideDown();

        if (!isEdit) {
            $(timeSectionSelector).slideUp();
            $(hoursSelector + ', ' + minutesSelector).attr('required', false);
        } else {
            $(hoursSelector + ', ' + minutesSelector).closest('.col-md-6').slideUp();
            $(hoursSelector + ', ' + minutesSelector).attr('required', false);
            $(hoursSelector + ', ' + minutesSelector).attr('readonly', true);
            $(hoursSelector + ', ' + minutesSelector).addClass('bg-light text-muted');
        }

        if ($('#' + flexibleNoticeId).length === 0) {
            $(graphicSectionSelector).after('<div id="' + flexibleNoticeId + '" class="alert alert-info mt-2"><i class="fas fa-info-circle"></i> الوقت المقدر سيتم تحديده تلقائياً من نوع المهمة الجرافيكية المحدد</div>');
        }
    } else {
        $(graphicInfoSelector).slideUp();

        if (!isEdit) {
            $(timeSectionSelector).slideDown();
            $(hoursSelector + ', ' + minutesSelector).attr('required', true);
        } else {
            $(hoursSelector + ', ' + minutesSelector).closest('.col-md-6').slideDown();
            $(hoursSelector + ', ' + minutesSelector).attr('required', true);
            $(hoursSelector + ', ' + minutesSelector).attr('readonly', false);
            $(hoursSelector + ', ' + minutesSelector).removeClass('bg-light text-muted');
        }

        $('#' + flexibleNoticeId).remove();
    }
}

function handleFlexibleTimeForGraphic() {
    if (!$('#is_flexible_time').is(':checked')) {
        const selectedGraphicType = $('#graphic_task_type_id').find('option:selected');
        if (selectedGraphicType.val()) {
            const avgTime = selectedGraphicType.data('avg-time');
            const hours = Math.floor(avgTime / 60);
            const minutes = avgTime % 60;
            $('#estimated_hours').val(hours);
            $('#estimated_minutes').val(minutes);
        }
    }
}
