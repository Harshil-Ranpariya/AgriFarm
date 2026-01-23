document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('signupForm');
      const username = document.getElementById('username');
      const email = document.getElementById('email');
      const password = document.getElementById('password');
      const confirmPassword = document.getElementById('confirm-password');
      const userType = document.getElementById('userType');

      const usernameError = document.getElementById('usernameError');
      const emailError = document.getElementById('emailError');
      const passwordError = document.getElementById('passwordError');
      const confirmPasswordError = document.getElementById('confirmPasswordError');
      const userTypeError = document.getElementById('userTypeError');

      username.addEventListener('input', () => validateUsername());
      email.addEventListener('input', () => validateEmail());
      password.addEventListener('input', () => validatePassword());
      confirmPassword.addEventListener('input', () => validateConfirmPassword());
      userType.addEventListener('change', () => validateUserType());

      function showError(element, message) {
        element.textContent = message;
        element.classList.add('show');
        if (element.parentElement.querySelector('input, select')) {
          element.parentElement.querySelector('input, select').classList.add('invalid');
          element.parentElement.querySelector('input, select').classList.remove('valid');
        }
      }

      function showSuccess(element) {
        element.textContent = '';
        element.classList.remove('show');
        if (element.parentElement.querySelector('input, select')) {
          element.parentElement.querySelector('input, select').classList.add('valid');
          element.parentElement.querySelector('input, select').classList.remove('invalid');
        }
      }

      function validateUsername() {
        const value = username.value.trim();
        if (value === '') {
          showError(usernameError, 'Username is required');
          return false;
        } else if (value.length < 3) {
          showError(usernameError, 'Username must be at least 3 characters');
          return false;
        } else if (!/^[a-zA-Z _]+$/.test(value)) {
          showError(usernameError, 'Username can only contain letters, spaces and underscores');
          return false;
        } else {
          showSuccess(usernameError);
          return true;
        }
      }

      function validateEmail() {
        const value = email.value.trim();
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (value === '') {
          showError(emailError, 'Email is required');
          return false;
        } else if (!emailPattern.test(value)) {
          showError(emailError, 'Please enter a valid email address');
          return false;
        } else {
          showSuccess(emailError);
          return true;
        }
      }

      function validatePassword() {
        const value = password.value.trim();
        if (value === '') {
          showError(passwordError, 'Password is required');
          return false;
        } else if (value.length < 8) {
          showError(passwordError, 'Password must be at least 8 characters');
          return false;
        } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
          showError(passwordError, 'Password must contain uppercase, lowercase, and number');
          return false;
        } else {
          showSuccess(passwordError);
          return true;
        }
      }

      function validateConfirmPassword() {
        const value = confirmPassword.value.trim();
        if (value === '') {
          showError(confirmPasswordError, 'Please confirm your password');
          return false;
        } else if (value !== password.value.trim()) {
          showError(confirmPasswordError, 'Passwords do not match');
          return false;
        } else {
          showSuccess(confirmPasswordError);
          return true;
        }
      }

      function validateUserType() {
        const value = userType.value;
        if (value === '') {
          showError(userTypeError, 'Please select a user type');
          return false;
        } else {
          showSuccess(userTypeError);
          return true;
        }
      }

      form.addEventListener('submit', function (e) {
        const isUsernameValid = validateUsername();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();
        const isUserTypeValid = validateUserType();

        if (!(isUsernameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid && isUserTypeValid)) {
          e.preventDefault();
          return;
        }
      });

      const formElements = document.querySelectorAll('.input-box');
      formElements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
        element.classList.add('fade-in');
      });

      // Toggle password visibility buttons (use eye icon)
      document.querySelectorAll('.toggle-password').forEach((btn) => {
        const target = document.getElementById(btn.dataset.target);
        const icon = btn.querySelector('i');
        if (!target || !icon) return;

        btn.addEventListener('click', () => {
          const isVisible = target.type === 'text';
          target.type = isVisible ? 'password' : 'text';

          // Toggle eye / eye-slash icon
          icon.classList.toggle('fa-eye', !isVisible);
          icon.classList.toggle('fa-eye-slash', isVisible);

          // Optional: change color when visible
          btn.style.color = isVisible ? '#ff0000' : '#28a745';

          btn.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        });
      });
    });
