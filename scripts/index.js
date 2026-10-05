/**
 * PERSYS — Login Page · UI Interactions
 * -------------------------------------------------------
 * Scope: client-side UI and form processing.
 * -------------------------------------------------------
 */

(function () {
  'use strict';

  // ── Element references ──────────────────────────────────
  const loginForm      = document.getElementById('loginForm');
  const emailInput     = document.getElementById('email');
  const emailGroup     = document.getElementById('emailGroup');
  const emailError     = document.getElementById('emailError');
  const passwordInput  = document.getElementById('password');
  const passwordGroup  = document.getElementById('passwordGroup');
  const passwordError  = document.getElementById('passwordError');
  const togglePassword = document.getElementById('togglePassword');

  // Exit safely if the form is missing on this specific page load
  if (!loginForm) return;

  // ── 1. SHOW / HIDE PASSWORD ────────────────────────────
  if (togglePassword) {
    togglePassword.addEventListener('click', function () {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      this.classList.toggle('is-visible', isHidden);
      this.setAttribute('aria-pressed', String(isHidden));
      passwordInput.focus();
    });
  }

  // ── 2. VALIDATION HELPERS ─────────────────────────────
  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
  }

  function showError(group, errorEl, message) {
    group.classList.add('has-error');
    errorEl.textContent = message;
  }

  function clearError(group, errorEl) {
    group.classList.remove('has-error');
    errorEl.textContent = '';
  }

  // ── 3. REAL-TIME VALIDATION (on blur) ──────────────────
  emailInput.addEventListener('blur', function () {
    const val = this.value.trim();
    if (val === '') {
      showError(emailGroup, emailError, 'Email is required.');
    } else if (!isValidEmail(val)) {
      showError(emailGroup, emailError, 'Please enter a valid email address.');
    } else {
      clearError(emailGroup, emailError);
    }
  });

  emailInput.addEventListener('input', function () {
    if (emailGroup.classList.contains('has-error')) {
      const val = this.value.trim();
      if (isValidEmail(val)) clearError(emailGroup, emailError);
    }
  });

  passwordInput.addEventListener('blur', function () {
    if (this.value === '') {
      showError(passwordGroup, passwordError, 'Password is required.');
    } else if (this.value.length < 6) {
      showError(passwordGroup, passwordError, 'Password must be at least 6 characters.');
    } else {
      clearError(passwordGroup, passwordError);
    }
  });

  passwordInput.addEventListener('input', function () {
    if (passwordGroup.classList.contains('has-error') && this.value.length >= 6) {
      clearError(passwordGroup, passwordError);
    }
  });

  // ── 4. SUBMIT VALIDATION ───────────────────────────────
  loginForm.addEventListener('submit', function (e) {
    // 1. Temporarily pause the submission to let JavaScript evaluate inputs
    e.preventDefault(); 

    let isValid = true;

    // Validate email
    const emailVal = emailInput.value.trim();
    if (emailVal === '') {
      showError(emailGroup, emailError, 'Email is required.');
      isValid = false;
    } else if (!isValidEmail(emailVal)) {
      showError(emailGroup, emailError, 'Please enter a valid email address.');
      isValid = false;
    } else {
      clearError(emailGroup, emailError);
    }

    // Validate password
    if (passwordInput.value === '') {
      showError(passwordGroup, passwordError, 'Password is required.');
      isValid = false;
    } else if (passwordInput.value.length < 6) {
      showError(passwordGroup, passwordError, 'Password must be at least 6 characters.');
      isValid = false;
    } else {
      clearError(passwordGroup, passwordError);
    }

    // If any field is invalid, cancel submission and focus field
    if (!isValid) {
      const firstError = loginForm.querySelector('.has-error .field-input');
      if (firstError) firstError.focus();
      return;
    }

    // 2. FIXED: If everything passes JavaScript validation, submit directly to PHP!
    loginForm.submit();
  });

})();
