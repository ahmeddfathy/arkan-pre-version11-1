@extends('layouts.app')

@section('title', 'التواصل الاجتماعي')

@section('content')
<div class="social-container">
    <!-- إنشاء منشور جديد -->
    <div class="create-post-card">
        <div class="create-post-header">
            <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="user-avatar">
            <div class="create-post-input" onclick="openCreatePostModal()">
                <span>ما الذي تريد مشاركته اليوم، {{ Auth::user()->name }}؟</span>
            </div>
        </div>
        <div class="create-post-actions">
            <button onclick="openCreatePostModal('image')" class="action-btn">
                <i class="fas fa-image text-green-500"></i>
                صورة
            </button>
            <button onclick="openCreatePostModal('video')" class="action-btn">
                <i class="fas fa-video text-red-500"></i>
                فيديو
            </button>
            <button onclick="openCreatePostModal('feeling')" class="action-btn">
                <i class="fas fa-smile text-yellow-500"></i>
                شعور
            </button>
        </div>
    </div>

    <!-- قائمة المنشورات -->
    <div class="posts-container" id="postsContainer">
        @forelse($posts as $post)
            @include('social.partials.post', ['post' => $post])
        @empty
            <div class="no-posts">
                <img src="{{ asset('img/empty-data.svg') }}" alt="لا توجد منشورات">
                <h3>لا توجد منشورات بعد</h3>
                <p>كن أول من ينشر شيئاً!</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($posts->hasPages())
        <div class="pagination-container">
            {{ $posts->links() }}
        </div>
    @endif
</div>

<!-- Modal إنشاء منشور -->
<div id="createPostModal" class="modal modal-hidden">
    <div class="modal-content create-post-modal">
        <div class="modal-header">
            <h3>إنشاء منشور</h3>
            <button class="close-btn" onclick="closeCreatePostModal()">&times;</button>
        </div>
        <form id="createPostForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="user-info">
                    <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="user-avatar-small">
                    <div>
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <select name="privacy" class="privacy-select">
                            <option value="public">🌍 عام</option>
                            <option value="friends">👥 الأصدقاء</option>
                            <option value="private">🔒 خاص</option>
                        </select>
                    </div>
                </div>

                <textarea name="content" id="postContent" class="post-textarea" placeholder="ما الذي تريد مشاركته؟" required></textarea>

                <!-- Preview للصور والفيديو -->
                <div id="mediaPreview" class="media-preview" style="display: none;"></div>

                <!-- أزرار الوسائط -->
                <div class="media-buttons">
                    <input type="file" id="imageInput" name="image" accept="image/*" style="display: none;" onchange="previewMedia(this, 'image')">
                    <input type="file" id="videoInput" name="video" accept="video/*" style="display: none;" onchange="previewMedia(this, 'video')">

                    <button type="button" onclick="document.getElementById('imageInput').click()" class="media-btn">
                        <i class="fas fa-image"></i> إضافة صورة
                    </button>
                    <button type="button" onclick="document.getElementById('videoInput').click()" class="media-btn">
                        <i class="fas fa-video"></i> إضافة فيديو
                    </button>
        
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="submit-btn" id="submitBtn">نشر</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/social-facebook.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/social-facebook.js') }}"></script>
@endpush
