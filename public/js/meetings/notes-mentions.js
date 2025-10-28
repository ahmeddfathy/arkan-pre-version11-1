class MeetingNotesMentionSystem {
    constructor(meetingId) {
        this.meetingId = meetingId;
        this.textarea = document.getElementById('content');
        this.meetingParticipants = [];
        this.mentionDropdown = null;
        this.currentMentionIndex = -1;
        this.mentionStartIndex = -1;
        this.mentionQuery = '';
        
        if (this.textarea) {
            this.init();
        }
    }

    init() {
        this.fetchMeetingParticipants();
        this.bindEvents();
        this.injectCSS();
    }

    fetchMeetingParticipants() {
        // استخدام البيانات المُمررة من الصفحة
        if (window.meetingParticipants) {
            this.meetingParticipants = window.meetingParticipants;
        } else {
            // Fallback: استخراج من المشاركين المعروضين في الصفحة
            this.parseMeetingParticipantsFromPage();
        }
    }

    parseMeetingParticipantsFromPage() {
        // استخراج البيانات من جدول المشاركين في صفحة الاجتماع
        const participantsTable = document.querySelector('.arkan-meeting-section table');
        if (participantsTable) {
            const rows = participantsTable.querySelectorAll('tbody tr');
            this.meetingParticipants = Array.from(rows).map(row => {
                const nameCell = row.querySelector('td:first-child');
                if (nameCell) {
                    const name = nameCell.textContent.trim();
                    return {
                        id: Math.random(), // سنحتاج لتحسين هذا
                        name: name,
                        email: '',
                        avatar: '/avatars/man.gif'
                    };
                }
            }).filter(Boolean);
        }
    }

    bindEvents() {
        this.textarea.addEventListener('input', (e) => this.handleInput(e));
        this.textarea.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        // إخفاء الـ dropdown عند النقر خارجه
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.mention-dropdown') && e.target !== this.textarea) {
                this.hideMentionDropdown();
            }
        });
    }

    handleInput(e) {
        const cursorPosition = this.textarea.selectionStart;
        const textBeforeCursor = this.textarea.value.substring(0, cursorPosition);
        
        // البحث عن آخر @ قبل المؤشر
        const lastAtIndex = textBeforeCursor.lastIndexOf('@');
        
        if (lastAtIndex !== -1) {
            const textAfterAt = textBeforeCursor.substring(lastAtIndex + 1);
            
            // التحقق من عدم وجود مسافة بعد @
            if (!textAfterAt.includes(' ') && !textAfterAt.includes('\n')) {
                this.mentionStartIndex = lastAtIndex;
                this.mentionQuery = textAfterAt;
                this.showMentionDropdown(this.mentionQuery, this.textarea);
                return;
            }
        }
        
        this.hideMentionDropdown();
    }

    handleKeydown(e) {
        if (!this.mentionDropdown || this.mentionDropdown.style.display === 'none') {
            return;
        }

        const items = this.mentionDropdown.querySelectorAll('.mention-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.currentMentionIndex = Math.min(this.currentMentionIndex + 1, items.length - 1);
                this.updateActiveItem();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.currentMentionIndex = Math.max(this.currentMentionIndex - 1, 0);
                this.updateActiveItem();
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.currentMentionIndex >= 0 && items[this.currentMentionIndex]) {
                    this.selectMention(items[this.currentMentionIndex]);
                }
                break;
                
            case 'Escape':
                e.preventDefault();
                this.hideMentionDropdown();
                break;
        }
    }

    showMentionDropdown(query, textarea) {
        let filteredParticipants = [];

        // إضافة خيارات الجميع في المقدمة
        if (query === '' || 'everyone'.includes(query.toLowerCase()) || 'الجميع'.includes(query)) {
            filteredParticipants.push({
                id: 'everyone',
                name: 'everyone',
                email: 'منشن جميع المشاركين في الاجتماع',
                avatar: '/avatars/man.gif',
                isEveryone: true
            });

            filteredParticipants.push({
                id: 'الجميع',
                name: 'الجميع',
                email: 'منشن جميع المشاركين في الاجتماع',
                avatar: '/avatars/man.gif',
                isEveryone: true
            });
        }

        // إضافة المشاركين المطابقين للبحث
        const regularParticipants = this.meetingParticipants.filter(participant =>
            participant.name.toLowerCase().includes(query.toLowerCase())
        );

        filteredParticipants = filteredParticipants.concat(regularParticipants);

        if (filteredParticipants.length === 0) {
            this.hideMentionDropdown();
            return;
        }

        // إنشاء أو تحديث الـ dropdown
        if (!this.mentionDropdown) {
            this.createMentionDropdown();
        }

        this.populateMentionDropdown(filteredParticipants);
        this.positionDropdown(textarea);
        this.mentionDropdown.style.display = 'block';
        this.currentMentionIndex = 0;
        this.updateActiveItem();
    }

    createMentionDropdown() {
        this.mentionDropdown = document.createElement('div');
        this.mentionDropdown.className = 'mention-dropdown';
        document.body.appendChild(this.mentionDropdown);
    }

    populateMentionDropdown(participants) {
        this.mentionDropdown.innerHTML = '';

        participants.forEach((participant, index) => {
            const item = document.createElement('div');
            item.className = 'mention-item';
            if (participant.isEveryone) item.classList.add('mention-everyone');
            if (index === 0) item.classList.add('active');

            item.style.cssText = `
                display: flex;
                align-items: center;
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
                transition: background-color 0.2s;
            `;

            const avatarSrc = participant.isEveryone ? '/avatars/man.gif' : participant.avatar;
            const displayName = participant.isEveryone ? `👥 ${participant.name}` : participant.name;

            item.innerHTML = `
                <img src="${avatarSrc}" alt="${participant.name}"
                     style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-left: 8px;">
                <div>
                    <div style="font-weight: 500; color: #333;">${displayName}</div>
                    <div style="font-size: 12px; color: #666;">${participant.email}</div>
                </div>
            `;

            item.addEventListener('click', () => this.selectMention(item));
            item.setAttribute('data-user-name', participant.name);
            item.setAttribute('data-user-id', participant.id);
            item.setAttribute('data-is-everyone', participant.isEveryone || false);

            this.mentionDropdown.appendChild(item);
        });
    }

    positionDropdown(textarea) {
        const rect = textarea.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        this.mentionDropdown.style.position = 'absolute';
        this.mentionDropdown.style.left = rect.left + 'px';
        this.mentionDropdown.style.top = (rect.bottom + scrollTop + 5) + 'px';
        this.mentionDropdown.style.width = Math.max(rect.width, 300) + 'px';
        this.mentionDropdown.style.zIndex = '9999';
    }

    updateActiveItem() {
        const items = this.mentionDropdown.querySelectorAll('.mention-item');
        items.forEach((item, index) => {
            if (index === this.currentMentionIndex) {
                item.classList.add('active');
                item.style.backgroundColor = '#f0f0f0';
            } else {
                item.classList.remove('active');
                item.style.backgroundColor = '';
            }
        });
    }

    selectMention(item) {
        const userName = item.getAttribute('data-user-name');
        const isEveryone = item.getAttribute('data-is-everyone') === 'true';
        
        // استبدال المنشن في النص
        const beforeMention = this.textarea.value.substring(0, this.mentionStartIndex);
        const afterMention = this.textarea.value.substring(this.textarea.selectionStart);
        
        const newValue = beforeMention + '@' + userName + ' ' + afterMention;
        this.textarea.value = newValue;
        
        // وضع المؤشر بعد المنشن
        const newCursorPosition = this.mentionStartIndex + userName.length + 2;
        this.textarea.setSelectionRange(newCursorPosition, newCursorPosition);
        
        this.hideMentionDropdown();
        this.textarea.focus();
    }

    hideMentionDropdown() {
        if (this.mentionDropdown) {
            this.mentionDropdown.style.display = 'none';
        }
        this.currentMentionIndex = -1;
        this.mentionStartIndex = -1;
        this.mentionQuery = '';
    }

    injectCSS() {
        const style = document.createElement('style');
        style.textContent = `
            .mention-dropdown {
                background: white;
                border: 1px solid #ddd;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-height: 200px;
                overflow-y: auto;
                display: none;
            }

            .mention-item:hover {
                background-color: #f0f0f0 !important;
            }

            .mention-item.active {
                background-color: #e3f2fd !important;
            }

            .mention-everyone {
                background-color: #fff3e0 !important;
                border-left: 3px solid #ff9800;
            }

            .mention-everyone:hover {
                background-color: #ffe0b2 !important;
            }
        `;
        document.head.appendChild(style);
    }
}

// تشغيل النظام عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    if (window.meetingId) {
        new MeetingNotesMentionSystem(window.meetingId);
    }
});
