

let createPostModal = null;
let currentEditingPost = null;
let reactionTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeSocialSystem();
});

function initializeSocialSystem() {
    createPostModal = document.getElementById('createPostModal');
    setupEventListeners();
    setupCSRFToken();
    initializeReactionMenus();
}

function setupEventListeners() {
    window.addEventListener('click', function(event) {
        if (event.target === createPostModal) {
            closeCreatePostModal();
        }
        closeAllOptionsMenus(event);
    });

    const postTextarea = document.getElementById('postContent');
    if (postTextarea) {
        postTextarea.addEventListener('input', autoResizeTextarea);
    }

    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('emoji-btn') ||
            event.target.classList.contains('reply-emoji-btn') ||
            event.target.closest('.emoji-btn') ||
            event.target.closest('.reply-emoji-btn')) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
}

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

function initializeReactionMenus() {
    const likeButtons = document.querySelectorAll('.like-btn, .comment-action-btn, .reply-action-btn');
    likeButtons.forEach(button => {
        createReactionMenu(button);
        button.addEventListener('mouseenter', function() {
            showReactionMenu(this);
        });
        button.addEventListener('mouseleave', function() {
            hideReactionMenuDelayed(this);
        });
    });
}

function createReactionMenu(button) {
    if (button.querySelector('.reactions-menu')) return;

    const reactionsMenu = document.createElement('div');
    reactionsMenu.className = 'reactions-menu';

    const reactions = [
        { type: 'like', emoji: '👍', color: '#1877f2' },
        { type: 'love', emoji: '❤️', color: '#f33e58' },
        { type: 'haha', emoji: '😂', color: '#ffb74d' },
        { type: 'wow', emoji: '😮', color: '#ffb74d' },
        { type: 'sad', emoji: '😢', color: '#ffb74d' },
        { type: 'angry', emoji: '😡', color: '#f4511e' }
    ];

    reactions.forEach(reaction => {
        const reactionEmoji = document.createElement('div');
        reactionEmoji.className = `reaction-emoji ${reaction.type}`;
        reactionEmoji.textContent = reaction.emoji;
        reactionEmoji.title = getReactionName(reaction.type);

        reactionEmoji.addEventListener('click', function(e) {
            e.stopPropagation();
            selectReaction(button, reaction.type);
            hideReactionMenu(button);
        });

        reactionEmoji.addEventListener('mouseenter', function() {
            clearTimeout(reactionTimeout);
        });

        reactionsMenu.appendChild(reactionEmoji);
    });

    // إضافة أحداث للقائمة نفسها
    reactionsMenu.addEventListener('mouseenter', function() {
        clearTimeout(reactionTimeout);
    });

    reactionsMenu.addEventListener('mouseleave', function() {
        hideReactionMenuDelayed(button);
    });

    button.style.position = 'relative';
    button.appendChild(reactionsMenu);
}

/**
 * عرض قائمة الإيموجيات
 */
function showReactionMenu(button) {
    clearTimeout(reactionTimeout);

    const menu = button.querySelector('.reactions-menu');
    if (menu) {

        document.querySelectorAll('.reactions-menu.show').forEach(otherMenu => {
            if (otherMenu !== menu) {
                otherMenu.classList.remove('show');
            }
        });

        menu.classList.add('show');
    }
}


function hideReactionMenuDelayed(button) {
    reactionTimeout = setTimeout(() => {
        hideReactionMenu(button);
    }, 500);
}


function hideReactionMenu(button) {
    const menu = button.querySelector('.reactions-menu');
    if (menu) {
        menu.classList.remove('show');
    }
}


function selectReaction(button, reactionType) {
    const postId = getPostIdFromButton(button);
    const commentId = getCommentIdFromButton(button);

    if (commentId) {
        toggleCommentLike(commentId, reactionType);
    } else if (postId) {
        toggleLike(postId, reactionType);
    }
}


function getPostIdFromButton(button) {
    const postCard = button.closest('.post-card');
    return postCard ? postCard.dataset.postId : null;
}


function getCommentIdFromButton(button) {
    const comment = button.closest('.comment, .reply');
    return comment ? comment.dataset.commentId || comment.dataset.replyId : null;
}


function getReactionName(type) {
    const names = {
        'like': 'إعجاب',
        'love': 'حب',
        'haha': 'ضحك',
        'wow': 'واو',
        'sad': 'حزين',
        'angry': 'غاضب'
    };
    return names[type] || 'إعجاب';
}


function openCreatePostModal(type = null) {
    if (!createPostModal) {
        showNotification('خطأ في تحميل النافذة', 'error');
        return;
    }

    createPostModal.classList.remove('modal-hidden');
    document.body.style.overflow = 'hidden';

    // Focus on the textarea
    setTimeout(() => {
        const postContent = document.getElementById('postContent');
        if (postContent) {
            postContent.focus();
        }
    }, 100);

    // If the type is specified, open the appropriate option
    if (type === 'image') {
        const imageInput = document.getElementById('imageInput');
        if (imageInput) imageInput.click();
    } else if (type === 'video') {
        const videoInput = document.getElementById('videoInput');
        if (videoInput) videoInput.click();
    }
}

/**
 * Close the create post modal
 */
function closeCreatePostModal() {
    if (createPostModal) {
        createPostModal.classList.add('modal-hidden');
        document.body.style.overflow = 'auto';

        // Reset the form
        resetCreatePostForm();
    }
}


function resetCreatePostForm() {
    const form = document.getElementById('createPostForm');
    if (form) {
        form.reset();
    }

    // إخفاء معاينة الوسائط
    const mediaPreview = document.getElementById('mediaPreview');
    if (mediaPreview) {
        mediaPreview.style.display = 'none';
        mediaPreview.innerHTML = '';
    }

    // إعادة تعيين زر الإرسال
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.textContent = 'نشر';
        submitBtn.disabled = false;
    }

    // إغلاق منتقي الإيموجي
    closeAllEmojiPickers();
}

/**
 * معاينة الوسائط
 */
