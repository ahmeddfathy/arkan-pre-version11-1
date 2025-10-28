/**
 * Profile JavaScript Functions
 */

// متغيرات عامة
let currentTab = 'posts';

// تهيئة الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initializeProfile();
});

/**
 * تهيئة البروفايل
 */
function initializeProfile() {
    setupTabNavigation();
    setupCSRFToken();
}

/**
 * إعداد CSRF Token
 */
function setupCSRFToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (!token) {
        const metaTag = document.createElement('meta');
        metaTag.name = 'csrf-token';
        metaTag.content = document.querySelector('input[name="_token"]')?.value || '';
        document.head.appendChild(metaTag);
    }
    window.csrfToken = token ? token.getAttribute('content') : document.querySelector('input[name="_token"]')?.value || '';
}

/**
 * إعداد التنقل بين التبويبات
 */
function setupTabNavigation() {
    const tabButtons = document.querySelectorAll('.tab-btn');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.onclick.toString().match(/showTab\('(\w+)'\)/)[1];
            showTab(tabName);
        });
    });
}

/**
 * إظهار تبويب
 */
function showTab(tabName) {
    // إخفاء جميع التبويبات
    const tabContents = document.querySelectorAll('.tab-content');
    const tabButtons = document.querySelectorAll('.tab-btn');

    tabContents.forEach(content => {
        content.classList.remove('active');
    });

    tabButtons.forEach(button => {
        button.classList.remove('active');
    });

    // إظهار التبويب المحدد
    const targetTab = document.getElementById(tabName + 'Tab');
    const targetButton = document.querySelector(`[onclick="showTab('${tabName}')"]`);

    if (targetTab) {
        targetTab.classList.add('active');
        targetTab.classList.add('fade-in');
    }

    if (targetButton) {
        targetButton.classList.add('active');
    }

    currentTab = tabName;
}

/**
 * متابعة/إلغاء متابعة مستخدم
 */
