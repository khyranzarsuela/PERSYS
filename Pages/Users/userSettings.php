<?php
require_once '../../session.php';
start_app_session();
require_user();

$pdo       = db();
$accountId = (int) $_SESSION['account_id'];

// AJAX: save font size
if (!empty($_POST['ajax_save_font_size'])) {
    $fontSizeRaw = trim($_POST['font_size'] ?? '16px');
    // Validate: allow only "NNpx" format, 12px–24px
    if (preg_match('/^(1[2-9]|2[0-4])px$/', $fontSizeRaw)) {
        $stmtUpsert = $pdo->prepare(
            "INSERT INTO account_settings (account_id, font_size)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE font_size = ?"
        );
        $stmtUpsert->execute([$accountId, $fontSizeRaw, $fontSizeRaw]);
    }
    exit;
}

// Load saved font size
$stmtFS = $pdo->prepare("SELECT font_size FROM account_settings WHERE account_id = ?");
$stmtFS->execute([$accountId]);
$savedFontSize    = $stmtFS->fetchColumn() ?: '16px';
$savedFontSizeNum = (int) filter_var($savedFontSize, FILTER_SANITIZE_NUMBER_INT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSHS-SAC | Settings</title>
  <link rel="stylesheet" href="../../styles/user.css" />
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
          integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
          crossorigin="anonymous"></script>
</head>
<body>
  <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
  <div class="app-shell">

    <!-- ── SIDEBAR: Settings active ─────────────────────────── -->
    <aside class="sidebar" id="sidebar" aria-label="User navigation">
      <img class="sidebar-avatar" src="https://placehold.co/72x72/6B0FBA/FFFFFF?text=U" alt="User profile photo" />
      <p class="sidebar-name"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
      <p class="sidebar-role"><?php echo htmlspecialchars($_SESSION['role_name']); ?></p>
      <div class="sidebar-divider" role="separator"></div>
      <nav aria-label="Main menu">
        <ul class="sidebar-nav">
          <li>
            <a href="userDashboard.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <rect x="3" y="3" width="7" height="7" rx="1"/>
                  <rect x="14" y="3" width="7" height="7" rx="1"/>
                  <rect x="3" y="14" width="7" height="7" rx="1"/>
                  <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
              </span>
              My Dashboard
            </a>
          </li>
          <li>
            <a href="userProfile.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <circle cx="12" cy="8" r="4"/>
                  <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
              </span>
              Profile
            </a>
          </li>
          <li>
            <a href="userServiceCredits.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <circle cx="12" cy="9" r="5"/>
                  <path d="M7.5 14.5L5 21l7-2.5L19 21l-2.5-6.5"/>
                </svg>
              </span>
              Service Credits
            </a>
          </li>
          <li>
            <a href="userRequest.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <rect x="4" y="2" width="16" height="20" rx="2"/>
                  <line x1="8" y1="7" x2="16" y2="7"/>
                  <line x1="8" y1="11" x2="16" y2="11"/>
                  <line x1="8" y1="15" x2="12" y2="15"/>
                </svg>
              </span>
              Request
            </a>
          </li>
          <li>
            <a href="userSettings.php" class="nav-item active" aria-current="page">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="3"/>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
                </svg>
              </span>
              Settings
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <div class="main-wrapper">
      <!-- TOPBAR -->
      <header class="topbar" role="banner">
        <span class="topbar-icon" aria-hidden="true">
          <span></span><span></span><span></span><span></span>
        </span>
        <h1 class="topbar-title">Settings</h1>
        <button class="hamburger" id="hamburger" type="button"
                aria-controls="sidebar" aria-expanded="false"
                aria-label="Toggle navigation menu">
          <span></span><span></span><span></span>
        </button>
      </header>

      <main class="page-content settings-content" id="mainContent">

        <div class="settings-grid">

          <!-- LEFT: Display and Brightness -->
          <div class="settings-card">
            <div class="settings-card-title">Display and Brightness</div>

            <!-- Mode options -->
            <div class="settings-row">
              <div class="mode-options-row">
                <div class="mode-option selected" data-mode="light">
                  <div class="mode-radio"></div>
                  Light Mode
                </div>
                <div class="mode-option" data-mode="dark">
                  <div class="mode-radio"></div>
                  Dark Mode
                </div>
              </div>
            </div>

            <!-- Brightness -->
            <div class="settings-row">
              <label class="settings-label" for="brightnessSlider">Screen Brightness</label>
              <input type="range" id="brightnessSlider" min="0" max="100" value="80" />
            </div>

            <!-- Adaptive Brightness toggle -->
            <div class="settings-row">
              <div class="toggle-row">
                <span class="toggle-label-text">Adaptive Brightness</span>
                <label class="toggle-switch">
                  <input type="checkbox" id="adaptiveBrightness" class="pref-toggle" checked />
                  <span class="toggle-track"></span>
                </label>
              </div>
            </div>
          </div>

          <!-- RIGHT: Accessibility Options -->
          <div class="settings-card">
            <div class="settings-card-title">Accessibility Options</div>

            <!-- Font Size -->
            <div class="settings-row">
              <label class="settings-label" for="fontSizeSlider">
                Font Size
                <span class="val-display" id="fontSizeVal"><?= $savedFontSizeNum ?>px</span>
              </label>
              <input type="range" id="fontSizeSlider"
                     min="12" max="24"
                     value="<?= $savedFontSizeNum ?>"
                     data-saved="<?= $savedFontSizeNum ?>" />
            </div>

            <div class="settings-divider"></div>

            <!-- Text Spacing -->
            <div class="settings-row">
              <label class="settings-label" for="textSpacingSlider">Text Spacing</label>
              <input type="range" id="textSpacingSlider" min="0" max="10" value="0" />
            </div>

            <div class="settings-divider"></div>

            <!-- Increased Color Contrast toggle -->
            <div class="settings-row">
              <div class="toggle-row">
                <span class="toggle-label-text">Increased Color Contrast</span>
                <label class="toggle-switch">
                  <input type="checkbox" id="colorContrastToggle" class="pref-toggle" checked />
                  <span class="toggle-track"></span>
                </label>
              </div>
            </div>

            <!-- Reduced Motion toggle -->
            <div class="settings-row">
              <div class="toggle-row">
                <span class="toggle-label-text">Reduced Motion</span>
                <label class="toggle-switch">
                  <input type="checkbox" id="reducedMotionToggle" class="pref-toggle" />
                  <span class="toggle-track"></span>
                </label>
              </div>
            </div>

          </div>

        </div>

      </main>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div class="modal-overlay" id="logoutModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle">
    <div class="modal-box">
      <div class="modal-header">
        <h2 id="logoutModalTitle">Log Out</h2>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to log out?</p>
      </div>
      <div class="modal-footer">
        <button type="button" id="cancelLogoutBtn" class="btn-secondary">Cancel</button>
        <button type="button" id="confirmLogoutBtn" class="btn-yellow">Log Out</button>
      </div>
    </div>
  </div>

  <script src="../../scripts/user.js"></script>
</body>
</html>