function previewMedia(input, type) {
    const file = input.files[0];
    if (!file) return;

    const mediaPreview = document.getElementById('mediaPreview');
    if (!mediaPreview) return;

    mediaPreview.innerHTML = '';

    if (type === 'image') {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.width = '100%';
        img.style.height = 'auto';
        img.style.maxHeight = '300px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '8px';

        mediaPreview.appendChild(img);
    } else if (type === 'video') {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.controls = true;
        video.style.width = '100%';
        video.style.height = 'auto';
        video.style.maxHeight = '300px';
        video.style.borderRadius = '8px';

        mediaPreview.appendChild(video);
    }

    // إضافة زر حذف
    const removeBtn = document.createElement('button');
    removeBtn.innerHTML = '&times;';
    removeBtn.className = 'remove-media-btn';
    removeBtn.style.cssText = `
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,0.5);
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    removeBtn.addEventListener('click', function() {
        input.value = '';
        mediaPreview.style.display = 'none';
        mediaPreview.innerHTML = '';
    });

    mediaPreview.style.position = 'relative';
    mediaPreview.appendChild(removeBtn);
    mediaPreview.style.display = 'block';
}

/**
 * تغيير حجم textarea تلقائياً
 */
function autoResizeTextarea(event) {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
}

/**
 * إرسال منشور جديد
 */
document.addEventListener('submit', function(event) {
    if (event.target.id === 'createPostForm') {
        event.preventDefault();
        submitPost();
    } else if (event.target.id && event.target.id.startsWith('editPostForm')) {
        event.preventDefault();
        const postId = event.target.id.replace('editPostForm', '');
        updatePost(event, postId);
    }
});

/**
 * إرسال المنشور
 */
function submitPost() {
    const form = document.getElementById('createPostForm');
    if (!form) {
        showNotification('خطأ في النموذج', 'error');
        return;
    }

    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitBtn');

    // تعطيل الزر وإظهار التحميل
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'جاري النشر... <div class="spinner"></div>';
    }

    fetch('/social/posts', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        const text = await response.text();

        // تحقق إذا كان النص يحتوي على HTML (صفحة خطأ)
        if (text.startsWith('<!DOCTYPE') || text.startsWith('<html')) {
            throw new Error('تم الحصول على صفحة HTML بدلاً من JSON - تحقق من الـ routes');
        }

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('خطأ في تحليل الاستجابة: ' + text.substring(0, 100));
        }

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    })
    .then(data => {
        if (data.success) {
            showNotification('تم نشر المنشور بنجاح', 'success');
            closeCreatePostModal();
            // إعادة تحميل الصفحة لعرض المنشور الجديد
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'حدث خطأ أثناء نشر المنشور', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ: ' + error.message, 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'نشر';
        }
    });
}

/**
 * إظهار/إخفاء خيارات المنشور
 */
function togglePostOptions(postId) {
    const menu = document.getElementById(`postOptions${postId}`);
    if (!menu) return;

    const isVisible = menu.style.display === 'block';

    // إغلاق جميع القوائم الأخرى
    closeAllOptionsMenus();

    // تبديل القائمة الحالية
    menu.style.display = isVisible ? 'none' : 'block';
}

/**
 * إظهار/إخفاء خيارات التعليق
 */
function toggleCommentOptions(commentId) {
    const menu = document.getElementById(`commentOptions${commentId}`);
    if (!menu) return;

    const isVisible = menu.style.display === 'block';

    // إغلاق جميع القوائم الأخرى
    closeAllOptionsMenus();

    // تبديل القائمة الحالية
    menu.style.display = isVisible ? 'none' : 'block';
}

/**
 * إظهار/إخفاء خيارات الرد
 */
function toggleReplyOptions(replyId) {
    const menu = document.getElementById(`replyOptions${replyId}`);
    if (!menu) return;

    const isVisible = menu.style.display === 'block';

    // إغلاق جميع القوائم الأخرى
    closeAllOptionsMenus();

    // تبديل القائمة الحالية
    menu.style.display = isVisible ? 'none' : 'block';
}

/**
 * إغلاق جميع قوائم الخيارات
 */
function closeAllOptionsMenus(event = null) {
    const menus = document.querySelectorAll('.post-options-menu, .comment-options-menu, .reply-options-menu');
    menus.forEach(menu => {
        if (!event || (!menu.contains(event.target) && !menu.previousElementSibling.contains(event.target))) {
            menu.style.display = 'none';
        }
    });

    // إغلاق قوائم الإيموجيات أيضاً
    if (event) {
        document.querySelectorAll('.reactions-menu.show').forEach(menu => {
            const button = menu.parentElement;
            if (button && !button.contains(event.target)) {
                menu.classList.remove('show');
            }
        });
    }
}

/**
 * فتح نافذة تعديل المنشور
 */
function editPost(postId) {
    // إغلاق قائمة الخيارات
    const optionsMenu = document.getElementById(`postOptions${postId}`);
    if (optionsMenu) {
        optionsMenu.style.display = 'none';
    }

    // فتح نافذة التعديل
    const editModal = document.getElementById(`editPostModal${postId}`);
    if (editModal) {
        editModal.classList.remove('modal-hidden');
        document.body.style.overflow = 'hidden';

        // التركيز على النص
        setTimeout(() => {
            const textarea = editModal.querySelector('.post-textarea');
            if (textarea) {
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }
        }, 100);
    } else {
        showNotification('خطأ في فتح نافذة التعديل', 'error');
    }
}

/**
 * إغلاق نافذة تعديل المنشور
 */
function closeEditPostModal(postId) {
    const editModal = document.getElementById(`editPostModal${postId}`);
    if (editModal) {
        editModal.classList.add('modal-hidden');
        document.body.style.overflow = 'auto';
    }
}

/**
 * تحديث المنشور
 */
function updatePost(event, postId) {
    const form = document.getElementById(`editPostForm${postId}`);
    if (!form) {
        showNotification('خطأ في النموذج', 'error');
        return;
    }

    const formData = new FormData(form);
    const submitBtn = form.querySelector('.submit-btn');

    // تعطيل الزر وإظهار التحميل
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'جاري الحفظ... <div class="spinner"></div>';
    }

    fetch(`/social/posts/${postId}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'X-HTTP-Method-Override': 'PUT',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        const text = await response.text();

        // تحقق إذا كان النص يحتوي على HTML (صفحة خطأ)
        if (text.startsWith('<!DOCTYPE') || text.startsWith('<html')) {
            throw new Error('تم الحصول على صفحة HTML بدلاً من JSON - تحقق من الـ routes');
        }

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('خطأ في تحليل الاستجابة: ' + text.substring(0, 100));
        }

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    })
    .then(data => {
        if (data.success) {
            showNotification('تم تحديث المنشور بنجاح', 'success');
            closeEditPostModal(postId);

            // تحديث محتوى المنشور في الصفحة
            const postTextElement = document.getElementById(`postText${postId}`);
            if (postTextElement && data.post) {
                postTextElement.innerHTML = data.post.content.replace(/\n/g, '<br>');
            }
        } else {
            showNotification(data.message || 'حدث خطأ أثناء تحديث المنشور', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ: ' + error.message, 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'حفظ التغييرات';
        }
    });
}

