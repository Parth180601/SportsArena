// Initialize Chart.js
let turfDistributionChart;

// Function to fetch and update dashboard statistics
async function updateDashboardStats() {
    try {
        const response = await fetch('get_admin_stats.php');
        if (!response.ok) {
            throw new Error('Failed to fetch statistics');
        }
        
        const data = await response.json();
        
        // Update statistics cards
        document.getElementById('total-bookings').textContent = data.total_bookings;
        document.getElementById('occupancy-rate').textContent = `${data.occupancy_rate}%`;
        
        // Update turf distribution chart
        updateTurfDistributionChart(data.turf_distribution);
        
        // Update recent bookings table
        updateRecentBookingsTable(data.recent_bookings);
        
    } catch (error) {
        console.error('Error updating dashboard:', error);
        showErrorAlert('Failed to update dashboard statistics');
    }
}

// Function to update the turf distribution chart
function updateTurfDistributionChart(turfData) {
    const labels = turfData.map(item => item.location_name);
    const counts = turfData.map(item => item.turf_count);
    
    if (turfDistributionChart) {
        turfDistributionChart.destroy();
    }
    
    const ctx = document.getElementById('turf-distribution-chart').getContext('2d');
    turfDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Turfs',
                data: counts,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Turf Distribution by Location'
                }
            }
        }
    });
}

// Function to update the recent bookings table
function updateRecentBookingsTable(bookings) {
    const tableBody = document.getElementById('recent-bookings-body');
    tableBody.innerHTML = '';
    
    bookings.forEach(booking => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(booking.booking_date)}</td>
            <td>${booking.time_slot}</td>
            <td>${booking.location_name}</td>
            <td>${booking.turf_name}</td>
            <td>${booking.username}</td>
            <td>
                <span class="badge ${getPaymentStatusClass(booking.payment_status)}">
                    ${booking.payment_status}
                </span>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Helper function to format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Helper function to get payment status badge class
function getPaymentStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'paid':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Function to show error alert
function showErrorAlert(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.querySelector('.main-content').prepend(alertDiv);
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    // Initial update
    updateDashboardStats();
    
    // Set up refresh button
    document.getElementById('refresh-stats').addEventListener('click', updateDashboardStats);
    
    // Set up auto-refresh every 5 minutes
    setInterval(updateDashboardStats, 5 * 60 * 1000);
}); 