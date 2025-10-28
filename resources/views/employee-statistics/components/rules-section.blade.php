<!-- قواعد التقييم -->
<div class="card-body border-bottom pb-3">
    <div class="mb-4">
        <div class="d-flex align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-book me-2 text-primary"></i> قواعد التقييم وأسس احتساب النقاط
            </h5>
            <button class="btn btn-sm btn-outline-secondary ms-3" type="button" id="rulesToggleBtn">
                <i class="fas fa-chevron-down" id="rulesIcon"></i> عرض/إخفاء القواعد
            </button>
        </div>

        <div id="rulesCollapse" style="display: none;">
            <div class="card card-body bg-light">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h6 class="fw-bold text-primary mb-3">كيفية احتساب التقييم النهائي للموظفين</h6>
                        <p>يتم تقييم أداء الموظفين بناءً على ثلاثة مؤشرات رئيسية ولكل منها وزن نسبي في التقييم النهائي:</p>


                    </div>

                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>المؤشر</th>
                                        <th>الوزن النسبي</th>
                                        <th>القواعد</th>
                                        <th>حالات خصم النقاط</th>
                                        <th>مثال</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- الحضور -->
                                    <tr>
                                        <td class="text-primary fw-bold">
                                            <i class="fas fa-user-check me-1"></i> الحضور
                                        </td>
                                        <td>45% من التقييم الكلي</td>
                                        <td>
                                            <ul class="mb-0 ps-3">
                                                <li>الدرجة الكاملة (100%) لمن لديه نسبة حضور 100%</li>
                                                <li>يتم خصم النقاط بنفس نسبة الغياب</li>
                                                <li>لا يتم احتساب الإجازات المعتمدة كغياب</li>
                                            </ul>
                                        </td>
                                        <td>
                                            يتم خصم نقاط من درجة الحضور في حالة:
                                            <ul class="mb-0 ps-3">
                                                <li>الغياب بدون إجازة معتمدة</li>
                                            </ul>
                                        </td>
                                        <td>
                                            <small>
                                                إذا كان لدى الموظف 22 يوم عمل، وحضر 20 يوم منها:<br>
                                                - نسبة الحضور = 90.9%<br>
                                                - درجة مؤشر الحضور = 90.9%<br>
                                                - التأثير على التقييم النهائي = 90.9% × 45% = 40.9%
                                            </small>
                                        </td>
                                    </tr>

                                    <!-- الالتزام بالمواعيد -->
                                    <tr>
                                        <td class="text-success fw-bold">
                                            <i class="fas fa-clock me-1"></i> الالتزام بالمواعيد
                                        </td>
                                        <td>20% من التقييم الكلي</td>
                                        <td>
                                            <ul class="mb-0 ps-3">
                                                <li>الحد المسموح للتأخير: 120 دقيقة شهرياً</li>
                                                <li>يتم مضاعفة الحد المسموح تلقائياً حسب الفترة المحددة (مثال: 240 دقيقة لشهرين، 360 دقيقة لثلاثة أشهر)</li>
                                                <li>إذا كان التأخير أقل من أو يساوي الحد المسموح للفترة المحددة، تكون الدرجة 100%</li>
                                                <li>إذا زاد التأخير عن الحد المسموح، يتم خصم نقاط بنسبة التجاوز</li>
                                            </ul>
                                        </td>
                                        <td>
                                            يتم خصم نقاط من درجة الالتزام بالمواعيد في حالة:
                                            <ul class="mb-0 ps-3">
                                                <li>تجاوز الحد المسموح للتأخير (120 دقيقة شهرياً × عدد الشهور)</li>
                                                <li>كل 120 دقيقة إضافية (لكل شهر) تؤدي لخصم 100% من الدرجة</li>
                                            </ul>
                                        </td>
                                        <td>
                                            <small>
                                                إذا تأخر الموظف 180 دقيقة خلال الشهر:<br>
                                                - تجاوز بمقدار 60 دقيقة (180 - 120)<br>
                                                - نسبة التجاوز = 60 ÷ 120 = 50%<br>
                                                - درجة مؤشر الالتزام = 100% - 50% = 50%<br>
                                                - التأثير على التقييم النهائي = 50% × 20% = 10%
                                            </small>
                                        </td>
                                    </tr>

                                    <!-- ساعات العمل -->
                                    <tr>
                                        <td class="text-info fw-bold">
                                            <i class="fas fa-business-time me-1"></i> ساعات العمل
                                        </td>
                                        <td>20% من التقييم الكلي</td>
                                        <td>
                                            <ul class="mb-0 ps-3">
                                                <li>المعيار: 8 ساعات يومياً</li>
                                                <li>متوسط ساعات العمل يقاس كنسبة من 8 ساعات</li>
                                                <li>يتم مراعاة نسبة الحضور في الاحتساب</li>
                                            </ul>
                                        </td>
                                        <td>
                                            يتم خصم نقاط من درجة ساعات العمل في حالة:
                                            <ul class="mb-0 ps-3">
                                                <li>إذا كان متوسط ساعات العمل اليومية أقل من 8 ساعات</li>
                                            </ul>
                                        </td>
                                        <td>
                                            <small>
                                                إذا كان متوسط ساعات عمل الموظف 7 ساعات يومياً:<br>
                                                - نسبة ساعات العمل = 7 ÷ 8 = 87.5%<br>
                                                - درجة مؤشر ساعات العمل = 87.5%<br>
                                                - التأثير على التقييم النهائي = 87.5% × 20% = 17.5%
                                            </small>
                                        </td>
                                    </tr>

                                    <!-- الأذونات -->
                                    <tr>
                                        <td class="text-warning fw-bold">
                                            <i class="fas fa-door-open me-1"></i> الأذونات
                                        </td>
                                        <td>لا يتم احتسابها حالياً</td>
                                        <td>
                                            <ul class="mb-0 ps-3">
                                                <li>الحد المسموح للأذونات: 180 دقيقة شهرياً</li>
                                                <li>يتم مضاعفة الحد المسموح تلقائياً حسب الفترة المحددة (مثال: 360 دقيقة لشهرين، 540 دقيقة لثلاثة أشهر)</li>
                                                <li>لا يؤثر تجاوز الحد حالياً على التقييم النهائي</li>
                                            </ul>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">لا يتم خصم نقاط حالياً</span>
                                        </td>
                                        <td>
                                            <small>لا يؤثر على التقييم النهائي في الوقت الحالي</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 p-3 bg-white rounded border">
                            <h6 class="fw-bold mb-2">كيفية حساب التقييم النهائي:</h6>
                            <div class="formula p-2 bg-light rounded">
                                <code dir="ltr">التقييم النهائي = (درجة الحضور × 45%) + (درجة الالتزام بالمواعيد × 20%) + (درجة ساعات العمل × 35%)</code>
                            </div>

                            <h6 class="fw-bold mt-3 mb-2">مستويات التقييم:</h6>
                            <div class="d-flex flex-wrap">
                                <div class="me-3 mb-2"><span class="badge bg-success me-1">90% - 100%</span> ممتاز</div>
                                <div class="me-3 mb-2"><span class="badge bg-primary me-1">80% - 89%</span> جيد جداً</div>
                                <div class="me-3 mb-2"><span class="badge bg-info me-1">70% - 79%</span> جيد</div>
                                <div class="me-3 mb-2"><span class="badge bg-warning me-1">60% - 69%</span> مقبول</div>
                                <div class="me-3 mb-2"><span class="badge bg-danger me-1">أقل من 60%</span> يحتاج إلى تحسين</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
