// ============================================
// MAIN JAVASCRIPT FOR SAAS SYSTEM
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize data tables
    initDataTables();
});

// ----- Tooltips -----
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
                pointer-events: none;
            `;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width/2 - tooltip.offsetWidth/2 + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            
            this.addEventListener('mouseleave', function() {
                tooltip.remove();
            });
        });
    });
}

// ----- Form Validation -----
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const required = this.querySelectorAll('[required]');
            let valid = true;
            
            required.forEach(field => {
                if(!field.value.trim()) {
                    field.style.borderColor = '#e74c3c';
                    valid = false;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if(!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
}

// ----- Data Tables -----
function initDataTables() {
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th');
        headers.forEach((th, index) => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', function() {
                sortTable(table, index);
            });
        });
    });
}

// ----- Table Sorting -----
function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isNumeric = !isNaN(rows[0].children[column].textContent.replace(/,/g, ''));
    
    rows.sort((a, b) => {
        const aVal = a.children[column].textContent.trim();
        const bVal = b.children[column].textContent.trim();
        
        if(isNumeric) {
            return parseFloat(aVal.replace(/,/g, '')) - parseFloat(bVal.replace(/,/g, ''));
        }
        return aVal.localeCompare(bVal);
    });
    
    // Toggle sort order
    if(table.dataset.sortDirection === 'asc') {
        rows.reverse();
        table.dataset.sortDirection = 'desc';
    } else {
        table.dataset.sortDirection = 'asc';
    }
    
    rows.forEach(row => tbody.appendChild(row));
}

// ----- Search Filter -----
function filterTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    if(!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm.toLowerCase()) ? '' : 'none';
    });
}

// ----- Modal -----
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// ----- Confirmation Dialog -----
function confirmAction(message, callback) {
    if(confirm(message)) {
        callback();
    }
}

// ----- AJAX Helper -----
function ajaxRequest(url, method, data, successCallback, errorCallback) {
    fetch(url, {
        method: method || 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
        body: data ? JSON.stringify(data) : null
    })
    .then(response => response.json())
    .then(data => {
        if(successCallback) successCallback(data);
    })
    .catch(error => {
        if(errorCallback) errorCallback(error);
    });
}

// ----- Copy to Clipboard -----
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    }, function() {
        // Fallback
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
        alert('Copied to clipboard!');
    });
}

// ----- Print Function -----
function printPage() {
    window.print();
}

// ----- Date Formatting -----
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PK', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// ----- Currency Formatting -----
function formatCurrency(amount) {
    return '$ ' + parseFloat(amount).toFixed(2);
}

// ----- Status Color Helper -----
function getStatusColor(status) {
    const colors = {
        'active': '#27ae60',
        'inactive': '#e74c3c',
        'pending': '#f39c12',
        'trial': '#3498db',
        'paid': '#27ae60',
        'unpaid': '#e74c3c',
        'open': '#3498db',
        'in_progress': '#f39c12',
        'resolved': '#27ae60',
        'closed': '#95a5a6'
    };
    return colors[status] || '#95a5a6';
}