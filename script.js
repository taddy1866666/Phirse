document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('error-message');

    // Check for error message in URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    if (error) {
        showError(decodeURIComponent(error));
    }

    loginForm.addEventListener('submit', function(e) {
        const userId = document.getElementById('user_id').value.trim();
        const password = document.getElementById('password').value.trim();

        if (!userId || !password) {
            e.preventDefault();
            showError('Please fill in all fields.');
            return;
        }

        if (userId.length < 3) {
            e.preventDefault();
            showError('User ID must be at least 3 characters long.');
            return;
        }

        if (password.length < 6) {
            e.preventDefault();
            showError('Password must be at least 6 characters long.');
            return;
        }

        hideError();
    });

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.add('show');
    }

    function hideError() {
        errorMessage.textContent = '';
        errorMessage.classList.remove('show');
    }

    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            hideError();
        });
    });
});

function handleLoginResponse(success, message) {
    const errorMessage = document.getElementById('error-message');
    
    if (success) {
        window.location.href = 'dashboard.php';
    } else {
        errorMessage.textContent = message;
        errorMessage.classList.add('show');
    }
}