/**
 * حذف منشور
 */
function deletePost(postId) {
    if (!confirm('هل أنت متأكد من حذف هذا المنشور؟')) return;

    fetch(`/social/posts/${postId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        const text = await response.text();

        // تحقق إذا كان النص يحتوي على HTML (صفحة خطأ)
        if (text.startsWith('<!DOCTYPE') || text.startsWith('<html')) {
            throw new Error('تم الحصول على صفحة HTML بدلاً من JSON - تحقق من الـ routes');
        }

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('خطأ في تحليل الاستجابة: ' + text.substring(0, 100));
        }

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    })
    .then(data => {
        if (data.success) {
            const postElement = document.querySelector(`[data-post-id="${postId}"]`);
            if (postElement) {
                postElement.remove();
            }
            showNotification('تم حذف المنشور بنجاح', 'success');
        } else {
            showNotification(data.message || 'حدث خطأ أثناء حذف المنشور', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ: ' + error.message, 'error');
    });
}

/**
 * تبديل إعجاب المنشور
 */
function toggleLike(postId, type = 'like') {
    const likeBtn = document.querySelector(`[data-post-id="${postId}"].like-btn`);
    if (!likeBtn) return;

    // تأثير بصري فوري
    likeBtn.classList.add('like-animation');
    setTimeout(() => likeBtn.classList.remove('like-animation'), 300);

    fetch(`/social/posts/${postId}/like`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ type: type })
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
            updateLikeButton(postId, data.user_liked, data.likes_count, data.user_like_type);
            updatePostLikesDisplay(postId, data.likes_count, data.reactions_summary);
        } else {
            showNotification(data.message || 'حدث خطأ أثناء الإعجاب', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء الإعجاب: ' + error.message, 'error');
    });
}

/**
 * تحديث زر الإعجاب
 */
function updateLikeButton(postId, userLiked, likesCount, likeType) {
    const likeBtn = document.querySelector(`[data-post-id="${postId}"].like-btn`);
    if (!likeBtn) return;

    if (userLiked) {
        likeBtn.classList.add('liked');
        const reactionName = getReactionName(likeType);
        const span = likeBtn.querySelector('span');
        if (span) span.textContent = reactionName;

        // تغيير لون الأيقونة حسب نوع الإعجاب
        const icon = likeBtn.querySelector('i');
        if (icon) {
            if (likeType === 'love') {
                icon.style.color = '#f33e58';
                icon.className = 'fas fa-heart';
            } else if (likeType === 'haha') {
                icon.style.color = '#ffb74d';
                icon.className = 'fas fa-laugh';
            } else if (likeType === 'wow') {
                icon.style.color = '#ffb74d';
                icon.className = 'fas fa-surprise';
            } else if (likeType === 'sad') {
                icon.style.color = '#ffb74d';
                icon.className = 'fas fa-sad-tear';
            } else if (likeType === 'angry') {
                icon.style.color = '#f4511e';
                icon.className = 'fas fa-angry';
            } else {
                icon.style.color = '#1877f2';
                icon.className = 'fas fa-thumbs-up';
            }
        }
    } else {
        likeBtn.classList.remove('liked');
        const span = likeBtn.querySelector('span');
        if (span) span.textContent = 'إعجاب';
        const icon = likeBtn.querySelector('i');
        if (icon) {
            icon.style.color = '';
            icon.className = 'fas fa-thumbs-up';
        }
    }
}

/**
 * تحديث عرض الإعجابات في المنشور
 */
function updatePostLikesDisplay(postId, likesCount, reactionsSummary) {
    const postCard = document.querySelector(`[data-post-id="${postId}"]`);
    if (!postCard) return;

    const likesInfo = postCard.querySelector('.likes-info');

    if (likesCount > 0) {
        if (!likesInfo) {
            // إنشاء عرض الإعجابات إذا لم يكن موجوداً
            let postStats = postCard.querySelector('.post-stats');
            if (!postStats) {
                postStats = document.createElement('div');
                postStats.className = 'post-stats';
                const postContent = postCard.querySelector('.post-content');
                if (postContent) {
                    postContent.after(postStats);
                }
            }

            const newLikesInfo = document.createElement('div');
            newLikesInfo.className = 'likes-info';
            newLikesInfo.onclick = () => showLikes(postId);
            postStats.appendChild(newLikesInfo);
        }

        // تحديث محتوى الإعجابات
        const currentLikesInfo = postCard.querySelector('.likes-info');
        if (currentLikesInfo) {
            currentLikesInfo.innerHTML = `
                <div class="reactions-summary">
                    ${generateReactionsSummary(reactionsSummary)}
                </div>
                <span class="likes-count">${likesCount}</span>
            `;
        }
    } else {
        // إزالة عرض الإعجابات إذا لم يعد هناك إعجابات
        if (likesInfo) {
            likesInfo.remove();
        }
    }
}

/**
 * إنشاء ملخص الإعجابات
 */
function generateReactionsSummary(reactionsSummary) {
    if (!reactionsSummary || Object.keys(reactionsSummary).length === 0) {
        return '<span class="reaction-summary-icon">👍</span>';
    }

    let html = '';
    const maxVisible = 3;
    let count = 0;

    for (const [type, amount] of Object.entries(reactionsSummary)) {
        if (count >= maxVisible) break;

        const emoji = getReactionEmoji(type);
        html += `<span class="reaction-summary-icon ${type}">${emoji}</span>`;
        count++;
    }

    return html;
}

/**
 * الحصول على إيموجي الإعجاب
 */
function getReactionEmoji(type) {
    const emojis = {
        'like': '👍',
        'love': '❤️',
        'haha': '😂',
        'wow': '😮',
        'sad': '😢',
        'angry': '😡'
    };
    return emojis[type] || '👍';
}

/**
 * إرسال تعليق
 */
function submitComment(event, postId) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const input = form.querySelector('.comment-input');

    if (!input || !input.value.trim()) return;

    fetch(`/social/posts/${postId}/comments`, {
        method: 'POST',
        body: formData,
        headers: {
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
            addCommentToDOM(postId, data.comment);
            updateCommentsCount(postId, data.comments_count);
            form.reset();

            // إخفاء معاينة الصورة إن وجدت
            const imagePreview = document.getElementById(`commentImagePreview${postId}`);
            if (imagePreview) {
                imagePreview.style.display = 'none';
                imagePreview.innerHTML = '';
            }
        } else {
            showNotification(data.message || 'حدث خطأ أثناء إضافة التعليق', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ في الاتصال: ' + error.message, 'error');
    });
}

/**
 * إضافة تعليق إلى DOM
 */
function addCommentToDOM(postId, comment) {
    const commentsList = document.getElementById(`commentsList${postId}`);
    if (!commentsList) return;

    const commentHTML = `
        <div class="comment" data-comment-id="${comment.id}">
            <div class="comment-content">
                <img src="${comment.user.profile_photo_url}" alt="${comment.user.name}" class="comment-avatar">
                <div class="comment-bubble">
                    <div class="comment-header">
                        <div class="comment-user-name">${comment.user.name}</div>
                        <div class="comment-time">الآن</div>
                    </div>
                    <div class="comment-text">${comment.content}</div>
                    ${comment.image ? `<div class="comment-media"><img src="${comment.image}" alt="صورة التعليق" class="comment-image"></div>` : ''}
                </div>
            </div>
            <div class="comment-actions-bar">
                <button class="comment-action-btn" onclick="toggleCommentLike(${comment.id})">
                    <i class="fas fa-thumbs-up"></i>
                    <span>إعجاب</span>
                </button>
                <button class="comment-action-btn" onclick="showReplyInput(${comment.id})">
                    <i class="fas fa-reply"></i>
                    <span>رد</span>
                </button>
            </div>
        </div>
    `;

    commentsList.insertAdjacentHTML('afterbegin', commentHTML);

    // تهيئة قوائم الإيموجيات للتعليق الجديد
    const newComment = commentsList.querySelector('.comment[data-comment-id="' + comment.id + '"]');
    if (newComment) {
        const likeBtn = newComment.querySelector('.comment-action-btn');
        if (likeBtn) {
            createReactionMenu(likeBtn);

            likeBtn.addEventListener('mouseenter', function() {
                showReactionMenu(this);
            });

            likeBtn.addEventListener('mouseleave', function() {
                hideReactionMenuDelayed(this);
            });
        }
    }
}

/**
 * تحديث عداد التعليقات
 */
function updateCommentsCount(postId, count) {
    const commentsCountElement = document.querySelector(`[data-post-id="${postId}"] .comments-count`);
    if (commentsCountElement) {
        commentsCountElement.textContent = `${count} تعليق`;
    }
}

/**
 * تبديل إعجاب التعليق
 */
function toggleCommentLike(commentId, type = 'like') {
    fetch(`/social/comments/${commentId}/like`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ type: type })
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
            updateCommentLikeButton(commentId, data.user_liked, data.likes_count, data.user_like_type);
        } else {
            showNotification(data.message || 'حدث خطأ أثناء الإعجاب', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء الإعجاب: ' + error.message, 'error');
    });
}

/**
 * تحديث زر إعجاب التعليق
 */
function updateCommentLikeButton(commentId, userLiked, likesCount, likeType) {
    const commentElement = document.querySelector(`[data-comment-id="${commentId}"], [data-reply-id="${commentId}"]`);
    if (!commentElement) return;

    const likeBtn = commentElement.querySelector('.comment-action-btn, .reply-action-btn');
    if (!likeBtn) return;

    if (userLiked) {
        likeBtn.classList.add('liked');
        const reactionName = getReactionName(likeType);
        const span = likeBtn.querySelector('span');
        if (span) span.textContent = reactionName;
    } else {
        likeBtn.classList.remove('liked');
        const span = likeBtn.querySelector('span');
        if (span) span.textContent = 'إعجاب';
    }

    // تحديث عداد الإعجابات
    let likesCountElement = commentElement.querySelector('.comment-likes-count, .reply-likes-count');
    if (likesCount > 0) {
        if (!likesCountElement) {
            const actionsBar = commentElement.querySelector('.comment-actions-bar, .reply-actions-bar');
            if (actionsBar) {
                const countClass = commentElement.classList.contains('reply') ? 'reply-likes-count' : 'comment-likes-count';
                actionsBar.insertAdjacentHTML('beforeend', `
                    <button class="${countClass}" onclick="showCommentLikes(${commentId})">
                        <span class="reaction-icon">👍</span>
                        ${likesCount}
                    </button>
                `);
            }
        } else {
            likesCountElement.innerHTML = `<span class="reaction-icon">👍</span> ${likesCount}`;
        }
    } else if (likesCountElement) {
        likesCountElement.remove();
    }
}

/**
 * حذف تعليق
 */
function deleteComment(commentId) {
    if (!confirm('هل أنت متأكد من حذف هذا التعليق؟')) return;

    fetch(`/social/comments/${commentId}`, {
        method: 'DELETE',
        headers: {
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
            const commentElement = document.querySelector(`[data-comment-id="${commentId}"], [data-reply-id="${commentId}"]`);
            if (commentElement) {
                commentElement.remove();
            }
            showNotification('تم حذف التعليق بنجاح', 'success');
        } else {
            showNotification(data.message || 'حدث خطأ أثناء حذف التعليق', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ في الاتصال: ' + error.message, 'error');
    });
}

/**
 * التركيز على input التعليق
 */
function focusCommentInput(postId) {
    const input = document.getElementById(`commentInput${postId}`);
    if (input) {
        input.focus();
    }
}

/**
 * إظهار input الرد
 */
function showReplyInput(commentId) {
    const replyInput = document.getElementById(`replyInput${commentId}`);
    if (replyInput) {
        replyInput.style.display = replyInput.style.display === 'none' ? 'flex' : 'none';

        if (replyInput.style.display === 'flex') {
            const input = replyInput.querySelector('.reply-input');
            if (input) {
                input.focus();
            }
        }
    }
}

/**
 * إرسال رد
 */
function submitReply(event, postId, parentId) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    formData.append('parent_id', parentId);

    const input = form.querySelector('.reply-input');
    if (!input || !input.value.trim()) return;

    fetch(`/social/posts/${postId}/comments`, {
        method: 'POST',
        body: formData,
        headers: {
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
            addReplyToDOM(parentId, data.comment);
            form.reset();
            const replyInput = document.getElementById(`replyInput${parentId}`);
            if (replyInput) {
                replyInput.style.display = 'none';
            }
        } else {
            showNotification(data.message || 'حدث خطأ أثناء إضافة الرد', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ في الاتصال: ' + error.message, 'error');
    });
}

/**
 * إضافة رد إلى DOM
 */
function addReplyToDOM(parentId, reply) {
    const parentComment = document.querySelector(`[data-comment-id="${parentId}"]`);
    if (!parentComment) return;

    let repliesContainer = parentComment.querySelector('.replies-container');

    if (!repliesContainer) {
        repliesContainer = document.createElement('div');
        repliesContainer.className = 'replies-container';
        parentComment.appendChild(repliesContainer);
    }

    const replyHTML = `
        <div class="reply" data-reply-id="${reply.id}">
            <div class="reply-content">
                <img src="${reply.user.profile_photo_url}" alt="${reply.user.name}" class="reply-avatar-small">
                <div class="reply-bubble">
                    <div class="reply-header">
                        <div class="reply-user-name">${reply.user.name}</div>
                        <div class="reply-time">الآن</div>
                    </div>
                    <div class="reply-text">${reply.content}</div>
                </div>
            </div>
            <div class="reply-actions-bar">
                <button class="reply-action-btn" onclick="toggleCommentLike(${reply.id})">
                    <i class="fas fa-thumbs-up"></i>
                    <span>إعجاب</span>
                </button>
            </div>
        </div>
    `;

    repliesContainer.insertAdjacentHTML('afterbegin', replyHTML);

    // تهيئة قوائم الإيموجيات للرد الجديد
    const newReply = repliesContainer.querySelector('.reply[data-reply-id="' + reply.id + '"]');
    if (newReply) {
        const likeBtn = newReply.querySelector('.reply-action-btn');
        if (likeBtn) {
            createReactionMenu(likeBtn);

            likeBtn.addEventListener('mouseenter', function() {
                showReactionMenu(this);
            });

            likeBtn.addEventListener('mouseleave', function() {
                hideReactionMenuDelayed(this);
            });
        }
    }
}

/**
 * معاينة صورة التعليق
 */
function previewCommentImage(input, postId) {
    const file = input.files[0];
    if (!file) return;

    const preview = document.getElementById(`commentImagePreview${postId}`);
    if (!preview) return;

    preview.innerHTML = '';

    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.style.cssText = 'max-width: 100px; max-height: 100px; border-radius: 4px; margin-top: 8px;';

    const removeBtn = document.createElement('button');
    removeBtn.innerHTML = '&times;';
    removeBtn.style.cssText = 'margin-right: 8px; background: #f0f2f5; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;';
    removeBtn.addEventListener('click', function() {
        input.value = '';
        preview.style.display = 'none';
        preview.innerHTML = '';
    });

    preview.appendChild(removeBtn);
    preview.appendChild(img);
    preview.style.display = 'block';
}

/**
 * فتح نافذة الصورة
 */
function openImageModal(imageUrl) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    `;

    const img = document.createElement('img');
    img.src = imageUrl;
    img.style.cssText = 'max-width: 90%; max-height: 90%; object-fit: contain;';

    modal.appendChild(img);
    document.body.appendChild(modal);

    modal.addEventListener('click', function() {
        document.body.removeChild(modal);
    });
}

/**
 * فتح منتقي الإيموجي
 */
function openEmojiPicker(postId, event) {
    // منع إرسال النموذج
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // إزالة أي منتقي إيموجي موجود
    closeAllEmojiPickers();

    // إنشاء منتقي الإيموجي
    const emojiPicker = createEmojiPicker(postId);

    // العثور على الزر الذي تم النقر عليه
    const emojiBtn = event ? event.target.closest('.emoji-btn') : null;

    // إضافة المنتقي إلى body
    document.body.appendChild(emojiPicker);

    // حساب الموضع
    if (emojiBtn) {
        const btnRect = emojiBtn.getBoundingClientRect();
        const pickerWidth = 320;
        const pickerHeight = 400;

        // تحديد الموضع بحيث يظهر فوق الزر
        let top = btnRect.top - pickerHeight - 10;
        let left = btnRect.left;

        // التأكد من أن المنتقي لا يخرج خارج الشاشة
        if (top < 10) {
            top = btnRect.bottom + 10;
        }

        if (left + pickerWidth > window.innerWidth - 10) {
            left = window.innerWidth - pickerWidth - 10;
        }

        if (left < 10) {
            left = 10;
        }

        emojiPicker.style.top = top + 'px';
        emojiPicker.style.left = left + 'px';
    }

    // إظهار المنتقي مع تأثير
    setTimeout(() => {
        emojiPicker.classList.add('show');
    }, 10);
}

/**
 * إنشاء منتقي الإيموجي
 */
function createEmojiPicker(postId) {
    const emojiPicker = document.createElement('div');
    emojiPicker.className = 'emoji-picker';
    emojiPicker.id = `emojiPicker${postId}`;

    // فئات الإيموجي
    const emojiCategories = {
        'الوجوه والمشاعر': [
            '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙',
            '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥',
            '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '😎', '🤓', '🧐'
        ],
        'الأيدي والإيماءات': [
            '👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌', '🤏', '✌', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝', '👍', '👎',
            '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍', '💅', '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻'
        ],
        'الحيوانات والطبيعة': [
            '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔',
            '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞', '🐜', '🦟'
        ],
        'الطعام والشراب': [
            '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦',
            '🥬', '🥒', '🌶', '🫑', '🌽', '🥕', '🫒', '🧄', '🧅', '🥔', '🍠', '🥐', '🥖', '🍞', '🥨', '🥯', '🧀', '🥚', '🍳', '🧈'
        ],
        'الأنشطة والرياضة': [
            '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳',
            '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸', '🥌', '🎿', '⛷', '🏂', '🪂', '🏋', '🤼', '🤸', '⛹'
        ],
        'السفر والأماكن': [
            '🚗', '🚙', '🚐', '🚛', '🚜', '🏎', '🚓', '🚑', '🚒', '🚐', '🛻', '🚚', '🚨', '🚔', '🚍', '🚘', '🚖', '🚡', '🚠', '🚟',
            '🚃', '🚋', '🚞', '🚝', '🚄', '🚅', '🚈', '🚂', '🚆', '🚇', '🚊', '🚉', '✈', '🛫', '🛬', '🛩', '💺', '🛰', '🚀', '🛸'
        ],
        'الأشياء والرموز': [
            '⌚', '📱', '📲', '💻', '⌨', '🖥', '🖨', '🖱', '🖲', '🕹', '🗜', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥',
            '📽', '🎞', '📞', '☎', '📟', '📠', '📺', '📻', '🎙', '🎚', '🎛', '🧭', '⏱', '⏲', '⏰', '🕰', '⌛', '⏳', '📡', '🔋'
        ],
        'القلوب والرموز': [
            '❤', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮',
            '✝', '☪', '🕉', '☸', '✡', '🔯', '🕎', '☯', '☦', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐'
        ]
    };

    // إنشاء العنوان
    const header = document.createElement('div');
    header.className = 'emoji-picker-header';
    header.innerHTML = '<span>اختر إيموجي</span><button class="emoji-close-btn" onclick="closeEmojiPicker(' + postId + ')">&times;</button>';
    emojiPicker.appendChild(header);

    // إنشاء فئات الإيموجي
    const categories = document.createElement('div');
    categories.className = 'emoji-categories';

    Object.entries(emojiCategories).forEach(([categoryName, emojis]) => {
        const category = document.createElement('div');
        category.className = 'emoji-category';

        const categoryTitle = document.createElement('div');
        categoryTitle.className = 'emoji-category-title';
        categoryTitle.textContent = categoryName;
        category.appendChild(categoryTitle);

        const emojiGrid = document.createElement('div');
        emojiGrid.className = 'emoji-grid';

        emojis.forEach(emoji => {
            const emojiBtn = document.createElement('button');
            emojiBtn.className = 'emoji-btn-item';
            emojiBtn.textContent = emoji;
            emojiBtn.title = emoji;

            emojiBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                insertEmojiInInput(emoji, postId);
            });

            emojiGrid.appendChild(emojiBtn);
        });

        category.appendChild(emojiGrid);
        categories.appendChild(category);
    });

    emojiPicker.appendChild(categories);

    return emojiPicker;
}