function toggleFollow(userId) {
    const followBtn = document.getElementById('followBtn');
    const followersCountElement = document.getElementById('followersCount');

    if (!followBtn) return;

    // تعطيل الزر مؤقتاً
    followBtn.disabled = true;
    followBtn.classList.add('loading');

    const originalText = followBtn.innerHTML;
    followBtn.innerHTML = '<div class="spinner"></div> جاري التحديث...';

    fetch(`/social/profile/${userId}/follow`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        const text = await response.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('خطأ في تحليل الاستجابة');
        }

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    })
    .then(data => {
        if (data.success) {
            // تحديث نص الزر
            const icon = followBtn.querySelector('i');
            const span = followBtn.querySelector('span');

            if (data.is_following) {
                followBtn.classList.add('following');
                if (icon) icon.className = 'fas fa-user-check';
                if (span) span.textContent = 'إلغاء المتابعة';
            } else {
                followBtn.classList.remove('following');
                if (icon) icon.className = 'fas fa-user-plus';
                if (span) span.textContent = 'متابعة';
            }

            // تحديث عداد المتابعين
            if (followersCountElement) {
                followersCountElement.textContent = data.followers_count;
                followersCountElement.classList.add('fade-in');
                setTimeout(() => {
                    followersCountElement.classList.remove('fade-in');
                }, 300);
            }

            // إظهار رسالة نجاح
            showNotification(data.message, 'success');

        } else {
            showNotification(data.message || 'حدث خطأ أثناء المتابعة', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ: ' + error.message, 'error');
    })
    .finally(() => {
        followBtn.disabled = false;
        followBtn.classList.remove('loading');
        followBtn.innerHTML = originalText;
    });
}

/**
 * البحث عن المستخدمين
 */
function searchUsers(query) {
    if (!query || query.trim().length < 2) {
        return Promise.resolve([]);
    }

    return fetch(`/social/profile/search?q=${encodeURIComponent(query)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return data.users;
        } else {
            throw new Error(data.message || 'حدث خطأ في البحث');
        }
    });
}

/**
 * الحصول على اقتراحات المتابعة
 */
function getSuggestions() {
    return fetch('/social/profile/suggestions', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return data.suggestions;
        } else {
            throw new Error(data.message || 'حدث خطأ في جلب الاقتراحات');
        }
    });
}

/**
 * إرسال رسالة
 */
function sendMessage(userId) {
    // يمكن تطوير هذه الوظيفة لاحقاً
    showNotification('ميزة الرسائل قيد التطوير', 'info');
}

/**
 * تعديل البروفايل
 */
function editProfile() {
    // يمكن تطوير هذه الوظيفة لاحقاً
    showNotification('ميزة تعديل البروفايل قيد التطوير', 'info');
}

/**
 * عرض المتابعين
 */
function showFollowers(userId) {
    window.location.href = `/social/profile/${userId}/followers`;
}

/**
 * عرض المتابَعين
 */
function showFollowing(userId) {
    window.location.href = `/social/profile/${userId}/following`;
}

/**
 * فتح منشور
 */
function openPost(postId) {
    // يمكن تطوير هذه الوظيفة لاحقاً لفتح المنشور في modal
    showNotification('ميزة عرض المنشور قيد التطوير', 'info');
}

/**
 * عرض الإشعارات
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 600;
        z-index: 3000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 400px;
        word-wrap: break-word;
    `;

    // ألوان حسب النوع
    const colors = {
        success: '#42b883',
        error: '#e74c3c',
        info: '#3498db',
        warning: '#f39c12'
    };

    notification.style.backgroundColor = colors[type] || colors.info;

    document.body.appendChild(notification);

    // إظهار الإشعار
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // إخفاء الإشعار
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

/**
 * تحميل المنشورات بشكل ديناميكي
 */
function loadMorePosts(userId, page = 2) {
    const postsGrid = document.querySelector('.posts-grid');
    if (!postsGrid) return;

    // إضافة مؤشر التحميل
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading-indicator';
    loadingIndicator.innerHTML = '<div class="spinner"></div> جاري تحميل المزيد...';
    postsGrid.appendChild(loadingIndicator);

    fetch(`/social/profile/${userId}?page=${page}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.posts && data.posts.length > 0) {
            data.posts.forEach(post => {
                const postElement = createPostElement(post);
                postsGrid.insertBefore(postElement, loadingIndicator);
            });

            if (data.has_more) {
                // إضافة زر "تحميل المزيد"
                const loadMoreBtn = document.createElement('button');
                loadMoreBtn.className = 'load-more-btn';
                loadMoreBtn.textContent = 'تحميل المزيد';
                loadMoreBtn.onclick = () => loadMorePosts(userId, page + 1);
                postsGrid.appendChild(loadMoreBtn);
            }
        } else {
            showNotification('لا توجد منشورات أخرى', 'info');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ في تحميل المنشورات', 'error');
    })
    .finally(() => {
        if (loadingIndicator && loadingIndicator.parentNode) {
            loadingIndicator.parentNode.removeChild(loadingIndicator);
        }
    });
}

/**
 * إنشاء عنصر منشور
 */
function createPostElement(post) {
    const postDiv = document.createElement('div');
    postDiv.className = 'post-card fade-in';
    postDiv.dataset.postId = post.id;

    postDiv.innerHTML = `
        <div class="post-header">
            <div class="post-user-info">
                <img src="${post.user.profile_photo_url}" alt="${post.user.name}" class="post-avatar">
                <div class="post-user-details">
                    <div class="post-user-name">${post.user.name}</div>
                    <div class="post-time">${post.time_ago}</div>
                </div>
            </div>
        </div>
        <div class="post-content">
            <div class="post-text">${post.formatted_content}</div>
            ${post.image ? `<div class="post-media"><img src="${post.image_url}" alt="صورة المنشور" class="post-image"></div>` : ''}
            ${post.video ? `<div class="post-media"><video controls class="post-video"><source src="${post.video_url}" type="video/mp4"></video></div>` : ''}
        </div>
        <div class="post-stats">
            ${post.likes_count > 0 ? `<div class="likes-info"><span class="likes-count">${post.likes_count}</span></div>` : ''}
            ${post.comments_count > 0 ? `<div class="comments-count">${post.comments_count} تعليق</div>` : ''}
        </div>
        <div class="post-actions">
            <button class="action-button like-btn" onclick="toggleLike(${post.id})">
                <i class="fas fa-thumbs-up"></i>
                <span>إعجاب</span>
            </button>
            <button class="action-button comment-btn">
                <i class="fas fa-comment"></i>
                <span>تعليق</span>
            </button>
        </div>
    `;

    return postDiv;
}

/**
 * تحديث الصفحة عند تغيير الحالة
 */
function refreshProfile() {
    // إعادة تحميل الإحصائيات
    const statsElements = document.querySelectorAll('.stat-number');
    statsElements.forEach(element => {
        element.classList.add('skeleton');
    });

    // محاكاة تحديث البيانات
    setTimeout(() => {
        statsElements.forEach(element => {
            element.classList.remove('skeleton');
        });
    }, 1000);
}

/**
 * مشاركة البروفايل
 */
function shareProfile(userId) {
    const profileUrl = window.location.href;

    if (navigator.share) {
        navigator.share({
            title: 'بروفايل المستخدم',
            url: profileUrl
        }).catch(console.error);
    } else {
        // نسخ الرابط إلى الحافظة
        navigator.clipboard.writeText(profileUrl).then(() => {
            showNotification('تم نسخ رابط البروفايل', 'success');
        }).catch(() => {
            showNotification('فشل في نسخ الرابط', 'error');
        });
    }
}

/**
 * تبديل وضع العرض (شبكة/قائمة)
 */
function toggleViewMode() {
    const postsGrid = document.querySelector('.posts-grid');
    if (!postsGrid) return;

    postsGrid.classList.toggle('list-view');
    postsGrid.classList.toggle('grid-view');

    const viewBtn = document.querySelector('.view-toggle-btn');
    if (viewBtn) {
        const icon = viewBtn.querySelector('i');
        if (postsGrid.classList.contains('list-view')) {
            icon.className = 'fas fa-th';
        } else {
            icon.className = 'fas fa-list';
        }
    }
}

// تصدير الوظائف للاستخدام العام
window.profileFunctions = {
    toggleFollow,
    showTab,
    searchUsers,
    getSuggestions,
    sendMessage,
    editProfile,
    showFollowers,
    showFollowing,
    openPost,
    showNotification,
    loadMorePosts,
    refreshProfile,
    shareProfile,
    toggleViewMode
};
