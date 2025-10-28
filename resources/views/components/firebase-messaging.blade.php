@if(auth()->check())
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js"></script>

    <script>
        (function() {
            if (window.fcmInitialized) return;
            window.fcmInitialized = true;

            // Initialize Firebase with config from server
            async function fetchFirebaseConfig() {
                try {
                    const response = await fetch('{{ route("firebase.config") }}');
                    if (!response.ok) {
                        throw new Error(`Failed to fetch Firebase configuration: ${response.status}`);
                    }

                    const data = await response.json();
                    if (!data.success) {
                        console.warn('Firebase configuration error:', data.message);
                        if (data.missing_fields) {
                            console.warn('Missing Firebase environment variables:', data.missing_fields);
                        }
                        return null;
                    }

                    if (!data.config) {
                        throw new Error('Invalid Firebase configuration: config object missing');
                    }

                    // Validate required fields on client side as well
                    const requiredFields = ['projectId', 'apiKey', 'authDomain', 'messagingSenderId', 'appId'];
                    const missingFields = requiredFields.filter(field => !data.config[field]);

                    if (missingFields.length > 0) {
                        console.error('Firebase configuration missing required fields:', missingFields);
                        return null;
                    }

                    console.log('Firebase configuration loaded successfully');
                    return data.config;
                } catch (error) {
                    console.error('Error fetching Firebase config:', error);
                    return null;
                }
            }

            let currentFcmToken = localStorage.getItem('fcm_token') || '';
            let tokenLastSent = parseInt(localStorage.getItem('fcm_token_last_sent') || '0');
            let isNewSession = @json(session('new_login', false));

            const TOKEN_UPDATE_INTERVAL = 24 * 60 * 60 * 1000;
            const isEdgeBrowser = navigator.userAgent.indexOf("Edg") !== -1;

            async function initializeFirebaseMessaging() {
                try {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        console.log('Notification permission not granted, skipping Firebase messaging initialization');
                        return;
                    }

                    const firebaseConfig = await fetchFirebaseConfig();
                    if (!firebaseConfig) {
                        console.warn('Could not load Firebase configuration. Please check your environment variables.');
                        console.warn('Required Firebase environment variables: FIREBASE_PROJECT_ID, FIREBASE_API_KEY, FIREBASE_AUTH_DOMAIN, FIREBASE_MESSAGING_SENDER_ID, FIREBASE_APP_ID');
                        return;
                    }

                    // Validate Firebase config before initializing
                    try {
                        // Initialize Firebase only if no apps exist
                        if (!firebase.apps.length) {
                            firebase.initializeApp(firebaseConfig);
                            console.log('Firebase app initialized successfully');
                        } else {
                            console.log('Firebase app already initialized');
                        }
                    } catch (firebaseInitError) {
                        console.error('Firebase initialization failed:', firebaseInitError);
                        return;
                    }

                    const swOptions = {
                        updateViaCache: 'none'
                    };

                    const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', swOptions);

                    if (isEdgeBrowser) {
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    }

                    // Send config to service worker
                    if (registration.active) {
                        registration.active.postMessage({
                            type: 'FIREBASE_CONFIG',
                            config: firebaseConfig
                        });
                    }

                    // Initialize Firebase messaging
                    let messaging;
                    try {
                        messaging = firebase.messaging();
                        messaging.useServiceWorker(registration);
                        console.log('Firebase messaging service initialized successfully');
                    } catch (messagingError) {
                        console.error('Failed to initialize Firebase messaging service:', messagingError);
                        return;
                    }

                    const now = Date.now();
                    let shouldUpdateServer = false;

                    try {
                        const tokenFromFirebase = await messaging.getToken();

                        if (tokenFromFirebase) {
                            if (
                                !currentFcmToken ||
                                tokenFromFirebase !== currentFcmToken ||
                                isNewSession ||
                                (now - tokenLastSent > TOKEN_UPDATE_INTERVAL)
                            ) {
                                currentFcmToken = tokenFromFirebase;
                                shouldUpdateServer = true;
                            }

                            localStorage.setItem('fcm_token', currentFcmToken);
                        }
                    } catch (error) {
                        if (currentFcmToken) {
                            try {
                                await messaging.deleteToken();
                            } catch (e) {}
                        }

                        try {
                            const newToken = await messaging.getToken();
                            if (newToken) {
                                currentFcmToken = newToken;
                                localStorage.setItem('fcm_token', newToken);
                                shouldUpdateServer = true;
                            }
                        } catch (e) {}
                    }

                    if (shouldUpdateServer && currentFcmToken) {
                        const updateResult = await updateTokenOnServer(currentFcmToken);
                        if (updateResult.success) {
                            localStorage.setItem('fcm_token_last_sent', now.toString());
                        }
                    }

                    messaging.onTokenRefresh(async () => {
                        try {
                            const refreshedToken = await messaging.getToken();
                            if (refreshedToken && refreshedToken !== currentFcmToken) {
                                currentFcmToken = refreshedToken;
                                localStorage.setItem('fcm_token', refreshedToken);
                                localStorage.setItem('fcm_token_last_sent', Date.now().toString());
                                await updateTokenOnServer(refreshedToken);
                            }
                        } catch (error) {}
                    });

                    messaging.onMessage((payload) => {
                        console.log('Received foreground message, letting service worker handle it');
                    });
                } catch (error) {
                    console.error('Error initializing Firebase messaging:', error);

                    // Provide specific guidance based on error type
                    if (error.message && error.message.includes('projectId')) {
                        console.error('Firebase Error: Missing projectId. Please check FIREBASE_PROJECT_ID environment variable.');
                    } else if (error.message && error.message.includes('apiKey')) {
                        console.error('Firebase Error: Missing apiKey. Please check FIREBASE_API_KEY environment variable.');
                    } else if (error.message && error.message.includes('missing-app-config')) {
                        console.error('Firebase Error: Missing required configuration values. Please check all Firebase environment variables.');
                    }
                }
            }

            async function updateTokenOnServer(token) {
                try {
                    const response = await fetch('{{ route("fcm.token.update") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ token })
                    });

                    const data = await response.json();
                    return data;
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                initializeFirebaseMessaging();
            } else {
                document.addEventListener('DOMContentLoaded', initializeFirebaseMessaging, { once: true });
            }
        })();
    </script>
@endif