/**
 * إدراج الإيموجي في الـ input
 */
function insertEmojiInInput(emoji, postId) {
    const commentInput = document.getElementById(`commentInput${postId}`);
    if (commentInput) {
        const cursorPos = commentInput.selectionStart;
        const textBefore = commentInput.value.substring(0, cursorPos);
        const textAfter = commentInput.value.substring(commentInput.selectionEnd, commentInput.value.length);

        commentInput.value = textBefore + emoji + textAfter;
        commentInput.focus();

        // وضع المؤشر بعد الإيموجي
        const newCursorPos = cursorPos + emoji.length;
        commentInput.setSelectionRange(newCursorPos, newCursorPos);
    }

    // إغلاق منتقي الإيموجي
    closeEmojiPicker(postId);
}

/**
 * إغلاق منتقي إيموجي محدد
 */
function closeEmojiPicker(postId) {
    const emojiPicker = document.getElementById(`emojiPicker${postId}`);
    if (emojiPicker) {
        emojiPicker.classList.add('hide');
        setTimeout(() => {
            if (emojiPicker.parentNode) {
                emojiPicker.parentNode.removeChild(emojiPicker);
            }
        }, 200);
    }
}

/**
 * إغلاق جميع منتقيات الإيموجي
 */
function closeAllEmojiPickers() {
    const pickers = document.querySelectorAll('.emoji-picker');
    pickers.forEach(picker => {
        picker.classList.add('hide');
        setTimeout(() => {
            if (picker.parentNode) {
                picker.parentNode.removeChild(picker);
            }
        }, 200);
    });

    // إغلاق منتقي المنشور أيضاً
    closePostEmojiPicker();
}

