<style>
    /* Project Code Filter - Datalist Input Special Styling */
    #projectCodeFilter,
    #create_project_code {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: #3b82f6;
        border: 2px solid #3b82f6;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
    }

    #projectCodeFilter::placeholder,
    #create_project_code::placeholder {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 400;
        color: #9ca3af;
        text-transform: none;
        letter-spacing: normal;
    }

    #projectCodeFilter:focus,
    #create_project_code:focus {
        border-color: #1e40af;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        background: #ffffff;
    }

    /* تنسيق datalist options */
    #projectCodesList option,
    #createProjectCodesList option {
        font-family: 'Courier New', monospace;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 12px;
    }

    /* زر المسح */
    #clearProjectCode,
    #clear_create_project_code {
        border-color: #3b82f6;
        color: #3b82f6;
        transition: all 0.3s ease;
    }

    #clearProjectCode:hover,
    #clear_create_project_code:hover {
        background-color: #3b82f6;
        color: white;
    }

    /* تحسين input-group */
    .input-group #projectCodeFilter,
    .input-group #create_project_code {
        border-right: none;
    }

    .input-group #clearProjectCode,
    .input-group #clear_create_project_code {
        border-left: 2px solid #3b82f6;
    }

    /* Project Filter */
    #projectFilter {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    #projectFilter option {
        font-size: 13px;
        padding: 6px 8px;
    }

    #projectFilter option:not([value=""]) {
        padding: 8px 12px;
    }

    /* View Toggle Buttons */
    .btn-group .btn.active {
        background-color: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    /* Transferred Task Styles */
    .transferred-task {
        border-left: 4px solid #ffc107 !important;
        background: linear-gradient(135deg, #fff3cd 0%, #ffffff 100%) !important;
    }

    .transferred-task .kanban-card-title {
        color: #856404;
    }

    .transferred-task .badge.bg-warning {
        background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%) !important;
        color: #000 !important;
        font-weight: 600;
    }

    /* Transfer Type Radio Buttons */
    .form-check-input:checked[value="positive"] {
        background-color: #28a745;
        border-color: #28a745;
    }

    .form-check-input:checked[value="negative"] {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .form-check-label {
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .form-check-label:hover {
        background-color: #f8f9fa;
    }

    .form-check-input:checked+.form-check-label {
        background-color: rgba(0, 123, 255, 0.1);
        border: 1px solid rgba(0, 123, 255, 0.2);
    }

    /* Kanban Column for Transferred Tasks */
    .kanban-header.transferred {
        background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
        color: #000;
        font-weight: 600;
    }

    .kanban-header.transferred h6 {
        color: #000;
        font-weight: 700;
    }

    .kanban-header.transferred .task-count {
        background: rgba(0, 0, 0, 0.2);
        color: #000;
        font-weight: 600;
    }

    .kanban-column[data-status="transferred"] {
        border-left: none;
    }

    .kanban-column[data-status="transferred"]:hover {
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    }

    /* Transfer Info in Kanban Cards */
    .kanban-card-transfer-info {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border: 1px solid #ffc107;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 12px;
        color: #856404;
        display: block;
        margin: 4px 0;
    }

    .kanban-card-transfer-info i {
        color: #ffc107;
    }

    .kanban-card-transfer-info strong {
        color: #856404;
        font-weight: 600;
    }

    .kanban-card-transfer-info small {
        color: #6c757d;
        font-size: 11px;
        line-height: 1.3;
    }

    /* Kanban Board Scroll Fix */
    .kanban-board {
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
        padding-bottom: 20px;
    }

    .kanban-columns {
        display: flex;
        gap: 20px;
        min-width: max-content;
        padding: 0 10px;
    }

    .kanban-column {
        flex: 0 0 300px;
        white-space: normal;
        display: flex;
        flex-direction: column;
        max-height: 80vh;
    }

    .kanban-cards {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 8px;
        margin-right: -8px;
    }

    /* Custom Scrollbar for Kanban Cards */
    .kanban-cards::-webkit-scrollbar {
        width: 6px;
    }

    .kanban-cards::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .kanban-cards::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .kanban-cards::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Custom Scrollbar for Kanban Board */
    .kanban-board::-webkit-scrollbar {
        height: 8px;
    }

    .kanban-board::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .kanban-board::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .kanban-board::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Task Sidebar Styles */
    .task-sidebar::-webkit-scrollbar {
        display: none;
    }

    .task-sidebar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* ✅ تنسيق زر العين المعطل للمهام المنقولة */
    .view-task[disabled] {
        cursor: not-allowed !important;
        opacity: 0.6;
        position: relative;
    }

    .view-task[disabled]:hover {
        transform: none !important;
    }

    /* أيقونة القفل للزر المعطل */
    .btn-secondary.view-task[disabled] {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
    }

    /* تأثير hover معطل */
    .btn-outline-secondary.view-task[disabled]:hover {
        background-color: transparent;
        border-color: #6c757d;
        color: #6c757d;
    }



    .project-cancelled .kanban-card-title::after {
        content: ' (المشروع ملغي)';
        color: #dc3545;
        font-size: 0.85em;
        font-weight: normal;
    }
</style>