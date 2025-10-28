// Meta data handling for Overtime Requests
document.addEventListener('DOMContentLoaded', function() {
    // Add meta tags with CSRF token and User ID for use in JavaScript
    const head = document.head || document.getElementsByTagName('head')[0];

    // Add CSRF token meta tag if it doesn't exist
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const csrfToken = document.createElement('meta');
        csrfToken.name = 'csrf-token';
        csrfToken.content = document.querySelector('input[name="_token"]')?.value || '';
        head.appendChild(csrfToken);
    }

    // Add user ID meta tag if it doesn't exist
    if (!document.querySelector('meta[name="user-id"]')) {
        const userId = document.createElement('meta');
        userId.name = 'user-id';
        // Try to get the user ID from a hidden input or other source
        userId.content = document.querySelector('input[name="user_id"][type="hidden"]')?.value || '';
        head.appendChild(userId);
    }
});