// إغلاق منتقي الإيموجي عند النقر خارجه
document.addEventListener('click', function(event) {
    if (!event.target.closest('.emoji-picker') && !event.target.closest('.emoji-btn')) {
        closeAllEmojiPickers();
    }
});

/**
 * فتح منتقي الإيموجي للمنشور الجديد
 */
function openPostEmojiPicker(event) {
    // منع إرسال النموذج
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // إزالة أي منتقي إيموجي موجود
    closeAllEmojiPickers();

    // إنشاء منتقي الإيموجي
    const emojiPicker = createPostEmojiPicker();

    // العثور على الزر الذي تم النقر عليه
    const emojiBtn = event ? event.target.closest('.media-btn.emoji-btn') : null;

    // إضافة المنتقي إلى body
    document.body.appendChild(emojiPicker);

    // حساب الموضع
    if (emojiBtn) {
        const btnRect = emojiBtn.getBoundingClientRect();
        const pickerWidth = 320;
        const pickerHeight = 300;

        // تحديد الموضع بحيث يظهر فوق الزر
        let top = btnRect.top - pickerHeight - 10;
        let left = btnRect.left;

        // التأكد من أن المنتقي لا يخرج خارج الشاشة
        if (top < 10) {
            top = btnRect.bottom + 10;
        }

        if (left + pickerWidth > window.innerWidth - 10) {
            left = window.innerWidth - pickerWidth - 10;
        }

        if (left < 10) {
            left = 10;
        }

        emojiPicker.style.top = top + 'px';
        emojiPicker.style.left = left + 'px';
    }

    // إظهار المنتقي مع تأثير
    setTimeout(() => {
        emojiPicker.classList.add('show');
    }, 10);
}

