<!-- Transfer Task Sidebar -->
<div id="transferSidebar" class="transfer-sidebar">
    <div class="sidebar-overlay" id="transferSidebarOverlay" onclick="closeTransferSidebar()"></div>
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="sidebar-icon bg-warning">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="ms-3">
                        <h5 class="mb-0" id="transferSidebarTitle">نقل المهمة</h5>
                        <small class="text-muted" id="transferSidebarSubtitle">تحويل المهمة لمستخدم آخر</small>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-secondary" onclick="closeTransferSidebar()" style="border-radius: 8px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Sidebar Content -->
        <div class="sidebar-body" id="transferSidebarContent">
            <form id="transferTaskForm">
                <input type="hidden" id="taskType" name="taskType">
                <input type="hidden" id="taskId" name="taskId">

                <!-- Task Info -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">معلومات المهمة</label>
                    <div class="p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-tasks text-primary me-2"></i>
                            <span class="fw-semibold" id="taskName">-</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user text-info me-2"></i>
                            <span class="text-muted" id="currentUser">-</span>
                        </div>
                    </div>
                </div>

                <!-- Transfer To User -->
                <div class="mb-4">
                    <label for="userInput" class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i>
                        نقل إلى المستخدم
                    </label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="userInput" name="userInput"
                               list="usersList" placeholder="اكتب اسم المستخدم..." required autocomplete="off"
                               style="border-radius: 8px; padding: 12px 16px;">
                        <datalist id="usersList">
                            <!-- سيتم ملؤها بـ JavaScript -->
                        </datalist>
                        <div id="selectedUserInfo" class="position-absolute user-info-dropdown d-none mt-2">
                            <div class="alert alert-info alert-sm mb-0" style="border-radius: 8px; font-size: 12px;">
                                <i class="fas fa-user me-2"></i>
                                <span id="selectedUserDetails"></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="toUserId" name="toUserId">
                    <div class="form-text mt-2" style="font-size: 11px;">اكتب اسم المستخدم واختر من القائمة</div>
                </div>

                <!-- Transfer Type -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-exchange-alt me-1"></i>
                        نوع النقل
                    </label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="transferType" id="positiveTransfer" value="positive" checked>
                                <label class="form-check-label" for="positiveTransfer">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-thumbs-up text-success me-2"></i>
                                        <div>
                                            <strong>نقل إيجابي</strong>
                                            <small class="d-block text-muted">لا يخصم نقاط - يحسب إنجاز</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="transferType" id="negativeTransfer" value="negative">
                                <label class="form-check-label" for="negativeTransfer">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-thumbs-down text-danger me-2"></i>
                                        <div>
                                            <strong>نقل سلبي</strong>
                                            <small class="d-block text-muted">يخصم نقاط - تحفيز للتحسن</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Deadline -->
                <div class="mb-4">
                    <label for="newDeadline" class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-calendar-alt me-1"></i>
                        موعد نهائي جديد (اختياري)
                    </label>
                    <input type="date" class="form-control" id="newDeadline" name="newDeadline"
                           style="border-radius: 8px; padding: 12px 16px;">
                    <div class="form-text mt-2" style="font-size: 11px;">
                        إذا تُرك فارغاً، سيتم الاحتفاظ بنفس الموعد النهائي الأصلي
                    </div>
                </div>

                <!-- Transfer Reason -->
                <div class="mb-4">
                    <label for="transferReason" class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-comment me-1"></i>
                        سبب النقل (اختياري)
                    </label>
                    <textarea class="form-control" id="transferReason" name="transferReason"
                              rows="3" placeholder="اكتب سبب نقل المهمة..."
                              style="border-radius: 8px; padding: 12px 16px; resize: vertical;"></textarea>
                </div>

                <!-- Status Messages -->
                <div id="transferCheck" class="alert alert-warning d-none mb-3" style="border-radius: 8px; font-size: 13px;">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    جاري التحقق من إمكانية النقل...
                </div>

                <div id="transferError" class="alert alert-danger d-none mb-3" style="border-radius: 8px; font-size: 13px;"></div>
                <div id="transferSuccess" class="alert alert-success d-none mb-3" style="border-radius: 8px; font-size: 13px;"></div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 pt-3" style="border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-warning px-4 py-2" id="confirmTransferBtn" style="border-radius: 8px; font-weight: 500; flex: 1;">
                        <i class="fas fa-exchange-alt me-1"></i>
                        نقل المهمة
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-4 py-2" onclick="closeTransferSidebar()" style="border-radius: 8px; font-weight: 500;">
                        <i class="fas fa-times me-1"></i>
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
