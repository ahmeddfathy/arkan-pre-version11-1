importScripts("https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js");
importScripts(
    "https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js"
);

// Firebase configuration will be injected by the page
let firebaseConfig = null;

// Initialize Firebase when config is available
self.addEventListener("message", (event) => {
    if (event.data && event.data.type === "FIREBASE_CONFIG") {
        firebaseConfig = event.data.config;

        if (!firebase.apps.length && firebaseConfig) {
            firebase.initializeApp(firebaseConfig);
            initializeMessaging();
        }
    }
});

let messaging = null;

function initializeMessaging() {
    messaging = firebase.messaging();

    messaging.onBackgroundMessage((payload) => {
        console.log(
            "[firebase-messaging-sw.js] Received background message ",
            payload
        );
        // Background message will be handled by push event
    });
}

self.addEventListener("push", function (event) {
    console.log("[firebase-messaging-sw.js] Push received");

    let payload;
    try {
        payload = event.data.json();
    } catch (e) {
        console.error("Error parsing push event data:", e);
        return;
    }

    const data = payload.data || {};
    const notificationTitle = data.title || "إشعار جديد";
    const notificationBody = data.body || "";

    const notificationOptions = {
        body: notificationBody,
        icon: "/logo.png",
        badge: "/badge.png",
        data: data,
        vibrate: [100, 50, 100],
        requireInteraction: true,
        dir: "rtl",
        lang: "ar",
        tag: `notification_${Date.now()}`,
    };

    event.waitUntil(
        self.registration.showNotification(
            notificationTitle,
            notificationOptions
        )
    );
});

self.addEventListener("notificationclick", function (event) {
    console.log("[firebase-messaging-sw.js] Notification clicked: ", event);

    try {
        event.notification.close();
    } catch (e) {
        console.error("Error closing notification:", e);
    }

    let urlToOpen;
    try {
        urlToOpen =
            event.notification.data?.url ||
            determineUrlFromType(event.notification.data?.type) ||
            "/dashboard";
    } catch (e) {
        console.error("Error getting URL from notification:", e);
        urlToOpen = "/dashboard";
    }

    event.waitUntil(
        clients
            .matchAll({
                type: "window",
                includeUncontrolled: true,
            })
            .then(function (clientList) {
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    const clientUrl = new URL(client.url);
                    const targetUrl = new URL(urlToOpen, self.location.origin);

                    if (
                        clientUrl.pathname === targetUrl.pathname &&
                        "focus" in client
                    ) {
                        return client.focus();
                    }
                }

                return clients.openWindow(urlToOpen);
            })
            .catch(function (error) {
                console.error("Error navigating to URL:", error);
                return clients.openWindow("/dashboard");
            })
    );
});

self.addEventListener("install", function (event) {
    console.log("[firebase-messaging-sw.js] Service Worker installed");
    self.skipWaiting();
});

self.addEventListener("activate", function (event) {
    console.log("[firebase-messaging-sw.js] Service Worker activated");
    event.waitUntil(clients.claim());
});

function determineUrlFromType(type) {
    if (!type) return "/dashboard";

    const urlMap = {
        new_leave_request: "/leaves/requests",
        leave_request_status_update: "/leaves/my-requests",
        leave_request_modified: "/leaves/requests",
        leave_request_deleted: "/leaves/requests",

        new_permission_request: "/permissions/requests",
        permission_request_status_update: "/permissions/my-requests",
        permission_request_modified: "/permissions/requests",
        permission_request_deleted: "/permissions/requests",

        new_overtime_request: "/overtime/requests",
        overtime_status_updated: "/overtime/my-requests",
        overtime_request_modified: "/overtime/requests",
        overtime_request_deleted: "/overtime/requests",

        general_notification: "/notifications",
        system_notification: "/dashboard",
    };

    return urlMap[type] || "/dashboard";
}