/**
 * إنشاء منتقي الإيموجي للمنشور
 */
function createPostEmojiPicker() {
    const emojiPicker = document.createElement('div');
    emojiPicker.className = 'emoji-picker post-emoji-picker';
    emojiPicker.id = 'postEmojiPicker';

    // فئات الإيموجي
    const emojiCategories = {
        'الوجوه والمشاعر': [
            '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙',
            '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥',
            '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '😎', '🤓', '🧐'
        ],
        'الأيدي والإيماءات': [
            '👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌', '🤏', '✌', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝', '👍', '👎',
            '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍', '💅', '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻'
        ],
        'الحيوانات والطبيعة': [
            '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔',
            '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞', '🐜', '🦟'
        ],
        'الطعام والشراب': [
            '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦',
            '🥬', '🥒', '🌶', '🫑', '🌽', '🥕', '🫒', '🧄', '🧅', '🥔', '🍠', '🥐', '🥖', '🍞', '🥨', '🥯', '🧀', '🥚', '🍳', '🧈'
        ],
        'الأنشطة والرياضة': [
            '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳',
            '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸', '🥌', '🎿', '⛷', '🏂', '🪂', '🏋', '🤼', '🤸', '⛹'
        ],
        'السفر والأماكن': [
            '🚗', '🚙', '🚐', '🚛', '🚜', '🏎', '🚓', '🚑', '🚒', '🚐', '🛻', '🚚', '🚨', '🚔', '🚍', '🚘', '🚖', '🚡', '🚠', '🚟',
            '🚃', '🚋', '🚞', '🚝', '🚄', '🚅', '🚈', '🚂', '🚆', '🚇', '🚊', '🚉', '✈', '🛫', '🛬', '🛩', '💺', '🛰', '🚀', '🛸'
        ],
        'الأشياء والرموز': [
            '⌚', '📱', '📲', '💻', '⌨', '🖥', '🖨', '🖱', '🖲', '🕹', '🗜', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥',
            '📽', '🎞', '📞', '☎', '📟', '📠', '📺', '📻', '🎙', '🎚', '🎛', '🧭', '⏱', '⏲', '⏰', '🕰', '⌛', '⏳', '📡', '🔋'
        ],
        'القلوب والرموز': [
            '❤', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮',
            '✝', '☪', '🕉', '☸', '✡', '🔯', '🕎', '☯', '☦', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐'
        ]
    };

    // إنشاء العنوان
    const header = document.createElement('div');
    header.className = 'emoji-picker-header';
    header.innerHTML = '<span>اختر إيموجي</span><button class="emoji-close-btn" onclick="closePostEmojiPicker()">&times;</button>';
    emojiPicker.appendChild(header);

    // إنشاء فئات الإيموجي
    const categories = document.createElement('div');
    categories.className = 'emoji-categories';

    Object.entries(emojiCategories).forEach(([categoryName, emojis]) => {
        const category = document.createElement('div');
        category.className = 'emoji-category';

        const categoryTitle = document.createElement('div');
        categoryTitle.className = 'emoji-category-title';
        categoryTitle.textContent = categoryName;
        category.appendChild(categoryTitle);

        const emojiGrid = document.createElement('div');
        emojiGrid.className = 'emoji-grid';

        emojis.forEach(emoji => {
            const emojiBtn = document.createElement('button');
            emojiBtn.className = 'emoji-btn-item';
            emojiBtn.textContent = emoji;
            emojiBtn.title = emoji;

            emojiBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                insertEmojiInPostContent(emoji);
            });

            emojiGrid.appendChild(emojiBtn);
        });

        category.appendChild(emojiGrid);
        categories.appendChild(category);
    });

    emojiPicker.appendChild(categories);

    return emojiPicker;
}

