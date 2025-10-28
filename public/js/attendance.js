// Attendance Management JavaScript

/**
 * Updates the hidden employee_filter field with the selected employee ID
 * This function is called when the employee dropdown selection changes
 * @param {HTMLInputElement} input - The employee search input field
 */
function updateEmployeeFilter(input) {
    const selectedName = input.value;
    const employeesList = document.getElementById('employees-list');
    const options = employeesList.getElementsByTagName('option');
    const hiddenInput = document.getElementById('employee_filter');

    // Reset hidden input if no name is entered
    if (!selectedName) {
        hiddenInput.value = '';
        return;
    }

    // Find the option with the matching name and get its data-value
    for (let i = 0; i < options.length; i++) {
        if (options[i].value === selectedName) {
            const employeeId = options[i].getAttribute('data-value');
            hiddenInput.value = employeeId;

            // Verify if the value is set correctly
            console.log("Selected employee ID:", employeeId);
            return;
        }
    }

    // If no match found, clear the hidden input
    hiddenInput.value = '';
}

/**
 * Initialize event listeners when the document is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log("Attendance JS loaded");

    // Prevent the form from auto-submitting when selecting from datalist
    const employeeSearch = document.getElementById('employee-search');
    if (employeeSearch) {
        console.log("Employee search input found");

        // Ensure the employee filter is initialized on page load
        if (employeeSearch.value) {
            updateEmployeeFilter(employeeSearch);
        }

        employeeSearch.addEventListener('input', function(e) {
            console.log("Input changed:", this.value);
            // Only update, don't submit the form automatically
            updateEmployeeFilter(this);
        });

        // Handle the change event for selections from datalist
        employeeSearch.addEventListener('change', function(e) {
            console.log("Selection changed:", this.value);
            updateEmployeeFilter(this);
        });
    }

    // Initialize the form submit button
    const form = document.getElementById('filter-form');
    if (form) {
        console.log("Form found");
        form.addEventListener('submit', function(e) {
            const hiddenInput = document.getElementById('employee_filter');
            const employeeSearch = document.getElementById('employee-search');

            console.log("Submitting form with employee ID:", hiddenInput.value);

            // If the user typed a name but it doesn't match any employee, show a message
            if (employeeSearch.value && !hiddenInput.value) {
                alert('الرجاء اختيار اسم موظف من القائمة');
                e.preventDefault();
            }
        });
    }

    // Initialize statistics cards if they exist
    initializeStatisticsCards();
});

/**
 * Initialize and animate statistics cards
 * Makes sure the statistics section is visible and properly animated
 */
function initializeStatisticsCards() {
    console.log("Initializing statistics cards");
    const statisticsSection = document.querySelector('.statistics-section');
    if (!statisticsSection) {
        console.log("No statistics section found");
        return;
    }

    console.log("Statistics section found");

    // Make sure the statistics section is visible
    statisticsSection.style.display = 'block';

    // Add animation classes to cards
    const cards = statisticsSection.querySelectorAll('.card');
    cards.forEach((card, index) => {
        // Stagger animation for a nice effect
        setTimeout(() => {
            card.classList.add('animate-in');
        }, index * 100);
    });

    // Make sure percentage values are formatted properly
    const percentageElements = statisticsSection.querySelectorAll('.text-success');
    percentageElements.forEach(element => {
        if (element.textContent.includes('%')) {
            const value = parseFloat(element.textContent);
            if (!isNaN(value)) {
                element.textContent = value.toFixed(1) + '%';
            }
        }
    });
}
