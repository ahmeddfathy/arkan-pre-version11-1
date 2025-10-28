<div class="comment" data-comment-id="{{ $comment->id }}">
    <div class="comment-content">
        <a href="{{ route('social.profile.show', $comment->user) }}" class="user-link">
            <img src="{{ $comment->user->profile_photo_url }}" alt="{{ $comment->user->name }}" class="comment-avatar">
        </a>
        <div class="comment-bubble">
            <div class="comment-header">
                <div class="comment-user-name">
                    <a href="{{ route('social.profile.show', $comment->user) }}" class="user-name-link">
                        {{ $comment->user->name }}
                    </a>
                </div>
                <div class="comment-time">{{ $comment->time_ago }}</div>
            </div>
            <div class="comment-text">{!! $comment->formatted_content !!}</div>

            @if($comment->image)
            <div class="comment-media">
                <img src="{{ $comment->getImageUrl() }}" alt="صورة التعليق" class="comment-image" onclick="openImageModal('{{ $comment->getImageUrl() }}')">
            </div>
            @endif
        </div>

        @if(Auth::id() === $comment->user_id || Auth::user()->hasRole('hr'))
        <div class="comment-options">
            <button class="comment-options-btn" onclick="toggleCommentOptions({{ $comment->id }})">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <div class="comment-options-menu" id="commentOptions{{ $comment->id }}" style="display: none;">
                <button onclick="deleteComment({{ $comment->id }})" class="option-item text-red-600">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- Comment Actions -->
    <div class="comment-actions-bar">
        <button class="comment-action-btn {{ $comment->isLikedBy() ? 'liked' : '' }}" onclick="toggleCommentLike({{ $comment->id }})">
            <i class="fas fa-thumbs-up"></i>
            <span>{{ $comment->isLikedBy() ? 'معجب' : 'إعجاب' }}</span>
        </button>

        <button class="comment-action-btn" onclick="showReplyInput({{ $comment->id }})">
            <i class="fas fa-reply"></i>
            <span>رد</span>
        </button>

        @if($comment->likes_count > 0)
        <button class="comment-likes-count" onclick="showCommentLikes({{ $comment->id }})">
            <span class="reaction-icon">👍</span>
            {{ $comment->likes_count }}
        </button>
        @endif
    </div>

    <!-- Reply Input -->
    <div class="reply-input-container" id="replyInput{{ $comment->id }}" style="display: none;">
        <a href="{{ route('social.profile.show', Auth::user()) }}" class="user-link">
            <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="reply-avatar">
        </a>
        <form class="reply-form" onsubmit="submitReply(event, {{ $post->id }}, {{ $comment->id }})">
            @csrf
            <div class="reply-input-wrapper">
                <input type="text" name="content" class="reply-input" placeholder="اكتب رد...">
                <button type="button" onclick="openReplyEmojiPicker({{ $comment->id }}, event)" class="reply-emoji-btn">
                    <i class="fas fa-smile"></i>
                </button>
            </div>
            <button type="submit" class="reply-submit-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>

    <!-- Replies -->
    @if($comment->replies->count() > 0)
    <div class="replies-container">
        @foreach($comment->replies as $reply)
            @include('social.partials.reply', ['reply' => $reply, 'post' => $post])
        @endforeach
    </div>
    @endif
</div>
