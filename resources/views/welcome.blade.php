<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Arkan') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- Styles -->
        <link rel="stylesheet" href="{{ asset('css/homePage.css') }}?t={{ time() }}">
    </head>
    <body>
        <!-- Header Section -->
        <header>
            <nav class="navbar">
                <div class="container">
                    <a href="/" class="logo">
                        <img src="{{ asset('assets/images/arkan.png') }}" alt="Arkan Logo" height="50">
                    </a>
                    <div class="nav-links">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">لوحة التحكم</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">تسجيل الدخول</a>
                        @endauth
                    </div>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-background"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 class="hero-title">نظام أركان لإدارة الموارد البشرية</h1>
                <p class="hero-subtitle">إدارة الحضور والانصراف والإجازات والأذونات بكل سهولة وكفاءة</p>
                <div class="hero-buttons">
                    <a href="{{ route('login') }}" class="btn btn-primary">الدخول للنظام</a>
                    <a href="#features" class="btn btn-outline">تعرف على المميزات</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <h2 class="section-title">مميزات النظام</h2>
            <div class="features-container">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">إدارة الحضور والانصراف</h3>
                    <p class="feature-description">تسجيل الحضور والانصراف بشكل دقيق مع إمكانية استخراج التقارير بسهولة</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="feature-title">إدارة الإجازات</h3>
                    <p class="feature-description">متابعة طلبات الإجازات والموافقات مع حساب الرصيد المتبقي بشكل تلقائي</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="feature-title">طلبات الأذونات</h3>
                    <p class="feature-description">تقديم ومتابعة طلبات الأذونات مع سير العمل المناسب للموافقات</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-business-time"></i>
                    </div>
                    <h3 class="feature-title">إدارة الوقت الإضافي</h3>
                    <p class="feature-description">تسجيل وحساب ساعات العمل الإضافي مع التقارير التفصيلية</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="cta-container">
                <h2 class="cta-title">نظام إدارة الأذونات والإجازات والأوفر تايم لموظفي أركان</h2>
                <p class="cta-description">نظام متكامل لإدارة طلبات الأذونات والإجازات وساعات العمل الإضافي لجميع موظفي أركان</p>
                <a href="{{ route('login') }}" class="btn btn-primary">ابدأ الآن</a>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="container">
                <div class="footer-logo">
                    <img src="{{ asset('assets/images/arkan.png') }}" alt="Arkan Logo" height="70">
                </div>
                <div class="footer-links">
                    <a href="#">الشروط والأحكام</a>
                    <a href="#">سياسة الخصوصية</a>
                    <a href="#">اتصل بنا</a>
                </div>
                <div class="copyright">
                    © {{ date('Y') }} أركان للاستشارات الاقتصادية. جميع الحقوق محفوظة.
                </div>
            </div>
        </footer>

        <script>
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        </script>
    </body>
</html>
