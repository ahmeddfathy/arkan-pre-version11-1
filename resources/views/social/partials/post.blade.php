<div class="post-card" data-post-id="{{ $post->id }}">
    <!-- Post Header -->
    <div class="post-header">
        <div class="post-user-info">
            <a href="{{ route('social.profile.show', $post->user) }}" class="user-link">
                <img src="{{ $post->user->profile_photo_url }}" alt="{{ $post->user->name }}" class="post-avatar">
            </a>
            <div class="post-user-details">
                <div class="post-user-name">
                    <a href="{{ route('social.profile.show', $post->user) }}" class="user-name-link">
                        {{ $post->user->name }}
                    </a>
                </div>
                <div class="post-time">
                    <span>{{ $post->time_ago }}</span>
                    <span class="privacy-icon">
                        @if($post->privacy === 'public')
                            <i class="fas fa-globe-americas" title="عام"></i>
                        @elseif($post->privacy === 'friends')
                            <i class="fas fa-user-friends" title="الأصدقاء"></i>
                        @else
                            <i class="fas fa-lock" title="خاص"></i>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        @if(Auth::id() === $post->user_id || Auth::user()->hasRole('hr'))
        <div class="post-options">
            <button class="options-btn" onclick="togglePostOptions({{ $post->id }})">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <div class="post-options-menu" id="postOptions{{ $post->id }}" style="display: none;">
                @if(Auth::id() === $post->user_id)
                <button onclick="editPost({{ $post->id }})" class="option-item">
                    <i class="fas fa-edit"></i> تعديل
                </button>
                @endif
                <button onclick="deletePost({{ $post->id }})" class="option-item text-red-600">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- Post Content -->
    <div class="post-content">
        <div class="post-text" id="postText{{ $post->id }}">{!! $post->formatted_content !!}</div>

        @if($post->image)
        <div class="post-media">
            <img src="{{ $post->getImageUrl() }}" alt="صورة المنشور" class="post-image" onclick="openImageModal('{{ $post->getImageUrl() }}')">
        </div>
        @endif

        @if($post->video)
        <div class="post-media">
            <video controls class="post-video">
                <source src="{{ $post->getVideoUrl() }}" type="video/mp4">
                متصفحك لا يدعم تشغيل الفيديو.
            </video>
        </div>
        @endif
    </div>

    <!-- Post Stats -->
    @if($post->likes_count > 0 || $post->comments_count > 0)
    <div class="post-stats">
        @if($post->likes_count > 0)
        <div class="likes-info" onclick="showLikes({{ $post->id }})">
            <div class="reactions-summary">
                <span class="reaction-summary-icon">👍</span>
                <span class="reaction-summary-icon love">❤️</span>
                <span class="reaction-summary-icon haha">😂</span>
            </div>
            <span class="likes-count">{{ $post->likes_count }}</span>
        </div>
        @endif

        <div class="comments-shares-info">
            @if($post->comments_count > 0)
            <span class="comments-count" onclick="toggleComments({{ $post->id }})">
                {{ $post->comments_count }} تعليق
            </span>
            @endif
            @if($post->shares_count > 0)
            <span class="shares-count">{{ $post->shares_count }} مشاركة</span>
            @endif
        </div>
    </div>
    @endif

    <!-- Post Actions -->
    <div class="post-actions">
        <button class="action-button like-btn {{ $post->isLikedBy() ? 'liked' : '' }}"
                onclick="toggleLike({{ $post->id }})"
                data-post-id="{{ $post->id }}">
            <i class="fas fa-thumbs-up"></i>
            <span>{{ $post->isLikedBy() ? 'معجب' : 'إعجاب' }}</span>
        </button>

        <button class="action-button comment-btn" onclick="focusCommentInput({{ $post->id }})">
            <i class="fas fa-comment"></i>
            <span>تعليق</span>
        </button>

        <button class="action-button share-btn" onclick="sharePost({{ $post->id }})">
            <i class="fas fa-share"></i>
            <span>مشاركة</span>
        </button>
    </div>

    <!-- Comments Section -->
    <div class="comments-section" id="commentsSection{{ $post->id }}">
        <!-- Write Comment -->
        <div class="write-comment">
            <a href="{{ route('social.profile.show', Auth::user()) }}" class="user-link">
                <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="comment-avatar">
            </a>
            <form class="comment-form" onsubmit="submitComment(event, {{ $post->id }})">
                @csrf
                <div class="comment-input-container">
                    <input type="text"
                           name="content"
                           class="comment-input"
                           placeholder="اكتب تعليق..."
                           id="commentInput{{ $post->id }}">
                    <div class="comment-actions">
                        <input type="file" id="commentImage{{ $post->id }}" name="image" accept="image/*" style="display: none;" onchange="previewCommentImage(this, {{ $post->id }})">
                        <button type="button" onclick="document.getElementById('commentImage{{ $post->id }}').click()" class="emoji-btn">
                            <i class="fas fa-image"></i>
                        </button>
                        <button type="button" class="emoji-btn" onclick="openEmojiPicker({{ $post->id }}, event)">
                            <i class="fas fa-smile"></i>
                        </button>
                    </div>
                </div>
                <div id="commentImagePreview{{ $post->id }}" class="comment-image-preview" style="display: none;"></div>
            </form>
        </div>

        <!-- Comments List -->
        <div class="comments-list" id="commentsList{{ $post->id }}">
            @foreach($post->comments as $comment)
                @include('social.partials.comment', ['comment' => $comment, 'post' => $post])
            @endforeach
        </div>

        @if($post->comments_count > 3)
        <div class="load-more-comments">
            <button onclick="loadMoreComments({{ $post->id }})" class="load-more-btn">
                عرض المزيد من التعليقات
            </button>
        </div>
        @endif
    </div>
</div>

<!-- Edit Post Modal -->
<div id="editPostModal{{ $post->id }}" class="modal modal-hidden">
    <div class="modal-content create-post-modal">
        <div class="modal-header">
            <h3>تعديل المنشور</h3>
            <button class="close-btn" onclick="closeEditPostModal({{ $post->id }})">&times;</button>
        </div>
        <form id="editPostForm{{ $post->id }}" onsubmit="updatePost(event, {{ $post->id }})">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="user-info">
                    <img src="{{ $post->user->profile_photo_url }}" alt="{{ $post->user->name }}" class="user-avatar-small">
                    <div>
                        <div class="user-name">{{ $post->user->name }}</div>
                        <select name="privacy" class="privacy-select">
                            <option value="public" {{ $post->privacy === 'public' ? 'selected' : '' }}>🌍 عام</option>
                            <option value="friends" {{ $post->privacy === 'friends' ? 'selected' : '' }}>👥 الأصدقاء</option>
                            <option value="private" {{ $post->privacy === 'private' ? 'selected' : '' }}>🔒 خاص</option>
                        </select>
                    </div>
                </div>

                <textarea name="content" class="post-textarea" placeholder="ما الذي تريد مشاركته؟" required>{{ $post->content }}</textarea>

                @if($post->image)
                <div class="current-media">
                    <p>الصورة الحالية:</p>
                    <img src="{{ $post->getImageUrl() }}" alt="الصورة الحالية" style="max-width: 100px; max-height: 100px; border-radius: 4px;">
                </div>
                @endif

                @if($post->video)
                <div class="current-media">
                    <p>الفيديو الحالي:</p>
                    <video style="max-width: 100px; max-height: 100px;" controls>
                        <source src="{{ $post->getVideoUrl() }}" type="video/mp4">
                    </video>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="submit" class="submit-btn">حفظ التغييرات</button>
            </div>
        </form>
    </div>
</div>
