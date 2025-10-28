// Sanitization utilities
const sanitizer = {
    DOMPurify: window.DOMPurify,
    sanitizeHTML: function(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },
    purify: function(html) {
        if (this.DOMPurify) {
            return this.DOMPurify.sanitize(html);
        }
        return this.sanitizeHTML(html);
    }
};

// Chat state
let currentChatId = null;
let chatConfig = {};
let messagePollingInterval = null;
let lastMessageTimestamp = null;
let isPollingPaused = false;
let pollingAttempts = 0;
const MAX_POLLING_ATTEMPTS = 5;
const POLLING_INTERVAL = 3000; // 3 seconds
const POLLING_BACKOFF_INTERVAL = 10000; // 10 seconds after failures

// DOM Elements
const elements = {
    chatList: document.querySelector('.chat-list'),
    usersList: document.querySelector('.users-list'),
    tabButtons: document.querySelectorAll('.tab-btn'),
    tabContents: document.querySelectorAll('.tab-content'),
    noChat: document.querySelector('#no-chat-selected'),
    chatArea: document.querySelector('#chat-area'),
    contactName: document.querySelector('#contact-name'),
    contactAvatar: document.querySelector('#contact-avatar'),
    messagesContainer: document.querySelector('#messages-container'),
    messageForm: document.querySelector('#message-form'),
    messageInput: document.querySelector('#message-input'),
    chatStatus: document.createElement('div')
};

// Initialize chat status indicator
elements.chatStatus.className = 'chat-status';
document.querySelector('.chat-header')?.appendChild(elements.chatStatus);

// API endpoints
const API = {
    messages: (userId) => `/chat/messages/${userId}`,
    newMessages: (userId, timestamp) => `/chat/new-messages/${userId}${timestamp ? `?after=${timestamp}` : ''}`,
    send: '/chat/send'
};

// Debug log function
function debugLog(message, data = null) {
    console.log(`[Chat Debug] ${message}`, data || '');
}

function loadChat(userId, userName) {
    if (!userId || !userName) return;

    debugLog(`Loading chat for user: ${userName} (ID: ${userId})`);

    elements.chatList?.classList.remove('active');
    currentChatId = userId;
    lastMessageTimestamp = null;
    pollingAttempts = 0;

    if (elements.chatStatus) {
        elements.chatStatus.textContent = 'جاري الاتصال...';
        elements.chatStatus.className = 'chat-status connecting';
    }

    if (elements.noChat) elements.noChat.classList.add('d-none');
    if (elements.chatArea) elements.chatArea.classList.remove('d-none');
    if (elements.contactName) elements.contactName.textContent = sanitizer.sanitizeHTML(userName);
    if (elements.contactAvatar) {
        elements.contactAvatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=random`;
    }

    fetchMessages(true);
    startMessagePolling();
}

function fetchMessages(isInitialLoad = false) {
    if (!currentChatId) return;

    const url = isInitialLoad ? API.messages(currentChatId) : API.newMessages(currentChatId, lastMessageTimestamp);

    debugLog(`Fetching messages: ${url}`);

    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error(`Network response was not ok: ${response.status}`);
            return response.json();
        })
        .then(messages => {
            // Reset polling attempts on successful response
            pollingAttempts = 0;
            updateConnectionStatus('connected');

            debugLog(`Fetched ${messages.length} messages`, messages);

            if (Array.isArray(messages) && messages.length > 0) {
                // Update the last message timestamp
                const lastMessage = messages[messages.length - 1];
                const messageTime = new Date(lastMessage.created_at).getTime();

                if (!lastMessageTimestamp || messageTime > lastMessageTimestamp) {
                    lastMessageTimestamp = messageTime;
                    debugLog(`Updated lastMessageTimestamp to: ${lastMessageTimestamp}`);
                }

                if (isInitialLoad) {
                    renderMessages(messages);
                } else {
                    // Append only new messages
                    const uniqueMessages = filterNewMessages(messages);
                    if (uniqueMessages.length > 0) {
                        appendNewMessages(uniqueMessages);
                    }
                }

                if (isInitialLoad) {
                    scrollToBottom();
                }
            } else if (isInitialLoad) {
                // Empty message history - show empty state
                elements.messagesContainer.innerHTML = `
                    <div class="no-messages">
                        <p>لا توجد رسائل سابقة. ابدأ محادثة جديدة!</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching messages:', error);
            pollingAttempts++;

            if (pollingAttempts >= MAX_POLLING_ATTEMPTS) {
                updateConnectionStatus('disconnected');
                // Use backoff strategy for repeated failures
                pausePollingTemporarily();
            }
        });
}

