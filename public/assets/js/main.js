// Form validation functions
function validateRegistrationForm() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
    }
    return true;
}

// Password strength checker
function checkPasswordStrength(password) {
    const strengthBar = document.querySelector('.password-strength-bar');
    if (!strengthBar) return;
    
    // Remove previous classes
    strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
    
    if (password.length === 0) {
        strengthBar.style.width = '0';
        return;
    }
    
    // Calculate strength based on length, character variety, etc.
    let strength = 0;
    
    // Length
    if (password.length >= 8) strength += 1;
    if (password.length >= 12) strength += 1;
    
    // Character variety
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[a-z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    // Calculate percentage (max score is 6)
    const percentage = Math.min(100, Math.floor((strength / 6) * 100));
    
    // Update strength bar
    if (percentage <= 33) {
        strengthBar.classList.add('strength-weak');
    } else if (percentage <= 66) {
        strengthBar.classList.add('strength-medium');
    } else {
        strengthBar.classList.add('strength-strong');
    }
    
    strengthBar.style.width = percentage + '%';
}

// Image preview for registration
function previewImage(input) {
    const preview = document.getElementById('profile-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize any elements that need it
document.addEventListener('DOMContentLoaded', function() {
    // Rating stars functionality
    const stars = document.querySelectorAll('.stars input');
    if (stars.length) {
        stars.forEach(star => {
            star.addEventListener('change', function() {
                document.getElementById('rating-value').value = this.value;
            });
        });
    }
}); 