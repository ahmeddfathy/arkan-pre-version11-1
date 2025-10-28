/**
 * Task Sidebar Main File
 * This file loads all the task sidebar modules
 */

// Helper function to load a script and return a promise
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.head.appendChild(script);
    });
}

// Load all task sidebar modules sequentially
async function loadTaskSidebarModules() {
    console.log('🔄 Loading task sidebar modules...');

    try {
        // Check if core module is already loaded
        if (typeof openTaskSidebar === 'undefined') {
            // Load core functionality first (most important)
            await loadScript('/js/projects/task-sidebar/core.js');
            console.log('✅ Core module loaded');
        } else {
            console.log('✅ Core module already loaded');
        }

        // Export core functions (make sure they're available globally)
        if (typeof openTaskSidebar !== 'undefined') {
            window.openTaskSidebar = openTaskSidebar;
            window.closeTaskSidebar = closeTaskSidebar;
            window.loadTaskDetails = loadTaskDetails;
            window.showError = showError;
            window.showWarning = showWarning;
            window.canUserStartTask = canUserStartTask;
            console.log('✅ Core functions exported to window');
        }

        // Load other modules in parallel (they're not critical for initial load)
        const modulePromises = [
            loadScript('/js/projects/task-sidebar/display.js').then(() => {
                console.log('✅ Display module loaded');
                if (typeof displayTaskDetails !== 'undefined') {
                    window.displayTaskDetails = displayTaskDetails;
                }
            }),
            loadScript('/js/projects/task-sidebar/notes.js').then(() => {
                console.log('✅ Notes module loaded');
                // Export notes functions
                if (typeof loadTaskNotes !== 'undefined') {
                    window.loadTaskNotes = loadTaskNotes;
                    window.displayNotes = displayNotes;
                    window.showAddNoteForm = showAddNoteForm;
                    window.hideAddNoteForm = hideAddNoteForm;
                    window.saveNote = saveNote;
                    window.editNote = editNote;
                    window.cancelEditNote = cancelEditNote;
                    window.updateNote = updateNote;
                    window.deleteNote = deleteNote;
                    window.showNotesError = showNotesError;
                }
            }),
            loadScript('/js/projects/task-sidebar/revisions.js').then(() => {
                console.log('✅ Revisions module loaded');
                // Export revisions functions
                if (typeof loadTaskRevisions !== 'undefined') {
                    window.loadTaskRevisions = loadTaskRevisions;
                    window.displayRevisions = displayRevisions;
                    window.showAddRevisionForm = showAddRevisionForm;
                    window.hideAddRevisionForm = hideAddRevisionForm;
                    window.toggleRevisionAttachmentType = toggleRevisionAttachmentType;
                    window.saveRevision = saveRevision;
                    window.deleteRevision = deleteRevision;
                    window.showRevisionsError = showRevisionsError;
                }
            }),
            loadScript('/js/projects/task-sidebar/attachments.js').then(() => {
                console.log('✅ Attachments module loaded');
                // Export attachments functions
                if (typeof loadTaskAttachments !== 'undefined') {
                    window.loadTaskAttachments = loadTaskAttachments;
                    window.initializeAttachmentHandlers = initializeAttachmentHandlers;
                    window.handleFileUpload = handleFileUpload;
                    window.uploadSingleFile = uploadSingleFile;
                    window.viewAttachment = viewAttachment;
                    window.downloadAttachment = downloadAttachment;
                    window.deleteAttachment = deleteAttachment;
                    window.formatFileSize = formatFileSize;
                    window.formatTimeAgo = formatTimeAgo;
                    window.showToast = showToast;
                }
            }),
            loadScript('/js/projects/task-sidebar/timer.js').then(() => {
                console.log('✅ Timer module loaded');
                // Export timer functions
                if (typeof startSidebarTimer !== 'undefined') {
                    window.startSidebarTimer = startSidebarTimer;
                    window.updateSidebarTimerDisplay = updateSidebarTimerDisplay;
                    window.stopSidebarTimer = stopSidebarTimer;
                    window.startTask = startTask;
                    window.resumeTask = resumeTask;
                }
            }),
            loadScript('/js/projects/task-sidebar/items.js').then(() => {
                console.log('✅ Items module loaded');
                // Export items functions
                if (typeof loadTaskItems !== 'undefined') {
                    window.loadTaskItems = loadTaskItems;
                    window.showAddItemForm = showAddItemForm;
                    window.hideAddItemForm = hideAddItemForm;
                    window.saveItem = saveItem;
                    window.editItem = editItem;
                    window.updateItem = updateItem;
                    window.deleteItem = deleteItem;
                }
            })
        ];

        await Promise.all(modulePromises);

        // Export utility functions if available
        if (typeof escapeHtml !== 'undefined') {
            window.escapeHtml = escapeHtml;
        }
        if (typeof formatDateTime !== 'undefined') {
            window.formatDateTime = formatDateTime;
        }

        console.log('✅ All task sidebar modules loaded successfully');

        // Debug: Log when functions are available
        console.log('✅ Task sidebar functions available:', {
            openTaskSidebar: typeof window.openTaskSidebar,
            closeTaskSidebar: typeof window.closeTaskSidebar,
            loadTaskDetails: typeof window.loadTaskDetails,
            displayTaskDetails: typeof window.displayTaskDetails
        });

    } catch (error) {
        console.error('❌ Error loading task sidebar modules:', error);
    }
}

// Load modules when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadTaskSidebarModules);
} else {
    // DOM is already loaded, execute immediately
    loadTaskSidebarModules();
}