// Filter out new messages to avoid duplication
function filterNewMessages(messages) {
    if (!messages || !messages.length) return [];

    // Get all message IDs already in the UI
    const existingMessageIds = Array.from(
        document.querySelectorAll('.message[id^="msg-"]')
    ).map(el => {
        const id = el.id.replace('msg-', '');
        return parseInt(id, 10);
    });

    debugLog(`Existing message IDs: ${existingMessageIds.join(', ')}`);

    // Filter out only new messages
    return messages.filter(msg => {
        // Skip messages without an ID
        if (!msg.id) return false;

        // Keep only messages that don't exist in the UI yet
        return !existingMessageIds.includes(parseInt(msg.id, 10));
    });
}

function renderMessages(messages) {
    if (!elements.messagesContainer || !Array.isArray(messages)) return;

    let html = '';
    let currentDate = null;

    messages.forEach(message => {
        const messageDate = new Date(message.created_at).toLocaleDateString();

        if (messageDate !== currentDate) {
            html += `
                <div class="message-date-divider">
                    <span>${sanitizer.sanitizeHTML(messageDate)}</span>
                </div>
            `;
            currentDate = messageDate;
        }

        html += createMessageHTML(message);
    });

    elements.messagesContainer.innerHTML = sanitizer.purify(html);
}

function appendNewMessages(messages) {
    if (!elements.messagesContainer || !Array.isArray(messages) || messages.length === 0) return;

    const fragment = document.createDocumentFragment();
    let lastElement = elements.messagesContainer.lastElementChild;
    let currentDate = null;

    // Try to get the current date from the last date divider
    const lastDateDivider = elements.messagesContainer.querySelector('.message-date-divider:last-of-type span');
    if (lastDateDivider) {
        currentDate = lastDateDivider.textContent;
    }

    messages.forEach(message => {
        const messageDate = new Date(message.created_at).toLocaleDateString();

        if (messageDate !== currentDate) {
            const dateDiv = document.createElement('div');
            dateDiv.className = 'message-date-divider';
            dateDiv.innerHTML = sanitizer.purify(`<span>${sanitizer.sanitizeHTML(messageDate)}</span>`);
            fragment.appendChild(dateDiv);
            currentDate = messageDate;
        }

        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = sanitizer.purify(createMessageHTML(message));

        // Append the first child directly to get proper DOM structure
        if (messageDiv.firstChild) {
            fragment.appendChild(messageDiv.firstChild);
        }
    });

    elements.messagesContainer.appendChild(fragment);
    scrollToBottom();

    // Play notification sound for incoming messages
    const hasIncomingMessage = messages.some(msg => msg.sender_id !== chatConfig.currentUserId);
    if (hasIncomingMessage) {
        playNotificationSound();
    }
}

function createMessageHTML(message) {
    const isOwn = message.sender_id === chatConfig.currentUserId;
    const time = new Date(message.created_at).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });

    const messageId = message.id ? `msg-${message.id}` : '';
    const optimisticClass = message.optimistic ? 'optimistic' : '';

    return `
        <div id="${messageId}" class="message ${isOwn ? 'message-own' : 'message-other'} ${optimisticClass}">
            <div class="message-content">
                <p>${sanitizer.sanitizeHTML(message.content)}</p>
                <div class="message-meta">
                    <span class="message-time">${sanitizer.sanitizeHTML(time)}</span>
                    ${isOwn ? `
                        <span class="message-status">
                            ${message.optimistic ?
                                '<span class="sending-indicator"></span>' :
                                message.is_seen ?
                                    '<i class="fas fa-check-double seen"></i>' :
                                    '<i class="fas fa-check"></i>'}
                        </span>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function startMessagePolling() {
    stopMessagePolling();
    isPollingPaused = false;
    messagePollingInterval = setInterval(() => {
        if (!isPollingPaused) {
            fetchMessages(false);
        }
    }, POLLING_INTERVAL);
}

