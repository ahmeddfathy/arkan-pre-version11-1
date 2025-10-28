// Common chart options and colors
const colors = {
    approved: { bg: 'rgba(40, 167, 69, 0.2)', border: '#28a745' },
    pending: { bg: 'rgba(255, 193, 7, 0.2)', border: '#ffc107' },
    rejected: { bg: 'rgba(220, 53, 69, 0.2)', border: '#dc3545' },
    onTime: { bg: 'rgba(23, 162, 184, 0.2)', border: '#17a2b4' },
    late: { bg: 'rgba(108, 117, 125, 0.2)', border: '#6c757d' }
};

const commonPieOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        }
    }
};

const commonBarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top'
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
};
