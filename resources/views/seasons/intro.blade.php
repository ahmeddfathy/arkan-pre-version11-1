<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>مرحباً بك في {{ $season->name }}</title>

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <!-- GSAP للأنيميشن المتقدم -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/TextPlugin.min.js"></script>

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts - تأكد من وجود فونت عربي جميل -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 400% 400%;
            animation: gradientBackground 15s ease infinite;
            color: white;
            direction: rtl;
        }

        @keyframes gradientBackground {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        #threejs-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 1;
        }

        .overlay-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(
                135deg,
                rgba(0, 0, 0, 0.4) 0%,
                rgba(102, 126, 234, 0.1) 50%,
                rgba(0, 0, 0, 0.4) 100%
            );
            backdrop-filter: blur(5px);
            padding: 2rem;
        }

        .season-logo {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            margin-bottom: 2rem;
            border: 5px solid rgba(255, 255, 255, 0.4);
            object-fit: cover;
            opacity: 0;
            transform: scale(0.5) rotate(-180deg);
            box-shadow:
                0 0 30px rgba(255, 255, 255, 0.3),
                0 0 60px rgba(78, 205, 196, 0.2),
                inset 0 0 30px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }


        .season-title {
            font-size: 5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 1.5rem;
            opacity: 0;
            transform: translateY(50px);
            text-shadow:
                2px 2px 10px rgba(0, 0, 0, 0.7),
                0 0 20px rgba(255, 255, 255, 0.5),
                0 0 40px rgba(78, 205, 196, 0.3);
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #ff6b6b);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .season-description {
            font-size: 1.6rem;
            text-align: center;
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: translateY(30px);
            max-width: 700px;
            line-height: 1.9;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.5);
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .season-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 3rem;
            opacity: 0;
            transform: translateY(30px);
        }

        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-width: 140px;
        }

        .stat-item:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15));
            transform: translateY(-5px) scale(1.02);
            border-color: rgba(78, 205, 196, 0.5);
            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.3),
                0 5px 15px rgba(78, 205, 196, 0.2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(45deg, #4ecdc4, #45b7d1, #96ceb4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(78, 205, 196, 0.3);
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 0.8rem;
            font-weight: 500;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            opacity: 0;
            transform: translateY(30px);
        }

        .btn {
            padding: 18px 45px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }


        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb);
            color: white;
            box-shadow:
                0 15px 35px rgba(102, 126, 234, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow:
                0 20px 50px rgba(102, 126, 234, 0.6),
                0 5px 15px rgba(78, 205, 196, 0.3);
            filter: brightness(1.1);
        }

        .btn-secondary {
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary:hover {
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            border-color: rgba(78, 205, 196, 0.8);
            transform: translateY(-3px) scale(1.02);
            box-shadow:
                0 15px 35px rgba(0, 0, 0, 0.3),
                0 0 20px rgba(78, 205, 196, 0.3);
        }

        .btn-ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        /* الشاشات الصغيرة */
        @media (max-width: 768px) {
            .season-title {
                font-size: 2.5rem;
            }

            .season-stats {
                flex-direction: column;
                gap: 1rem;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                justify-content: center;
            }
        }

        /* تأثيرات إضافية */
        .floating {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .glow {
            box-shadow: 0 0 20px rgba(78, 205, 196, 0.5);
        }

        /* Loading animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: #1a1a1a;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(78, 205, 196, 0.3);
            border-radius: 50%;
            border-top-color: #4ecdc4;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-overlay" id="loadingScreen">
        <div class="loader"></div>
        <p>جاري تحضير تجربة رائعة...</p>
    </div>

    <!-- Three.js Container -->
    <div id="threejs-container"></div>

    <!-- Content Overlay -->
    <div class="overlay-content">
        <!-- شعار السيزون -->
        @if($season->image)
            <img src="{{ asset('storage/' . $season->image) }}" alt="{{ $season->name }}" class="season-logo floating" id="seasonLogo">
        @else
            <div class="season-logo floating glow" id="seasonLogo" style="background: linear-gradient(45deg, {{ $season->color_theme ?? '#4ecdc4' }}, #667eea); display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-trophy" style="font-size: 3rem;"></i>
            </div>
        @endif

        <!-- عنوان السيزون -->
        <h1 class="season-title" id="seasonTitle">{{ $season->name }}</h1>

        <!-- وصف السيزون -->
        @if($season->description)
            <p class="season-description" id="seasonDesc">{{ $season->description }}</p>
        @endif

        <!-- إحصائيات السيزون -->
        <div class="season-stats" id="seasonStats">
            <div class="stat-item">
                <div class="stat-number" id="daysRemaining">
                    {{ (int) ($season->is_upcoming ? $season->start_date->diffInDays(now()) : ($season->is_current ? $season->end_date->diffInDays(now()) : 0)) }}
                </div>
                <div class="stat-label">
                    {{ $season->is_upcoming ? 'أيام للبداية' : 'أيام متبقية' }}
                </div>
            </div>

            @if($season->rewards && count($season->rewards) > 0)
            <div class="stat-item">
                <div class="stat-number">{{ count($season->rewards) }}</div>
                <div class="stat-label">مكافآت رائعة</div>
            </div>
            @endif
        </div>

        <!-- أزرار العمل -->
        <div class="action-buttons" id="actionButtons">
            <button class="btn btn-primary" id="continueBtn" onclick="markSeasonAsSeen()">
                <i class="fas fa-play"></i>
                ابدأ الرحلة
            </button>

            <a href="{{ route('seasons.show', $season) }}" class="btn btn-secondary">
                <i class="fas fa-info-circle"></i>
                تفاصيل أكثر
            </a>
        </div>
    </div>

    <script>
        // متغيرات Three.js
        let scene, camera, renderer, particles = [];
        let animationId;

        // تهيئة Three.js
        function initThreeJS() {
            // إنشاء المشهد
            scene = new THREE.Scene();

            // إنشاء الكاميرا
            camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 5;

            // إنشاء العارض
            renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0);
            document.getElementById('threejs-container').appendChild(renderer.domElement);

            // إنشاء الجسيمات
            createParticles();

            // إنشاء أشكال ثلاثية الأبعاد
            createSeasonObjects();

            // بدء الأنيميشن
            animate();
        }

        // إنشاء جسيمات متحركة
        function createParticles() {
            const particleCount = 1000;
            const geometry = new THREE.BufferGeometry();
            const positions = new Float32Array(particleCount * 3);
            const colors = new Float32Array(particleCount * 3);

            // ألوان السيزون
            const seasonColor = new THREE.Color('{{ $season->color_theme ?? "#4ecdc4" }}');
            const accentColor = new THREE.Color('#667eea');

            for (let i = 0; i < particleCount; i++) {
                // المواضع العشوائية
                positions[i * 3] = (Math.random() - 0.5) * 20;
                positions[i * 3 + 1] = (Math.random() - 0.5) * 20;
                positions[i * 3 + 2] = (Math.random() - 0.5) * 20;

                // ألوان متدرجة
                const color = Math.random() > 0.5 ? seasonColor : accentColor;
                colors[i * 3] = color.r;
                colors[i * 3 + 1] = color.g;
                colors[i * 3 + 2] = color.b;
            }

            geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
            geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

            const material = new THREE.PointsMaterial({
                size: 0.05,
                vertexColors: true,
                transparent: true,
                opacity: 0.8
            });

            const particleSystem = new THREE.Points(geometry, material);
            scene.add(particleSystem);
            particles.push(particleSystem);
        }

        // إنشاء أشكال ثلاثية الأبعاد للسيزون
        function createSeasonObjects() {
            // إنشاء أشكال هندسية متحركة
            const geometries = [
                new THREE.OctahedronGeometry(0.3),
                new THREE.DodecahedronGeometry(0.3),
                new THREE.IcosahedronGeometry(0.3)
            ];

            for (let i = 0; i < 15; i++) {
                const geometry = geometries[Math.floor(Math.random() * geometries.length)];
                const material = new THREE.MeshBasicMaterial({
                    color: new THREE.Color('{{ $season->color_theme ?? "#4ecdc4" }}'),
                    transparent: true,
                    opacity: 0.3,
                    wireframe: true
                });

                const mesh = new THREE.Mesh(geometry, material);

                // مواضع عشوائية
                mesh.position.x = (Math.random() - 0.5) * 10;
                mesh.position.y = (Math.random() - 0.5) * 10;
                mesh.position.z = (Math.random() - 0.5) * 10;

                // سرعات دوران عشوائية
                mesh.rotationSpeed = {
                    x: (Math.random() - 0.5) * 0.02,
                    y: (Math.random() - 0.5) * 0.02,
                    z: (Math.random() - 0.5) * 0.02
                };

                scene.add(mesh);
                particles.push(mesh);
            }
        }

        // أنيميشن مستمر
        function animate() {
            animationId = requestAnimationFrame(animate);

            // دوران الجسيمات
            particles.forEach(particle => {
                if (particle.rotationSpeed) {
                    particle.rotation.x += particle.rotationSpeed.x;
                    particle.rotation.y += particle.rotationSpeed.y;
                    particle.rotation.z += particle.rotationSpeed.z;
                } else if (particle.rotation) {
                    particle.rotation.y += 0.001;
                }
            });

            // تحريك الكاميرا قليلاً
            camera.position.x = Math.sin(Date.now() * 0.0005) * 0.5;
            camera.position.y = Math.cos(Date.now() * 0.0005) * 0.5;

            renderer.render(scene, camera);
        }

        // تهيئة GSAP Timeline
        function initGSAPAnimations() {
            const tl = gsap.timeline();

            // إخفاء شاشة التحميل
            tl.to('#loadingScreen', {
                opacity: 0,
                duration: 1,
                onComplete: () => {
                    document.getElementById('loadingScreen').style.display = 'none';
                }
            });

            // أنيميشن الشعار
            tl.to('#seasonLogo', {
                opacity: 1,
                scale: 1,
                rotation: 0,
                duration: 1.5,
                ease: 'elastic.out(1, 0.3)'
            }, '-=0.5');

            // أنيميشن العنوان
            tl.to('#seasonTitle', {
                opacity: 1,
                y: 0,
                duration: 1,
                ease: 'back.out(1.7)'
            }, '-=1');

            // أنيميشن الوصف
            tl.to('#seasonDesc', {
                opacity: 1,
                y: 0,
                duration: 0.8,
                ease: 'power2.out'
            }, '-=0.5');

            // أنيميشن الإحصائيات
            tl.to('#seasonStats', {
                opacity: 1,
                y: 0,
                duration: 0.8,
                ease: 'power2.out'
            }, '-=0.3');

            // أنيميشن الأزرار
            tl.to('#actionButtons', {
                opacity: 1,
                y: 0,
                duration: 0.8,
                ease: 'back.out(1.7)'
            }, '-=0.3');
        }

        // تأثير الريبل على الأزرار
        function createRipple(event) {
            const button = event.currentTarget;
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;

            const ripple = document.createElement('span');
            ripple.classList.add('btn-ripple');
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            button.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        }

        // تسجيل مشاهدة السيزون
        function markSeasonAsSeen() {
            const btn = document.getElementById('continueBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحضير...';
            btn.disabled = true;

            // أنيميشن خروج
            gsap.to('.overlay-content', {
                opacity: 0,
                scale: 0.9,
                duration: 0.5,
                ease: 'power2.in',
                onComplete: () => {
                    // إرسال طلب AJAX
                    fetch(`{{ route('season.mark-seen', $season) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            redirect_to: window.location.href.includes('redirect_to') ?
                                new URLSearchParams(window.location.search).get('redirect_to') :
                                '{{ route("dashboard") }}'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = data.redirect_url;
                        } else {
                            throw new Error('فشل في المعالجة');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // في حالة الخطأ، العودة إلى الصفحة الرئيسية
                        window.location.href = '{{ route("dashboard") }}';
                    });
                }
            });
        }

        // تغيير حجم النافذة
        function handleResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        // تهيئة التطبيق
        function init() {
            initThreeJS();
            setTimeout(() => {
                initGSAPAnimations();
            }, 500);

            // إضافة مستمعي الأحداث
            window.addEventListener('resize', handleResize);

            // إضافة تأثير الريبل للأزرار
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', createRipple);
            });

            // موسيقى خلفية خفيفة (اختياري)
            // يمكنك إضافة ملف صوتي هنا
        }

        // بدء التطبيق عند تحميل الصفحة
        window.addEventListener('load', init);

        // تنظيف الموارد عند الخروج
        window.addEventListener('beforeunload', () => {
            if (animationId) {
                cancelAnimationFrame(animationId);
            }
            if (renderer) {
                renderer.dispose();
            }
        });
    </script>
</body>
</html>