function stopMessagePolling() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
        messagePollingInterval = null;
    }
}

function pausePollingTemporarily() {
    isPollingPaused = true;

    // Try to reconnect after POLLING_BACKOFF_INTERVAL
    setTimeout(() => {
        isPollingPaused = false;
        pollingAttempts = 0;
        updateConnectionStatus('reconnecting');
        fetchMessages(false);
    }, POLLING_BACKOFF_INTERVAL);
}

function scrollToBottom() {
    if (elements.messagesContainer) {
        elements.messagesContainer.scrollTop = elements.messagesContainer.scrollHeight;
    }
}

function updateConnectionStatus(status) {
    if (!elements.chatStatus) return;

    elements.chatStatus.className = `chat-status ${status}`;

    switch (status) {
        case 'connected':
            elements.chatStatus.textContent = 'متصل';
            break;
        case 'connecting':
            elements.chatStatus.textContent = 'جاري الاتصال...';
            break;
        case 'reconnecting':
            elements.chatStatus.textContent = 'جاري إعادة الاتصال...';
            break;
        case 'disconnected':
            elements.chatStatus.textContent = 'فقد الاتصال';
            break;
        default:
            elements.chatStatus.textContent = '';
    }

    // Auto-hide status after 3 seconds if connected
    if (status === 'connected') {
        setTimeout(() => {
            if (elements.chatStatus.classList.contains('connected')) {
                elements.chatStatus.classList.add('fade-out');
            }
        }, 3000);
    }
}

function playNotificationSound() {
    const sound = new Audio('/sounds/notification-18-270129.mp3');
    sound.volume = 0.5;

    // Only play sound if tab is not active
    if (document.hidden) {
        sound.play().catch(error => {
            console.error('Failed to play notification sound:', error);
        });
    }
}

// Update an optimistic message with the real message data once received from the server
function updateOptimisticMessage(optimisticId, realMessage) {
    const messageElement = document.getElementById(optimisticId);
    if (!messageElement) {
        debugLog(`Could not find optimistic message with ID: ${optimisticId}`);
        return;
    }

    debugLog(`Updating optimistic message: ${optimisticId} with real ID: ${realMessage.id}`);

    // Update the message content with the real data
    messageElement.id = 'msg-' + realMessage.id;
    messageElement.classList.remove('optimistic');

    // Update the message status to show it's been sent
    const statusElement = messageElement.querySelector('.message-status');
    if (statusElement) {
        statusElement.innerHTML = '<i class="fas fa-check"></i>';
    }
}

// Mark a message as failed if sending fails
function markMessageAsFailed(optimisticId) {
    const messageElement = document.getElementById(optimisticId);
    if (!messageElement) {
        debugLog(`Could not find optimistic message with ID: ${optimisticId}`);
        return;
    }

    debugLog(`Marking message as failed: ${optimisticId}`);

    // Add failed class to the message
    messageElement.classList.add('failed');

    // Update the message status to show it failed
    const statusElement = messageElement.querySelector('.message-status');
    if (statusElement) {
        statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> <span class="resend-link">إعادة المحاولة</span>';

        // Add click handler to the resend link
        const resendLink = statusElement.querySelector('.resend-link');
        if (resendLink) {
            resendLink.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Get the message content
                const content = messageElement.querySelector('p').textContent;

                // Remove the failed message
                messageElement.remove();

                // Create a new message with the same content
                if (elements.messageInput) {
                    elements.messageInput.value = content;
                    elements.messageForm.dispatchEvent(new Event('submit'));
                }
            });
        }
    }
}

function showError(message) {
    console.error(message);
    // Could add a visual error message here
}

function switchTab(tabName) {
    // Hide all tab contents
    elements.tabContents.forEach(content => {
        content.classList.remove('active');
    });

    // Deactivate all tab buttons
    elements.tabButtons.forEach(button => {
        button.classList.remove('active');
    });

    // Activate selected tab
    document.querySelector(`[data-tab="${tabName}"]`)?.classList.add('active');
    document.getElementById(`${tabName}-tab`)?.classList.add('active');
}

