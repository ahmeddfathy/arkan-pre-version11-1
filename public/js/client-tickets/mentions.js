/**
 * نظام المنشن في تعليقات التذاكر
 * Ticket Comments Mention System
 */

class TicketMentionSystem {
    constructor(ticketId) {
        this.ticketId = ticketId;
        this.teamMembers = [];
        this.mentionDropdown = null;
        this.currentTextarea = null;
        this.currentCaretPosition = 0;

        this.init();
    }

    async init() {
        await this.loadTeamMembers();
        this.setupEventListeners();
        this.createMentionDropdown();
    }

    async loadTeamMembers() {
        try {
            const response = await fetch(`/client-tickets/${this.ticketId}/team-members`);
            if (response.ok) {
                this.teamMembers = await response.json();
                console.log('Team members loaded:', this.teamMembers);
            } else {
                console.error('Failed to load team members');
            }
        } catch (error) {
            console.error('Error loading team members:', error);
        }
    }

    setupEventListeners() {
        // استمع للتغييرات في textarea التعليقات
        document.addEventListener('input', (e) => {
            if (e.target.matches('textarea[name="comment"]')) {
                this.handleTextareaInput(e);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.target.matches('textarea[name="comment"]')) {
                this.handleTextareaKeydown(e);
            }
        });

        // إغلاق القائمة عند النقر خارجها
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.mention-dropdown') && !e.target.matches('textarea[name="comment"]')) {
                this.hideMentionDropdown();
            }
        });
    }

    handleTextareaInput(e) {
        this.currentTextarea = e.target;
        const text = e.target.value;
        const caretPosition = e.target.selectionStart;

        // البحث عن @ في النص
        const beforeCaret = text.substring(0, caretPosition);
        const mentionMatch = beforeCaret.match(/@(\w*)$/);

        if (mentionMatch) {
            const query = mentionMatch[1];
            this.currentCaretPosition = caretPosition;
            this.showMentionDropdown(query, e.target);
        } else {
            this.hideMentionDropdown();
        }
    }

    handleTextareaKeydown(e) {
        if (this.mentionDropdown && this.mentionDropdown.style.display === 'block') {
            const items = this.mentionDropdown.querySelectorAll('.mention-item');
            const activeItem = this.mentionDropdown.querySelector('.mention-item.active');
            let currentIndex = Array.from(items).indexOf(activeItem);

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    currentIndex = (currentIndex + 1) % items.length;
                    this.highlightMentionItem(items[currentIndex]);
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    currentIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                    this.highlightMentionItem(items[currentIndex]);
                    break;

                case 'Enter':
                case 'Tab':
                    if (activeItem) {
                        e.preventDefault();
                        this.selectMention(activeItem);
                    }
                    break;

                case 'Escape':
                    e.preventDefault();
                    this.hideMentionDropdown();
                    break;
            }
        }
    }

        showMentionDropdown(query, textarea) {
        let filteredMembers = [];

        // إضافة خيارات الجميع في المقدمة
        if (query === '' || 'everyone'.includes(query.toLowerCase()) || 'الجميع'.includes(query)) {
            filteredMembers.push({
                id: 'everyone',
                name: 'everyone',
                email: 'منشن جميع أعضاء الفريق',
                avatar: '/avatars/everyone.png',
                isEveryone: true
            });

            filteredMembers.push({
                id: 'الجميع',
                name: 'الجميع',
                email: 'منشن جميع أعضاء الفريق',
                avatar: '/avatars/everyone.png',
                isEveryone: true
            });
        }

        // إضافة الأعضاء المطابقين للبحث
        const regularMembers = this.teamMembers.filter(member =>
            member.name.toLowerCase().includes(query.toLowerCase())
        );

        filteredMembers = filteredMembers.concat(regularMembers);

        if (filteredMembers.length === 0) {
            this.hideMentionDropdown();
            return;
        }

        // إنشاء أو تحديث القائمة
        if (!this.mentionDropdown) {
            this.createMentionDropdown();
        }

        this.populateMentionDropdown(filteredMembers);
        this.positionMentionDropdown(textarea);
        this.mentionDropdown.style.display = 'block';
    }

    createMentionDropdown() {
        this.mentionDropdown = document.createElement('div');
        this.mentionDropdown.className = 'mention-dropdown';
        this.mentionDropdown.style.cssText = `
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            min-width: 200px;
        `;
        document.body.appendChild(this.mentionDropdown);
    }

        populateMentionDropdown(members) {
        this.mentionDropdown.innerHTML = '';

        members.forEach((member, index) => {
            const item = document.createElement('div');
            item.className = 'mention-item';
            if (member.isEveryone) item.classList.add('mention-everyone');
            if (index === 0) item.classList.add('active');

            item.style.cssText = `
                padding: 10px 15px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: background-color 0.2s;
                ${member.isEveryone ? 'border-left: 3px solid #ffa726; background-color: #fff3e0;' : ''}
            `;

            const avatarSrc = member.isEveryone ? '/avatars/man.gif' : member.avatar;
            const displayName = member.isEveryone ? `👥 ${member.name}` : member.name;

            item.innerHTML = `
                <img src="${avatarSrc}" alt="${member.name}"
                     style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                <div>
                    <div style="font-weight: 500; color: #333;">${displayName}</div>
                    <div style="font-size: 12px; color: #666;">${member.email}</div>
                </div>
            `;

            item.addEventListener('click', () => this.selectMention(item));
            item.addEventListener('mouseenter', () => this.highlightMentionItem(item));

            item.setAttribute('data-user-id', member.id);
            item.setAttribute('data-user-name', member.name);
            item.setAttribute('data-is-everyone', member.isEveryone ? 'true' : 'false');

            this.mentionDropdown.appendChild(item);
        });
    }

    positionMentionDropdown(textarea) {
        const rect = textarea.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        this.mentionDropdown.style.left = (rect.left + scrollLeft) + 'px';
        this.mentionDropdown.style.top = (rect.bottom + scrollTop + 5) + 'px';
    }

    highlightMentionItem(item) {
        // إزالة التمييز من جميع العناصر
        this.mentionDropdown.querySelectorAll('.mention-item').forEach(i => {
            i.classList.remove('active');
            i.style.backgroundColor = '';
        });

        // تمييز العنصر المحدد
        item.classList.add('active');
        item.style.backgroundColor = '#f8f9fa';
    }

    selectMention(item) {
        const userName = item.getAttribute('data-user-name');
        const userId = item.getAttribute('data-user-id');

        if (!this.currentTextarea) return;

        const text = this.currentTextarea.value;
        const caretPosition = this.currentCaretPosition;

        // العثور على بداية المنشن
        const beforeCaret = text.substring(0, caretPosition);
        const mentionStart = beforeCaret.lastIndexOf('@');

        if (mentionStart !== -1) {
            // استبدال النص
            const beforeMention = text.substring(0, mentionStart);
            const afterCaret = text.substring(caretPosition);
            const newText = beforeMention + `@${userName} ` + afterCaret;

            this.currentTextarea.value = newText;

            // تحديد موضع المؤشر بعد المنشن
            const newCaretPosition = mentionStart + userName.length + 2;
            this.currentTextarea.setSelectionRange(newCaretPosition, newCaretPosition);

            // إخفاء القائمة
            this.hideMentionDropdown();

            // إعادة التركيز على textarea
            this.currentTextarea.focus();

            // إرسال حدث تغيير لتحديث أي مستمعين آخرين
            this.currentTextarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    hideMentionDropdown() {
        if (this.mentionDropdown) {
            this.mentionDropdown.style.display = 'none';
        }
    }

    // إضافة styles CSS
    static addStyles() {
        if (document.getElementById('mention-styles')) return;

        const style = document.createElement('style');
        style.id = 'mention-styles';
        style.textContent = `
            .mention-dropdown .mention-item:hover {
                background-color: #f8f9fa !important;
            }

            .mention-dropdown .mention-item.active {
                background-color: #e3f2fd !important;
            }

            .mention {
                background-color: #e3f2fd;
                color: #1976d2;
                padding: 2px 4px;
                border-radius: 3px;
                font-weight: 500;
            }

            .mention-everyone {
                background-color: #fff3e0 !important;
                color: #f57c00 !important;
                border: 1px solid #ffa726;
                font-weight: 600;
            }

            .mention-dropdown::-webkit-scrollbar {
                width: 6px;
            }

            .mention-dropdown::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 3px;
            }

            .mention-dropdown::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 3px;
            }

            .mention-dropdown::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }
        `;
        document.head.appendChild(style);
    }
}

// تهيئة النظام عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // إضافة الـ styles
    TicketMentionSystem.addStyles();

    // الحصول على ticket ID من البيانات أو الـ URL
    const ticketId = window.ticketId || document.querySelector('[data-ticket-id]')?.dataset.ticketId;

    if (ticketId) {
        window.ticketMentionSystem = new TicketMentionSystem(ticketId);
    }
});

// تصدير الكلاس للاستخدام الخارجي
window.TicketMentionSystem = TicketMentionSystem;
