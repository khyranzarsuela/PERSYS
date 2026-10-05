<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSHS-SAC | Login</title>
  <link rel="stylesheet" href="styles/style.css" />
</head>
<body>

  <!-- ========================================================
       Login Page — Two-panel layout
       Left  : Form panel (white)
       Right : Illustration panel (purple)
  ======================================================== -->
  <main class="login-page">

    <!-- ── LEFT PANEL ──────────────────────────────────────── -->
    <section class="form-panel" aria-label="Login form">

      <!-- Brand / Logo -->
      <header class="brand">
        <!-- Simple SVG pin / location icon that mirrors the design mark -->
        <svg class="brand-icon" viewBox="0 0 32 32" fill="none"
             xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle cx="16" cy="13" r="7" stroke="#6A0DAD" stroke-width="2.5"/>
          <circle cx="16" cy="13" r="2.5" fill="#6A0DAD"/>
          <path d="M16 20 C16 20 9 28 16 28 C23 28 16 20 16 20Z"
                fill="#6A0DAD" opacity="0.15"/>
          <!-- Head arc -->
          <path d="M11 10 Q16 4 21 10" stroke="#6A0DAD" stroke-width="2"
                fill="none" stroke-linecap="round"/>
        </svg>
        <span class="brand-name">BCSHS-SAC</span>
      </header>

      <!-- Login Form -->
      <div class="form-wrapper">
        <h1 class="form-title">Login</h1>
        <p class="form-subtitle">Login to access your travelwise account</p>
<?php 
// Start the session at the very top of index.php if not already started
if (session_status() === PHP_SESSION_ACTIVE) {
    // Session is active
} else {
    session_start(); 
}

// Display the login error if it exists
if (isset($_SESSION['login_error'])) {
    echo '<div style="color: red; margin-bottom: 15px; font-weight: bold;">' . $_SESSION['login_error'] . '</div>';
    unset($_SESSION['login_error']); // Clear it so it disappears on next refresh
}
?>

        <form action="handleLogin.php" method="POST" id="loginForm" class="login-form" novalidate>

          <!-- Email field -->
          <div class="field-group" id="emailGroup">
            <label class="field-label" for="email">Email</label>
            <input
              class="field-input"
              type="email"
              id="email"
              name="depedEmail"
              placeholder="john.doe@gmail.com"
              autocomplete="email"
              required
            />
            <!-- Inline error message (hidden by default) -->
            <span class="field-error" id="emailError" role="alert"></span>
          </div>

          <!-- Password field -->
          <div class="field-group" id="passwordGroup">
            <label class="field-label" for="password">Password</label>
            <div class="password-wrapper">
              <input
                class="field-input"
                type="password"
                id="password"
                name="password"
                placeholder="••••••••••••••••••••"
                autocomplete="current-password"
                required
              />
              <!-- Show / hide password toggle -->
              <button
                type="button"
                class="toggle-password"
                id="togglePassword"
                aria-label="Toggle password visibility"
                aria-pressed="false"
              >
                <!-- Eye-off icon (default — password hidden) -->
                <svg class="eye-icon eye-off" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20
                           C7 20 2.73 16.11 1 12
                           c.75-1.8 1.98-3.37 3.52-4.56
                           M9.9 4.24A9.12 9.12 0 0 1 12 4
                           c5 0 9.27 3.89 11 8
                           a10.15 10.15 0 0 1-1.67 2.67"
                        stroke="#9CA3AF" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M1 1l22 22" stroke="#9CA3AF" stroke-width="2"
                        stroke-linecap="round"/>
                  <path d="M10.73 10.73A3 3 0 0 0 14.83 14.83"
                        stroke="#9CA3AF" stroke-width="2"
                        stroke-linecap="round"/>
                </svg>
                <!-- Eye icon (password visible) -->
                <svg class="eye-icon eye-on" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M1 12C2.73 7.89 7 4 12 4s9.27 3.89 11 8
                           c-1.73 4.11-6 8-11 8S2.73 16.11 1 12Z"
                        stroke="#6A0DAD" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3"
                          stroke="#6A0DAD" stroke-width="2"/>
                </svg>
              </button>
            </div>
            <span class="field-error" id="passwordError" role="alert"></span>
          </div>

          <!-- Remember me + Forgot password row -->
          <div class="form-options">
            <label class="remember-label">
              <input type="checkbox" id="rememberMe" name="rememberMe" />
              <span class="custom-checkbox" aria-hidden="true"></span>
              Remember me
            </label>
            <a href="#" class="forgot-link">Forgot Password?</a>
          </div>

          <!-- Submit button -->
          <button type="submit" class="btn-login" name="btn-login">Login</button>

        </form>
      </div>

    </section>
    <!-- ── END LEFT PANEL ───────────────────────────────────── -->

    <!-- ── RIGHT PANEL ─────────────────────────────────────── -->
    <section class="illustration-panel" aria-label="Security illustration">
      <div class="illustration-card">
        <img
          src="https://placehold.co/420x520/e8e8f0/6A0DAD?text=Security+Illustration"
          alt="A hand holding a phone displaying a security lock and password interface"
          class="illustration-img"
        />
        <!-- Slide indicator dots -->
        <div class="slide-dots" aria-hidden="true">
          <span class="dot dot--active"></span>
          <span class="dot"></span>
          <span class="dot"></span>
        </div>
      </div>
    </section>
    <!-- ── END RIGHT PANEL ──────────────────────────────────── -->

  </main>

  <script src="scripts/index.js"></script>
</body>
</html>