// Initialize chat
function initializeChat() {
    const chatContainer = document.querySelector('.chat-container');
    if (chatContainer) {
        try {
            const configStr = chatContainer.getAttribute('data-chat-config');
            if (!configStr) {
                throw new Error('Chat configuration not found');
            }
            chatConfig = JSON.parse(configStr);
            if (!chatConfig.currentUserId || !chatConfig.csrfToken) {
                throw new Error('Invalid chat configuration');
            }
            debugLog('Chat configuration loaded:', chatConfig);
        } catch (e) {
            console.error('Error parsing chat configuration:', e);
            showError('Failed to initialize chat. Please refresh the page.');
            return;
        }
    }

    // Setup tab switching
    elements.tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            if (tabName) switchTab(tabName);
        });
    });

    // Setup form submission
    elements.messageForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        debugLog('Message form submitted');

        const content = elements.messageInput?.value.trim();
        if (!content || !currentChatId || !chatConfig.csrfToken) {
            debugLog('Missing required data for message submission', {
                content: !!content,
                currentChatId: currentChatId,
                csrfToken: !!chatConfig.csrfToken
            });
            return;
        }

        // Disable submit button while sending
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        // Add optimistic message
        const optimisticId = 'msg-' + Date.now();
        const optimisticMessage = {
            id: optimisticId,
            sender_id: parseInt(chatConfig.currentUserId),
            receiver_id: parseInt(currentChatId),
            content: content,
            created_at: new Date().toISOString(),
            is_seen: false,
            optimistic: true
        };

        // Show optimistic message immediately
        appendNewMessages([optimisticMessage]);

        // Clear input field immediately
        if (elements.messageInput) elements.messageInput.value = '';

        // Create form data manually
        const formData = new FormData();
        formData.append('receiver_id', currentChatId);
        formData.append('content', content);
        formData.append('_token', chatConfig.csrfToken);

        debugLog('Sending message data:', {
            receiver_id: currentChatId,
            content: content,
            token: chatConfig.csrfToken.substring(0, 5) + '...'
        });

        fetch(API.send, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': chatConfig.csrfToken
            }
        })
        .then(response => {
            if (!response.ok) {
                console.error('Server response not OK:', response.status);
                throw new Error('Failed to send message');
            }
            return response.json();
        })
        .then(message => {
            debugLog('Message sent successfully:', message);
            // Update the optimistic message with the real one
            updateOptimisticMessage(optimisticId, message);
            // Focus the input field
            if (elements.messageInput) elements.messageInput.focus();
        })
        .catch(error => {
            console.error('Error sending message:', error);
            showError('فشل إرسال الرسالة. حاول مرة أخرى.');
            // Mark the optimistic message as failed
            markMessageAsFailed(optimisticId);
        })
        .finally(() => {
            if (submitButton) {
                submitButton.disabled = false;
            }
        });
    });

    // Add click event listeners for chat items
    document.querySelectorAll('.chat-item').forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            if (userId && userName) loadChat(userId, userName);
        });
    });

    // Add click event listeners for user items
    document.querySelectorAll('.user-item').forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            if (userId && userName) loadChat(userId, userName);
        });
    });

    // Toggle chat list on mobile
    document.querySelector('.chat-sidebar-header')?.addEventListener('click', function() {
        elements.chatList?.classList.toggle('active');
    });

    // Auto-load chat with manager if available
    if (chatConfig.managerData?.id && chatConfig.managerData?.name) {
        loadChat(chatConfig.managerData.id, chatConfig.managerData.name);
    }

    // Handle tab visibility changes
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // When tab is hidden, slow down polling
            stopMessagePolling();
            messagePollingInterval = setInterval(() => {
                if (!isPollingPaused) {
                    fetchMessages(false);
                }
            }, POLLING_INTERVAL * 2);
        } else {
            // When tab is visible again, resume normal polling
            stopMessagePolling();
            startMessagePolling();

            // Immediately fetch messages when coming back
            if (currentChatId) {
                fetchMessages(false);
            }
        }
    });

    debugLog('Chat initialization complete');
}

// Start initialization when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeChat);
} else {
    initializeChat();
}
