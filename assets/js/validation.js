// Form validation functions
document.addEventListener('DOMContentLoaded', function() {
    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', validateLoginForm);
    }
    
    // Registration form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', validateRegisterForm);
    }
    
    // Real-time validation for registration form
    if (registerForm) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const email = document.getElementById('email');
        
        if (password) {
            password.addEventListener('input', validatePassword);
        }
        
        if (confirmPassword) {
            confirmPassword.addEventListener('input', validateConfirmPassword);
        }
        
        if (email) {
            email.addEventListener('input', validateEmail);
        }
    }
});

function validateLoginForm(e) {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous error messages
    clearErrors();
    
    // Email validation
    if (!email) {
        showError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    // Password validation
    if (!password) {
        showError('password', 'Password is required');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        showAlert('Please correct the errors above', 'error');
    }
}

function validateRegisterForm(e) {
    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const role = document.getElementById('role').value;
    
    let isValid = true;
    
    // Clear previous error messages
    clearErrors();
    
    // First name validation
    if (!firstName) {
        showError('first_name', 'First name is required');
        isValid = false;
    } else if (firstName.length < 2) {
        showError('first_name', 'First name must be at least 2 characters');
        isValid = false;
    }
    
    // Last name validation
    if (!lastName) {
        showError('last_name', 'Last name is required');
        isValid = false;
    } else if (lastName.length < 2) {
        showError('last_name', 'Last name must be at least 2 characters');
        isValid = false;
    }
    
    // Email validation
    if (!email) {
        showError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    // Role validation
    if (!role) {
        showError('role', 'Please select a role');
        isValid = false;
    }
    
    // Password validation
    if (!password) {
        showError('password', 'Password is required');
        isValid = false;
    } else if (password.length < 6) {
        showError('password', 'Password must be at least 6 characters');
        isValid = false;
    }
    
    // Confirm password validation
    if (!confirmPassword) {
        showError('confirm_password', 'Please confirm your password');
        isValid = false;
    } else if (password !== confirmPassword) {
        showError('confirm_password', 'Passwords do not match');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        showAlert('Please correct the errors above', 'error');
    }
}

function validatePassword() {
    const password = document.getElementById('password');
    const passwordValue = password.value;
    
    clearFieldError('password');
    
    if (passwordValue && passwordValue.length < 6) {
        showError('password', 'Password must be at least 6 characters');
    }
}

function validateConfirmPassword() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password');
    const confirmPasswordValue = confirmPassword.value;
    
    clearFieldError('confirm_password');
    
    if (confirmPasswordValue && password !== confirmPasswordValue) {
        showError('confirm_password', 'Passwords do not match');
    }
}

function validateEmail() {
    const email = document.getElementById('email');
    const emailValue = email.value.trim();
    
    clearFieldError('email');
    
    if (emailValue && !isValidEmail(emailValue)) {
        showError('email', 'Please enter a valid email address');
    }
}

// Utility functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
        field.style.borderColor = '#e74c3c';
    }
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        const errorDiv = field.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
        field.style.borderColor = '#e0e0e0';
    }
}

function clearErrors() {
    const errorDivs = document.querySelectorAll('.field-error');
    errorDivs.forEach(div => div.remove());
    
    const inputs = document.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.style.borderColor = '#e0e0e0';
    });
}

function showAlert(message, type) {
    // Remove existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    // Insert alert at the top of the form
    const form = document.querySelector('form');
    if (form) {
        form.parentNode.insertBefore(alertDiv, form);
    }
}

// Dashboard functionality
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    }
}

// Add mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.createElement('button');
    mobileMenuToggle.className = 'mobile-menu-toggle';
    mobileMenuToggle.innerHTML = '☰';
    mobileMenuToggle.style.display = 'none';
    mobileMenuToggle.style.position = 'fixed';
    mobileMenuToggle.style.top = '20px';
    mobileMenuToggle.style.left = '20px';
    mobileMenuToggle.style.zIndex = '1000';
    mobileMenuToggle.style.background = '#2c3e50';
    mobileMenuToggle.style.color = 'white';
    mobileMenuToggle.style.border = 'none';
    mobileMenuToggle.style.padding = '10px';
    mobileMenuToggle.style.borderRadius = '5px';
    mobileMenuToggle.style.cursor = 'pointer';
    
    mobileMenuToggle.addEventListener('click', toggleSidebar);
    
    // Add to dashboard if it exists
    const dashboard = document.querySelector('.dashboard-container');
    if (dashboard) {
        document.body.appendChild(mobileMenuToggle);
        
        // Show/hide based on screen size
        function checkScreenSize() {
            if (window.innerWidth <= 768) {
                mobileMenuToggle.style.display = 'block';
            } else {
                mobileMenuToggle.style.display = 'none';
            }
        }
        
        checkScreenSize();
        window.addEventListener('resize', checkScreenSize);
    }
}); 