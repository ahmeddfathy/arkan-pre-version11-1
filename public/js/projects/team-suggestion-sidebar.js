


document.addEventListener('DOMContentLoaded', function() {

    let currentAnalysis = null;
    let currentServiceId = null;


    function openTeamSuggestionSidebar() {
        const sidebar = document.getElementById('teamSuggestionSidebar');
        const overlay = document.getElementById('teamSuggestionSidebarOverlay');

        if (sidebar && overlay) {
            sidebar.classList.add('show');
            overlay.classList.add('show');
            sidebar.style.right = '0';

            document.body.style.overflow = 'hidden';
        }
    }


    function closeTeamSuggestionSidebar() {
        const sidebar = document.getElementById('teamSuggestionSidebar');
        const overlay = document.getElementById('teamSuggestionSidebarOverlay');

        if (sidebar && overlay) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            sidebar.style.right = '-650px';

            document.body.style.overflow = '';
        }
    }

    window.openTeamSuggestionSidebar = openTeamSuggestionSidebar;
    window.closeTeamSuggestionSidebar = closeTeamSuggestionSidebar;


    document.querySelectorAll('.smart-suggestion-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const serviceId = this.dataset.serviceId;
            const serviceName = this.dataset.serviceName;
            const projectId = this.dataset.projectId;

            currentServiceId = serviceId;

            openTeamSuggestionSidebar();

            document.getElementById('teamSuggestionSidebarTitle').innerHTML =
                `<i class="fas fa-brain me-2"></i>نظام الاقتراح الذكي للفرق`;

            document.getElementById('teamSuggestionSidebarSubtitle').innerHTML =
                `خدمة: ${serviceName}`;

            document.getElementById('teamSuggestionSidebarContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">جاري التحليل...</span>
                    </div>
                    <p class="mt-3 text-muted">جاري تحليل أحمال الفرق وحساب أفضل اقتراح...</p>
                </div>
            `;

            document.getElementById('selectRecommendedTeamSidebar').style.display = 'none';

            fetchTeamAnalysis(projectId, serviceId);
        });
    });


    function fetchTeamAnalysis(projectId, serviceId) {
        fetch(`/projects/${projectId}/smart-team-suggestion/${serviceId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentAnalysis = data;
                displayTeamAnalysis(data);
            } else {
                displayError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayError('حدث خطأ أثناء التحليل');
        });
    }


    function displayTeamAnalysis(data) {
        const content = document.getElementById('teamSuggestionSidebarContent');

        if (!data.teams_analysis || data.teams_analysis.length === 0) {
            content.innerHTML = `
                <div class="p-4">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <h5>لا توجد فرق متاحة</h5>
                        <p>لا توجد فرق في قسم ${data.service.department} حالياً</p>
                    </div>
                </div>
            `;
            return;
        }

        const bestTeam = data.best_team;
        let html = '<div class="p-4">';

        if (bestTeam) {
            html += generateRecommendedTeamHTML(bestTeam);
        }


        html += `
            <h6 class="mb-3">
                <i class="fas fa-chart-bar me-2"></i>
                تحليل مفصل لجميع الفرق المتاحة
            </h6>
            <div class="teams-list">
        `;

        data.teams_analysis.forEach((team, index) => {
            html += generateTeamCardHTML(team, index === 0);
        });

        html += '</div></div>';

        content.innerHTML = html;

        if (bestTeam) {
            document.getElementById('selectRecommendedTeamSidebar').style.display = 'inline-block';
        }

        bindToggleMembersButtons();
    }


    function generateRecommendedTeamHTML(bestTeam) {
        return `
            <div class="recommended-team-card mb-4" style="
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border: 2px solid #e2e8f0;
                border-radius: 20px;
                padding: 2rem;
                position: relative;
                overflow: hidden;
            ">
                <!-- شريط ذهبي في الأعلى -->
                <div style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, #fbbf24, #f59e0b, #d97706);
                "></div>

                <!-- أيقونة الإنجاز -->
                <div class="text-center mb-3">
                    <div style="
                        width: 80px;
                        height: 80px;
                        background: linear-gradient(135deg, #fbbf24, #f59e0b);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto;
                        box-shadow: 0 8px 25px rgba(251, 191, 36, 0.3);
                    ">
                        <i class="fas fa-crown" style="font-size: 2rem; color: white;"></i>
                    </div>
                </div>

                <!-- العنوان الرئيسي -->
                <div class="text-center mb-3">
                    <h4 style="
                        color: #1e293b;
                        font-weight: 700;
                        font-size: 1.5rem;
                        margin-bottom: 0.5rem;
                    ">🏆 الفريق المقترح</h4>
                    <h5 style="
                        color: #3730a3;
                        font-weight: 600;
                        font-size: 1.3rem;
                        margin: 0;
                    ">${bestTeam.team.name}</h5>
                </div>

                <!-- معلومات الفريق -->
                <div class="team-recommended-info" style="
                    background: white;
                    border-radius: 15px;
                    padding: 1.5rem;
                    margin-bottom: 1.5rem;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                ">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item" style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="
                                    width: 40px;
                                    height: 40px;
                                    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
                                    border-radius: 10px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                ">
                                    <i class="fas fa-user-tie" style="color: white; font-size: 1rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 500;">قائد الفريق</div>
                                    <div style="font-weight: 600; color: #1e293b;">${bestTeam.team.owner_name}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item" style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="
                                    width: 40px;
                                    height: 40px;
                                    background: linear-gradient(135deg, #06b6d4, #0891b2);
                                    border-radius: 10px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                ">
                                    <i class="fas fa-chart-line" style="color: white; font-size: 1rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 500;">نقاط التقييم</div>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 1.2rem;">${bestTeam.score}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- سبب الاقتراح -->
                <div style="
                    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
                    border-radius: 12px;
                    padding: 1.25rem;
                    border-left: 4px solid #8b5cf6;
                ">
                    <div style="display: flex; align-items: start; gap: 0.75rem;">
                        <i class="fas fa-lightbulb" style="color: #7c3aed; font-size: 1.2rem; margin-top: 0.2rem;"></i>
                        <div>
                            <div style="font-weight: 600; color: #5b21b6; margin-bottom: 0.5rem;">سبب الاقتراح:</div>
                            <div style="color: #6b46c1; line-height: 1.6;">${bestTeam.recommendation_reason}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * إنشاء HTML لكارد الفريق - تصميم جديد
     * @param {object} team - بيانات الفريق
     * @param {boolean} isRecommended - هل هو الفريق المقترح
     * @returns {string} HTML string
     */
    function generateTeamCardHTML(team, isRecommended) {
        const borderStyle = isRecommended ? 'border: 3px solid #fbbf24;' : 'border: 1px solid #e2e8f0;';
        const badgeColor = isRecommended ? 'background: linear-gradient(135deg, #fbbf24, #f59e0b);' : 'background: linear-gradient(135deg, #6b7280, #4b5563);';
        const crownIcon = isRecommended ? '<i class="fas fa-star" style="color: #fbbf24; margin-left: 0.5rem;"></i>' : '';

        return `
            <div class="team-analysis-card mb-4" style="
                background: white;
                border-radius: 16px;
                ${borderStyle}
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                overflow: hidden;
                transition: all 0.3s ease;
            ">
                <!-- Header الكارد -->
                <div style="
                    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
                    padding: 1.5rem;
                    border-bottom: 1px solid #e2e8f0;
                ">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 style="
                                color: #1e293b;
                                font-weight: 700;
                                font-size: 1.3rem;
                                margin: 0;
                                display: flex;
                                align-items: center;
                            ">
                                ${team.team.name}
                                ${crownIcon}
                            </h5>
                            <p style="
                                color: #64748b;
                                margin: 0.5rem 0 0 0;
                                font-size: 0.9rem;
                            ">
                                <i class="fas fa-user-tie" style="margin-left: 0.5rem; color: #8b5cf6;"></i>
                                ${team.team.owner_name}
                            </p>
                        </div>
                        <div style="
                            ${badgeColor}
                            color: white;
                            padding: 0.75rem 1.25rem;
                            border-radius: 12px;
                            font-weight: 700;
                            font-size: 1rem;
                            text-align: center;
                            min-width: 80px;
                        ">
                            <div style="font-size: 1.4rem; margin-bottom: 0.2rem;">${team.score}</div>
                            <div style="font-size: 0.75rem; opacity: 0.9;">نقطة</div>
                        </div>
                    </div>
                </div>

                <!-- محتوى الكارد -->
                <div style="padding: 1.5rem;">
                    ${generateNewTeamInfoHTML(team)}
                    ${generateNewStatsGridHTML(team)}
                    ${generateNewAlertsHTML(team)}
                    ${generateNewRecommendationReasonHTML(team)}
                    ${generateNewMembersDetailsHTML(team)}
                </div>
            </div>
        `;
    }

    /**
     * إنشاء HTML لمعلومات الفريق - تصميم جديد
     * @param {object} team - بيانات الفريق
     * @returns {string} HTML string
     */
    function generateNewTeamInfoHTML(team) {
        return `
            <div style="
                background: linear-gradient(135deg, #f8fafc, #f1f5f9);
                border-radius: 12px;
                padding: 1.25rem;
                margin-bottom: 1.5rem;
                border: 1px solid #e2e8f0;
            ">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="
                                width: 35px;
                                height: 35px;
                                background: linear-gradient(135deg, #06b6d4, #0891b2);
                                border-radius: 8px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas fa-users" style="color: white; font-size: 0.9rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: #64748b; font-weight: 500;">عدد الأعضاء</div>
                                <div style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">${team.workload.team_size}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="
                                width: 35px;
                                height: 35px;
                                background: linear-gradient(135deg, #8b5cf6, #7c3aed);
                                border-radius: 8px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas fa-cogs" style="color: white; font-size: 0.9rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: #64748b; font-weight: 500;">العبء الفعلي</div>
                                <div style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">${team.workload.effective_workload}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }


    function generateTeamInfoHTML(team) {
        return generateNewTeamInfoHTML(team);
    }


    function generateNewStatsGridHTML(team) {
        return `
            <div style="margin-bottom: 1.5rem;">
                <h6 style="
                    color: #374151;
                    font-weight: 600;
                    margin-bottom: 1rem;
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                ">
                    <i class="fas fa-chart-bar" style="color: #6366f1; margin-left: 0.5rem;"></i>
                    إحصائيات العمل
                </h6>
                <div class="row g-3">
                    <div class="col-6">
                        <div style="
                            background: linear-gradient(135deg, #eff6ff, #dbeafe);
                            border-radius: 12px;
                            padding: 1.25rem;
                            text-align: center;
                            border: 1px solid #bfdbfe;
                        ">
                            <div style="
                                font-size: 2rem;
                                font-weight: 800;
                                color: #1d4ed8;
                                margin-bottom: 0.5rem;
                                line-height: 1;
                            ">${team.workload.total_active_projects}</div>
                            <div style="
                                font-size: 0.85rem;
                                color: #1e40af;
                                font-weight: 600;
                            ">مشاريع نشطة</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="
                            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
                            border-radius: 12px;
                            padding: 1.25rem;
                            text-align: center;
                            border: 1px solid #7dd3fc;
                        ">
                            <div style="
                                font-size: 2rem;
                                font-weight: 800;
                                color: #0369a1;
                                margin-bottom: 0.5rem;
                                line-height: 1;
                            ">${team.workload.effective_workload}</div>
                            <div style="
                                font-size: 0.85rem;
                                color: #0c4a6e;
                                font-weight: 600;
                            ">عبء فعلي</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }


    function generateStatsGridHTML(team) {
        return generateNewStatsGridHTML(team);
    }



    function generateNewAlertsHTML(team) {
        let alerts = '';

        if (team.workload.total_ending_soon_projects > 0 || team.workload.total_overdue_projects > 0) {
            alerts += `
                <div style="margin-bottom: 1.5rem;">
                    <h6 style="
                        color: #374151;
                        font-weight: 600;
                        margin-bottom: 1rem;
                        font-size: 1rem;
                        display: flex;
                        align-items: center;
                    ">
                        <i class="fas fa-exclamation-circle" style="color: #f59e0b; margin-left: 0.5rem;"></i>
                        تنبيهات مهمة
                    </h6>
                    <div class="row g-2">
            `;

            if (team.workload.total_ending_soon_projects > 0) {
                alerts += `
                    <div class="col-12">
                        <div style="
                            background: linear-gradient(135deg, #fef3c7, #fde68a);
                            border: 1px solid #fbbf24;
                            border-radius: 10px;
                            padding: 1rem;
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                        ">
                            <div style="
                                width: 30px;
                                height: 30px;
                                background: #f59e0b;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-clock" style="color: white; font-size: 0.8rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #92400e; margin-bottom: 0.2rem;">
                                    ${team.workload.total_ending_soon_projects} مشروع سينتهي قريباً
                                </div>
                                <div style="font-size: 0.8rem; color: #b45309;">
                                    يتطلب انتباه خلال الأيام القادمة
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            if (team.workload.total_overdue_projects > 0) {
                alerts += `
                    <div class="col-12">
                        <div style="
                            background: linear-gradient(135deg, #fee2e2, #fecaca);
                            border: 1px solid #f87171;
                            border-radius: 10px;
                            padding: 1rem;
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                        ">
                            <div style="
                                width: 30px;
                                height: 30px;
                                background: #ef4444;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-exclamation-triangle" style="color: white; font-size: 0.8rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #991b1b; margin-bottom: 0.2rem;">
                                    ${team.workload.total_overdue_projects} مشروع متأخر
                                </div>
                                <div style="font-size: 0.8rem; color: #b91c1c;">
                                    يحتاج إلى إجراء عاجل
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            alerts += `
                    </div>
                </div>
            `;
        }

        return alerts;
    }


    function generateAlertsHTML(team) {
        return generateNewAlertsHTML(team);
    }


    function generateNewRecommendationReasonHTML(team) {
        return `
            <div style="
                background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
                border: 1px solid #7dd3fc;
                border-radius: 12px;
                padding: 1.25rem;
                margin-bottom: 1.5rem;
                border-right: 4px solid #0ea5e9;
            ">
                <div style="display: flex; align-items: start; gap: 0.75rem;">
                    <div style="
                        width: 35px;
                        height: 35px;
                        background: linear-gradient(135deg, #0ea5e9, #0284c7);
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                        margin-top: 0.2rem;
                    ">
                        <i class="fas fa-lightbulb" style="color: white; font-size: 0.9rem;"></i>
                    </div>
                    <div>
                        <div style="
                            font-weight: 600;
                            color: #0c4a6e;
                            margin-bottom: 0.5rem;
                            font-size: 1rem;
                        ">سبب التقييم:</div>
                        <div style="
                            color: #075985;
                            line-height: 1.6;
                            font-size: 0.95rem;
                        ">${team.recommendation_reason}</div>
                    </div>
                </div>
            </div>
        `;
    }


    function generateRecommendationReasonHTML(team) {
        return generateNewRecommendationReasonHTML(team);
    }


    function generateNewMembersDetailsHTML(team) {
        const membersHTML = team.workload.member_details.map(member => `
            <div style="
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 1.25rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            ">
                <div style="display: flex; justify-content-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <div style="
                            font-weight: 700;
                            color: #1f2937;
                            font-size: 1.1rem;
                            margin-bottom: 0.5rem;
                            display: flex;
                            align-items: center;
                        ">
                            <i class="fas fa-user-circle" style="color: #6366f1; margin-left: 0.5rem; font-size: 1.2rem;"></i>
                            ${member.user.name}
                        </div>
                    </div>
                    <div style="
                        background: linear-gradient(135deg, #6b7280, #4b5563);
                        color: white;
                        padding: 0.5rem 1rem;
                        border-radius: 20px;
                        font-size: 0.85rem;
                        font-weight: 600;
                    ">
                        ${member.active_projects} مشروع
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div style="
                            background: linear-gradient(135deg, #eff6ff, #dbeafe);
                            border-radius: 8px;
                            padding: 0.75rem;
                            text-align: center;
                        ">
                            <div style="font-size: 1.3rem; font-weight: 700; color: #1d4ed8; margin-bottom: 0.2rem;">${member.effective_workload}</div>
                            <div style="font-size: 0.75rem; color: #1e40af; font-weight: 500;">عبء فعلي</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="
                            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
                            border-radius: 8px;
                            padding: 0.75rem;
                            text-align: center;
                        ">
                            <div style="font-size: 1.3rem; font-weight: 700; color: #0369a1; margin-bottom: 0.2rem;">${member.active_tasks}</div>
                            <div style="font-size: 0.75rem; color: #0c4a6e; font-weight: 500;">مهام نشطة</div>
                        </div>
                    </div>
                </div>

                ${member.ending_soon_projects > 0 || member.overdue_projects > 0 ? `
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.5rem; font-weight: 600;">تنبيهات:</div>
                        <div class="row g-2">
                            ${member.ending_soon_projects > 0 ? `
                                <div class="col-6">
                                    <div style="
                                        background: #fef3c7;
                                        color: #92400e;
                                        padding: 0.5rem;
                                        border-radius: 6px;
                                        font-size: 0.75rem;
                                        text-align: center;
                                        font-weight: 600;
                                    ">
                                        <i class="fas fa-clock" style="margin-left: 0.3rem;"></i>
                                        ${member.ending_soon_projects} قريب انتهاء
                                    </div>
                                </div>
                            ` : ''}
                            ${member.overdue_projects > 0 ? `
                                <div class="col-6">
                                    <div style="
                                        background: #fee2e2;
                                        color: #991b1b;
                                        padding: 0.5rem;
                                        border-radius: 6px;
                                        font-size: 0.75rem;
                                        text-align: center;
                                        font-weight: 600;
                                    ">
                                        <i class="fas fa-exclamation-triangle" style="margin-left: 0.3rem;"></i>
                                        ${member.overdue_projects} متأخر
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
            </div>
        `).join('');

        return `
            <div style="margin-top: 1rem;">
                <button style="
                    width: 100%;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    color: white;
                    border: none;
                    border-radius: 10px;
                    padding: 0.875rem 1.25rem;
                    font-weight: 600;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                    cursor: pointer;
                " class="toggle-members" data-team-id="${team.team.id}" type="button">
                    <i class="fas fa-users" style="margin-left: 0.5rem;"></i>
                    عرض تفاصيل الأعضاء (${team.workload.member_details.length})
                </button>
                <div class="members-details" id="members-${team.team.id}" style="display: none; margin-top: 1rem;">
                    ${membersHTML}
                </div>
            </div>
        `;
    }


    function generateMembersDetailsHTML(team) {
        return generateNewMembersDetailsHTML(team);
    }


    function generateFooterHTML() {
        return `
            <div class="card-footer text-center" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; border-radius: 0 0 16px 16px;">
                <small>
                    <i class="fas fa-star me-2"></i>
                    الاختيار الأمثل
                </small>
            </div>
        `;
    }


    function bindToggleMembersButtons() {
        document.querySelectorAll('.toggle-members').forEach(btn => {
            btn.addEventListener('click', function() {
                const teamId = this.dataset.teamId;
                const detailsDiv = document.getElementById(`members-${teamId}`);
                const isVisible = detailsDiv.style.display !== 'none';

                if (isVisible) {
                    detailsDiv.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-eye me-2"></i>عرض تفاصيل الأعضاء';
                } else {
                    detailsDiv.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-eye-slash me-2"></i>إخفاء التفاصيل';
                }
            });
        });
    }


    function displayError(message) {
        const content = document.getElementById('teamSuggestionSidebarContent');
        content.innerHTML = `
            <div class="p-4">
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <h5>حدث خطأ</h5>
                    <p>${message}</p>
                </div>
            </div>
        `;
    }


    document.getElementById('selectRecommendedTeamSidebar').addEventListener('click', function() {
        if (currentAnalysis && currentAnalysis.best_team && currentServiceId) {
            selectRecommendedTeam();
        }
    });


    function selectRecommendedTeam() {
        const bestTeam = currentAnalysis.best_team;
        const teamId = bestTeam.team.id;

        const teamRadio = document.querySelector(`input[name="add_type_${currentServiceId}"][value="team"]`);
        if (teamRadio) {
            teamRadio.checked = true;
            teamRadio.dispatchEvent(new Event('change'));

            setTimeout(() => {
                const teamSelect = document.querySelector(`select.team-select[data-service-id="${currentServiceId}"]`);
                if (teamSelect) {
                    teamSelect.value = teamId;
                    teamSelect.dispatchEvent(new Event('change'));

                    closeTeamSuggestionSidebar();

                    showSuccessMessage(`تم اختيار الفريق المقترح: ${bestTeam.team.name}`);
                }
            }, 100);
        }
    }


    function showSuccessMessage(message) {
        if (window.toastr) {
            toastr.success(message, 'اقتراح ذكي ✨');
        } else {
            alert(message);
        }
    }
});
