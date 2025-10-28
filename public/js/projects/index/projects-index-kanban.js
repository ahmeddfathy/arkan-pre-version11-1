/**
 * Projects Index Kanban Board JavaScript
 * Handles kanban board functionality for projects index page
 */

// Enhanced Projects Index Kanban Board Functionality
function initializeProjectsIndexKanbanBoard() {
    const kanbanCards = document.querySelectorAll('.projects-index-kanban-card');
    const kanbanColumns = document.querySelectorAll('#kanbanView .projects-index-kanban-cards');

    // Add drag functionality to project cards
    kanbanCards.forEach(card => {
        card.draggable = true;

        card.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.projectId);
            e.dataTransfer.setData('text/status', this.dataset.status);
            this.classList.add('dragging');
        });

        card.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
        });

        // Card click handler (exclude action buttons)
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.projects-index-kanban-card-actions') &&
                !e.target.closest('form')) {
                const projectId = this.dataset.projectId;
                window.location.href = `/projects/${projectId}`;
            }
        });
    });

    // Add drop functionality to columns
    kanbanColumns.forEach(column => {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        column.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });

        column.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            const projectId = e.dataTransfer.getData('text/plain');
            const oldStatus = e.dataTransfer.getData('text/status');
            const newStatus = this.dataset.status;

            if (oldStatus !== newStatus) {
                // Show confirmation using SweetAlert if available, otherwise use confirm
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'تأكيد النقل',
                        text: `هل تريد نقل المشروع إلى "${newStatus}"؟`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3b82f6',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'نعم، انقل',
                        cancelButtonText: 'إلغاء'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            console.log(`Moving project ${projectId} from ${oldStatus} to ${newStatus}`);

                            Swal.fire({
                                title: 'تم النقل!',
                                text: `تم نقل المشروع إلى "${newStatus}" بنجاح`,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    });
                } else {
                    if (confirm(`هل تريد نقل المشروع إلى "${newStatus}"؟`)) {
                        console.log(`Moving project ${projectId} from ${oldStatus} to ${newStatus}`);
                        alert(`تم نقل المشروع إلى "${newStatus}" بنجاح`);
                    }
                }
            }
        });
    });

    console.log('✅ Projects Index Kanban Board initialized');
}

// Initialize kanban when switching to kanban view
document.addEventListener('DOMContentLoaded', function() {
    const kanbanViewBtn = document.getElementById('kanbanViewBtn');

    if (kanbanViewBtn) {
        kanbanViewBtn.addEventListener('click', function() {
            setTimeout(() => {
                initializeProjectsIndexKanbanBoard();
            }, 200);
        });
    }
});
