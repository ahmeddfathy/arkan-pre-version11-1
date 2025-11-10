<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('بروفايل') }} - {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Profile Header -->
            <div class="profile-header bg-white rounded-lg shadow-sm mb-6">
                <!-- Cover Photo -->
                <div class="profile-cover">
                    <div class="cover-image">
                        <div class="arkan-logo">
                            <img src="{{ asset('assets/images/arkan.png') }}" alt="أركان" class="logo-img">
                        </div>
                    </div>
                    <div class="cover-overlay"></div>
                </div>

                <!-- Profile Info -->
                <div class="profile-info">
                    <div class="profile-avatar-container">
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="profile-avatar">
                        @if($user->is_online ?? false)
                        <div class="online-indicator"></div>
                        @endif
                    </div>

                    <div class="profile-details">
                        <h1 class="profile-name">{{ $user->name }}</h1>
                        <div class="profile-email">{{ $user->email }}</div>

                        <!-- Profile Stats -->
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-number">{{ $stats['posts_count'] }}</span>
                                <span class="stat-label">منشور</span>
                            </div>
                            <div class="stat-item" onclick="showFollowers({{ $user->id }})">
                                <span class="stat-number" id="followersCount">{{ $stats['followers_count'] }}</span>
                                <span class="stat-label">متابع</span>
                            </div>
                            <div class="stat-item" onclick="showFollowing({{ $user->id }})">
                                <span class="stat-number">{{ $stats['following_count'] }}</span>
                                <span class="stat-label">متابَع</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="profile-actions">
                            @if(!$isOwnProfile)
                                <button id="followBtn"
                                        class="follow-btn {{ $isFollowing ? 'following' : '' }}"
                                        onclick="toggleFollow({{ $user->id }})">
                                    <i class="fas {{ $isFollowing ? 'fa-user-check' : 'fa-user-plus' }}"></i>
                                    <span>{{ $isFollowing ? 'إلغاء المتابعة' : 'متابعة' }}</span>
                                </button>

                                <button class="message-btn" onclick="sendMessage({{ $user->id }})">
                                    <i class="fas fa-envelope"></i>
                                    <span>رسالة</span>
                                </button>
                            @else
                                <button class="edit-profile-btn" onclick="editProfile()">
                                    <i class="fas fa-edit"></i>
                                    <span>تعديل البروفايل</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Followers -->
                @if($recentFollowers->count() > 0)
                <div class="recent-followers">
                    <div class="recent-followers-label">آخر المتابعين:</div>
                    <div class="followers-avatars">
                        @foreach($recentFollowers as $follower)
                        @if($follower)
                        <a href="{{ route('social.profile.show', $follower) }}" class="follower-avatar" title="{{ $follower->name }}">
                            <img src="{{ $follower->profile_photo_url }}" alt="{{ $follower->name }}">
                        </a>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Navigation Tabs -->
                <div class="profile-tabs">
                    <button class="tab-btn active" onclick="showTab('posts')">
                        <i class="fas fa-newspaper"></i>
                        المنشورات
                    </button>
                    <button class="tab-btn" onclick="showTab('about')">
                        <i class="fas fa-info-circle"></i>
                        حول
                    </button>
                    <button class="tab-btn" onclick="showTab('media')">
                        <i class="fas fa-images"></i>
                        الصور والفيديوهات
                    </button>
                </div>

                <!-- Posts Tab -->
                <div id="postsTab" class="tab-content active">
                    @if($posts->count() > 0)
                        <div class="posts-grid">
                            @foreach($posts as $post)
                                @include('social.partials.post', ['post' => $post])
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-wrapper">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <h3>لا توجد منشورات</h3>
                            <p>{{ $isOwnProfile ? 'لم تقم بنشر أي منشور بعد' : $user->name . ' لم ينشر أي منشور بعد' }}</p>
                            @if($isOwnProfile)
                            <button class="create-post-btn" onclick="openCreatePostModal()">
                                <i class="fas fa-plus"></i>
                                إنشاء منشور جديد
                            </button>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- About Tab -->
                <div id="aboutTab" class="tab-content">
                    <div class="about-section">
                        <div class="info-card">
                            <h3><i class="fas fa-user"></i> معلومات أساسية</h3>
                            <div class="info-item">
                                <strong>الاسم:</strong> {{ $user->name }}
                            </div>
                            <div class="info-item">
                                <strong>البريد الإلكتروني:</strong> {{ $user->email }}
                            </div>
                            <div class="info-item">
                                <strong>تاريخ الانضمام:</strong> {{ $user->created_at->format('d/m/Y') }}
                            </div>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-chart-bar"></i> الإحصائيات</h3>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon posts">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-num">{{ $stats['posts_count'] }}</span>
                                        <span class="stat-text">منشور</span>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-icon followers">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-num">{{ $stats['followers_count'] }}</span>
                                        <span class="stat-text">متابع</span>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-icon following">
                                        <i class="fas fa-user-friends"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-num">{{ $stats['following_count'] }}</span>
                                        <span class="stat-text">متابَع</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Tab -->
                <div id="mediaTab" class="tab-content">
                    <div class="media-grid">
                        @php
                            $mediaPosts = $posts->filter(function($post) {
                                return $post->image || $post->video;
                            });
                        @endphp

                        @if($mediaPosts->count() > 0)
                            @foreach($mediaPosts as $post)
                                <div class="media-item" onclick="openPost({{ $post->id }})">
                                    @if($post->image)
                                        <img src="{{ $post->getImageUrl() }}" alt="صورة المنشور">
                                        <div class="media-overlay">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @elseif($post->video)
                                        <video>
                                            <source src="{{ $post->getVideoUrl() }}" type="video/mp4">
                                        </video>
                                        <div class="media-overlay">
                                            <i class="fas fa-play"></i>
                                        </div>
                                    @endif

                                    <div class="media-stats">
                                        <span><i class="fas fa-heart"></i> {{ $post->likes_count }}</span>
                                        <span><i class="fas fa-comment"></i> {{ $post->comments_count }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-images"></i>
                                </div>
                                <h3>لا توجد صور أو فيديوهات</h3>
                                <p>{{ $isOwnProfile ? 'لم تقم بنشر صور أو فيديوهات بعد' : $user->name . ' لم ينشر صور أو فيديوهات بعد' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/social-facebook.css') }}">

    <!-- JavaScript -->
    <script src="{{ asset('js/profile.js') }}"></script>
    <script src="{{ asset('js/social-facebook.js') }}"></script>
    <script>
        // تهيئة معرف المستخدم الحالي
        window.currentUserId = {{ $user->id }};
        window.isOwnProfile = {{ $isOwnProfile ? 'true' : 'false' }};
    </script>
</x-app-layout>