/**
 * إدراج الإيموجي في محتوى المنشور
 */
function insertEmojiInPostContent(emoji) {
    const postContent = document.getElementById('postContent');
    if (postContent) {
        const cursorPos = postContent.selectionStart;
        const textBefore = postContent.value.substring(0, cursorPos);
        const textAfter = postContent.value.substring(postContent.selectionEnd, postContent.value.length);

        postContent.value = textBefore + emoji + textAfter;
        postContent.focus();

        // وضع المؤشر بعد الإيموجي
        const newCursorPos = cursorPos + emoji.length;
        postContent.setSelectionRange(newCursorPos, newCursorPos);

        // تحديث حجم textarea تلقائياً
        autoResizeTextarea({ target: postContent });
    }

    // إغلاق منتقي الإيموجي
    closePostEmojiPicker();
}

/**
 * إغلاق منتقي إيموجي المنشور
 */
function closePostEmojiPicker() {
    const emojiPicker = document.getElementById('postEmojiPicker');
    if (emojiPicker) {
        emojiPicker.classList.add('hide');
        setTimeout(() => {
            if (emojiPicker.parentNode) {
                emojiPicker.parentNode.removeChild(emojiPicker);
            }
        }, 200);
    }
}

/**
 * فتح منتقي الإيموجي للرد
 */
