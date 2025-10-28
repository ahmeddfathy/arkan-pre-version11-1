// File size warning and validation
function checkFileSize(file) {
    const maxSize = 50 * 1024 * 1024; // 50MB
    const warningSize = 10 * 1024 * 1024; // 10MB

    if (file.size > maxSize) {
        toastr.error(`الملف ${file.name} كبير جداً (${formatFileSize(file.size)}). الحد الأقصى 50 ميجابايت`, 'ملف كبير ❌');
        return false;
    }

    if (file.size > warningSize) {
        toastr.warning(`الملف ${file.name} كبير (${formatFileSize(file.size)}). قد يستغرق وقتاً أطول للرفع`, 'تحذير ⚠️');
    }

    return true;
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' بايت';
    else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' كيلوبايت';
    else return (bytes / 1048576).toFixed(1) + ' ميجابايت';
}

// Export functions if needed
window.checkFileSize = checkFileSize;
window.formatFileSize = formatFileSize;
