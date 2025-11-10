<div class="reply" data-reply-id="{{ $reply->id }}">
    <div class="reply-content">
        @if($reply->user)
        <a href="{{ route('social.profile.show', $reply->user) }}" class="user-link">
            <img src="{{ $reply->user->profile_photo_url }}" alt="{{ $reply->user->name }}" class="reply-avatar-small">
        </a>
        @else
        <div class="user-link">
            <img src="{{ asset('img/default-avatar.png') }}" alt="مستخدم محذوف" class="reply-avatar-small">
        </div>
        @endif
        <div class="reply-bubble">
            <div class="reply-header">
                <div class="reply-user-name">
                    @if($reply->user)
                    <a href="{{ route('social.profile.show', $reply->user) }}" class="user-name-link">
                        {{ $reply->user->name }}
                    </a>
                    @else
                    <span class="user-name-link">مستخدم محذوف</span>
                    @endif
                </div>
                <div class="reply-time">{{ $reply->time_ago }}</div>
            </div>
            <div class="reply-text">{!! $reply->formatted_content !!}</div>

            @if($reply->image)
            <div class="reply-media">
                <img src="{{ $reply->getImageUrl() }}" alt="صورة الرد" class="reply-image" onclick="openImageModal('{{ $reply->getImageUrl() }}')">
            </div>
            @endif
        </div>

        @if($reply->user && (Auth::id() === $reply->user_id || Auth::user()->hasRole('hr')))
        <div class="reply-options">
            <button class="reply-options-btn" onclick="toggleReplyOptions({{ $reply->id }})">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <div class="reply-options-menu" id="replyOptions{{ $reply->id }}" style="display: none;">
                <button onclick="deleteComment({{ $reply->id }})" class="option-item text-red-600">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
        @elseif(!$reply->user && Auth::user()->hasRole('hr'))
        <div class="reply-options">
            <button class="reply-options-btn" onclick="toggleReplyOptions({{ $reply->id }})">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <div class="reply-options-menu" id="replyOptions{{ $reply->id }}" style="display: none;">
                <button onclick="deleteComment({{ $reply->id }})" class="option-item text-red-600">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- Reply Actions -->
    <div class="reply-actions-bar">
        <button class="reply-action-btn {{ $reply->isLikedBy() ? 'liked' : '' }}" onclick="toggleCommentLike({{ $reply->id }})">
            <i class="fas fa-thumbs-up"></i>
            <span>{{ $reply->isLikedBy() ? 'معجب' : 'إعجاب' }}</span>
        </button>

        @if($reply->likes_count > 0)
        <button class="reply-likes-count" onclick="showCommentLikes({{ $reply->id }})">
            <span class="reaction-icon">👍</span>
            {{ $reply->likes_count }}
        </button>
        @endif
    </div>
</div>