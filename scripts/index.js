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
  const loginInput     = document.getElementById('login');
  const loginGroup     = document.getElementById('loginGroup');
  const loginError     = document.getElementById('loginError');

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

  function showError(group, errorEl, message) {
    group.classList.add('has-error');
    errorEl.textContent = message;
  }

  function clearError(group, errorEl) {
    group.classList.remove('has-error');
    errorEl.textContent = '';
  }

  // ── 3. REAL-TIME VALIDATION (on blur) ──────────────────

  /*
   * Login can be either:
   * - Email
   * - Username
   *
   * Therefore, we only require that the field
   * contains a value.
   */
  loginInput.addEventListener('blur', function () {
    const val = this.value.trim();

    if (val === '') {
      showError(
        loginGroup,
        loginError,
        'Email or username is required.'
      );
    } else {
      clearError(loginGroup, loginError);
    }
  });

  loginInput.addEventListener('input', function () {
    if (loginGroup.classList.contains('has-error')) {
      const val = this.value.trim();

      if (val !== '') {
        clearError(loginGroup, loginError);
      }
    }
  });

  // ── PASSWORD VALIDATION ────────────────────────────────

  passwordInput.addEventListener('blur', function () {
    if (this.value === '') {
      showError(
        passwordGroup,
        passwordError,
        'Password is required.'
      );
    } else if (this.value.length < 6) {
      showError(
        passwordGroup,
        passwordError,
        'Password must be at least 6 characters.'
      );
    } else {
      clearError(passwordGroup, passwordError);
    }
  });

  passwordInput.addEventListener('input', function () {
    if (
      passwordGroup.classList.contains('has-error') &&
      this.value.length >= 6
    ) {
      clearError(passwordGroup, passwordError);
    }
  });

  // ── 4. SUBMIT VALIDATION ───────────────────────────────

  loginForm.addEventListener('submit', function (e) {

    // Temporarily stop normal form submission
    // while JavaScript validates the fields.
    e.preventDefault();

    let isValid = true;

    // ── Validate Email / Username ─────────────────────────

    const loginVal = loginInput.value.trim();

    if (loginVal === '') {

      showError(
        loginGroup,
        loginError,
        'Email or username is required.'
      );

      isValid = false;

    } else {

      clearError(loginGroup, loginError);
    }

    // ── Validate Password ─────────────────────────────────

    if (passwordInput.value === '') {

      showError(
        passwordGroup,
        passwordError,
        'Password is required.'
      );

      isValid = false;

    } else if (passwordInput.value.length < 6) {

      showError(
        passwordGroup,
        passwordError,
        'Password must be at least 6 characters.'
      );

      isValid = false;

    } else {

      clearError(passwordGroup, passwordError);
    }

    // ── Stop if validation failed ─────────────────────────

    if (!isValid) {

      const firstError =
        loginForm.querySelector('.has-error .field-input');

      if (firstError) {
        firstError.focus();
      }

      return;
    }

    // ── Everything is valid ──────────────────────────────
    //
    // Send the form normally to handleLogin.php.
    //
    loginForm.submit();
  });

})();