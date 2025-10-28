<!-- Task Sidebar (مثل Asana) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeTaskSidebar()" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; visibility: hidden; opacity: 0; transition: all 0.3s ease;"></div>

<div class="task-sidebar" id="taskSidebar" style="position: fixed; top: 0; left: -480px; width: 460px; height: 100vh; background: #ffffff; z-index: 1050; transition: left 0.4s cubic-bezier(0.4, 0.0, 0.2, 1); box-shadow: 0 8px 40px rgba(0,0,0,0.12); overflow-y: auto; border-right: 1px solid #e1e5e9; scrollbar-width: none; -ms-overflow-style: none;">
    <!-- Sidebar Header -->
    <div class="sidebar-header" style="background: #ffffff; color: #333; padding: 24px 32px 16px 32px; border-bottom: 1px solid #e9ecef; position: sticky; top: 0; z-index: 10;">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="flex-grow-1">
                <h4 id="taskSidebarTitle" class="mb-2" style="font-weight: 600; color: #2c3e50; font-size: 1.5rem; line-height: 1.3;">تفاصيل المهمة</h4>
                <p id="taskSidebarSubtitle" class="mb-0" style="font-size: 14px; color: #6c757d; margin: 0;">المشروع</p>
            </div>
            <button onclick="closeTaskSidebar()" class="btn btn-link p-0 ms-3" style="color: #6c757d; opacity: 0.7; font-size: 20px; background: none; border: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="taskSidebarBadge" class="badge" style="background: #e8f5e8; color: #2d7d2d; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                <i class="fas fa-layer-group me-1"></i>مهمة قالب
            </span>
        </div>
    </div>

    <div id="taskSidebarContent" style="padding: 0; min-height: calc(100vh - 140px);">
    </div>
</div>