function openReplyEmojiPicker(commentId, event) {
    // منع إرسال النموذج
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // إزالة أي منتقي إيموجي موجود
    closeAllEmojiPickers();

    // إنشاء منتقي الإيموجي
    const emojiPicker = createReplyEmojiPicker(commentId);

    // العثور على الزر الذي تم النقر عليه
    const emojiBtn = event ? event.target.closest('.reply-emoji-btn') : null;

    // إضافة المنتقي إلى body
    document.body.appendChild(emojiPicker);

    // حساب الموضع
    if (emojiBtn) {
        const btnRect = emojiBtn.getBoundingClientRect();
        const pickerWidth = 300;
        const pickerHeight = 200;

        // تحديد الموضع بحيث يظهر فوق الزر
        let top = btnRect.top - pickerHeight - 10;
        let left = btnRect.left;

        // التأكد من أن المنتقي لا يخرج خارج الشاشة
        if (top < 10) {
            top = btnRect.bottom + 10;
        }

        if (left + pickerWidth > window.innerWidth - 10) {
            left = window.innerWidth - pickerWidth - 10;
        }

        if (left < 10) {
            left = 10;
        }

        emojiPicker.style.top = top + 'px';
        emojiPicker.style.left = left + 'px';
    }

    // إظهار المنتقي مع تأثير
    setTimeout(() => {
        emojiPicker.classList.add('show');
    }, 10);
}

/**
 * إنشاء منتقي الإيموجي للرد
 */
function createReplyEmojiPicker(commentId) {
    const emojiPicker = document.createElement('div');
    emojiPicker.className = 'emoji-picker reply-emoji-picker';
    emojiPicker.id = `replyEmojiPicker${commentId}`;

    // فئات الإيموجي (نسخة مختصرة للردود)
    const emojiCategories = {
        'الأكثر استخداماً': [
            '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '😉', '😊', '😍', '🥰', '😘', '😋', '😛', '😜', '🤪', '😝', '🤑',
            '👍', '👎', '👌', '✌', '🤞', '🤟', '🤘', '🤙', '👏', '🙌', '❤', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔'
        ],
        'الوجوه': [
            '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😔', '😪', '🤤', '😴', '😷', '🤒'
        ]
    };

    // إنشاء العنوان
    const header = document.createElement('div');
    header.className = 'emoji-picker-header';
    header.innerHTML = '<span>اختر إيموجي</span><button class="emoji-close-btn" onclick="closeReplyEmojiPicker(' + commentId + ')">&times;</button>';
    emojiPicker.appendChild(header);

    // إنشاء فئات الإيموجي
    const categories = document.createElement('div');
    categories.className = 'emoji-categories';

    Object.entries(emojiCategories).forEach(([categoryName, emojis]) => {
        const category = document.createElement('div');
        category.className = 'emoji-category';

        const categoryTitle = document.createElement('div');
        categoryTitle.className = 'emoji-category-title';
        categoryTitle.textContent = categoryName;
        category.appendChild(categoryTitle);

        const emojiGrid = document.createElement('div');
        emojiGrid.className = 'emoji-grid';

        emojis.forEach(emoji => {
            const emojiBtn = document.createElement('button');
            emojiBtn.className = 'emoji-btn-item';
            emojiBtn.textContent = emoji;
            emojiBtn.title = emoji;

            emojiBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                insertEmojiInReply(emoji, commentId);
            });

            emojiGrid.appendChild(emojiBtn);
        });

        category.appendChild(emojiGrid);
        categories.appendChild(category);
    });

    emojiPicker.appendChild(categories);

    return emojiPicker;
}

/**
 * إدراج الإيموجي في الرد
 */
function insertEmojiInReply(emoji, commentId) {
    const replyInput = document.querySelector(`#replyInput${commentId} .reply-input`);
    if (replyInput) {
        const cursorPos = replyInput.selectionStart;
        const textBefore = replyInput.value.substring(0, cursorPos);
        const textAfter = replyInput.value.substring(replyInput.selectionEnd, replyInput.value.length);

        replyInput.value = textBefore + emoji + textAfter;
        replyInput.focus();

        // وضع المؤشر بعد الإيموجي
        const newCursorPos = cursorPos + emoji.length;
        replyInput.setSelectionRange(newCursorPos, newCursorPos);
    }

    // إغلاق منتقي الإيموجي
    closeReplyEmojiPicker(commentId);
}

/**
 * إغلاق منتقي إيموجي الرد
 */
function closeReplyEmojiPicker(commentId) {
    const emojiPicker = document.getElementById(`replyEmojiPicker${commentId}`);
    if (emojiPicker) {
        emojiPicker.classList.add('hide');
        setTimeout(() => {
            if (emojiPicker.parentNode) {
                emojiPicker.parentNode.removeChild(emojiPicker);
            }
        }, 200);
    }
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
 * مشاركة منشور
 */
function sharePost(postId) {
    // يمكن تطوير هذه الوظيفة لاحقاً
    showNotification('ميزة المشاركة قيد التطوير', 'info');
}

/**
 * إظهار الإعجابات
 */
function showLikes(postId) {
    // يمكن تطوير هذه الوظيفة لاحقاً
    showNotification('ميزة عرض الإعجابات قيد التطوير', 'info');
}

/**
 * إظهار إعجابات التعليق
 */
function showCommentLikes(commentId) {
    // يمكن تطوير هذه الوظيفة لاحقاً
    showNotification('ميزة عرض الإعجابات قيد التطوير', 'info');
}

/**
 * تحميل المزيد من التعليقات
 */
function loadMoreComments(postId) {
    // يمكن تطوير هذه الوظيفة لاحقاً
    showNotification('ميزة تحميل المزيد قيد التطوير', 'info');
}
