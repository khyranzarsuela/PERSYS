# Implementation Plan — PERSYS User Dashboard & Pages

## Codebase Notes (discovered during exploration)

- **Session helpers** (`session.php`): `start_app_session()`, `require_user()`, `logout_session()`, `db()` — all available via `require_once '../../session.php'`.
- **`require_user()`** redirects non-employee roles to `adminDashboard.php`, so every user page is naturally protected.
- **`db()`** returns a singleton PDO instance (from `database.php`); use it directly — no `new PDO(...)` needed.
- **Existing `user.js`** is a plain IIFE (no jQuery). The new jQuery blocks go below, each self-contained and guarded.
- **Existing `user.css`** already defines all shell variables (`:root`), sidebar, topbar, app-shell, and responsive breakpoints. Only new page-specific selectors are appended.
- **`userProfile.php`** currently links `../../scripts/admin.js` — this is the main fix needed there.
- **`userDashboard.php`** is a stub (just `require_user()` + a bare `<h1>USER</h1>`). Full rebuild required.
- **Files to create new**: `userRequest.php`, `userServiceCredits.php`, `userSettings.php`, `logout.php`.
- **Schema key facts**:
  - `leave_requests`: columns `leave_request_id`, `request_number`, `employee_id`, `leave_type_id`, `date_filed`, `start_datetime`, `end_datetime`, `total_leave_days`, `status` (enum Pending/Approved/Rejected), `created_at`.
  - `leave_types`: `leave_type_id`, `leave_type_name`, `is_active`.
  - `service_credit_transactions`: `transaction_id`, `employee_id`, `credit_type_id`, `activity_name`, `activity_date`, `credit_amount`, `transaction_type` (EARNED/DEDUCTED), `created_at`.
  - `service_credit_types`: `credit_type_id` (1=Local, 2=National), `credit_type_name`.
  - `account_settings`: `account_id`, `font_size` — used by Settings page AJAX save.
- **Request number format**: `LR-YYYY-NNNN` (zero-padded 4 digits). Generate by reading MAX of existing `request_number` for the same year via parameterized query, then incrementing.
- **No AGENTS.md / CONTRIBUTING.md** found in project root — vendor docs only. Follow the hard rules in the task brief.

---

## Hard Rules (non-negotiable, checked at every step)

1. Admin files (`Pages/Admin/*`, `styles/admin.css`, `styles/admin-profile.css`, `scripts/admin.js`) — **never touch**.
2. All 5 user PHP files live under `Pages/Users/`.
3. `user.css` — **append only** at bottom; never remove or rewrite existing rules.
4. `user.js` — **append only** at bottom; never remove or rewrite existing code.
5. Every user PHP file: `<head>` includes jQuery 3.7.1 CDN **before** `user.js`, and references `../../styles/user.css` and `../../scripts/user.js`.
6. All PHP DB queries use **parameterized PDO** (`prepare` + `execute([...])`) — no string interpolation of user data.
7. Sidebar nav is **identical in every page** — 5 items, no Transactions, correct active class per page.

---

## Shared Sidebar HTML Template

Use this exact HTML block in every page (swap `active` class to the correct item):

```html
<aside class="sidebar" id="sidebar" aria-label="User navigation">
  <img class="sidebar-avatar"
       src="https://placehold.co/72x72/6B0FBA/FFFFFF?text=U"
       alt="User profile photo" />
  <p class="sidebar-name"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
  <p class="sidebar-role"><?php echo htmlspecialchars($_SESSION['role_name']); ?></p>
  <div class="sidebar-divider" role="separator"></div>
  <nav aria-label="Main menu">
    <ul class="sidebar-nav">
      <li>
        <a href="userDashboard.php" class="nav-item [active on dashboard]">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          </span>
          My Dashboard
        </a>
      </li>
      <li>
        <a href="userProfile.php" class="nav-item [active on profile]">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
          </span>
          Profile
        </a>
      </li>
      <li>
        <a href="userServiceCredits.php" class="nav-item [active on service credits]">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="5"/><path d="M7.5 14.5L5 21l7-2.5L19 21l-2.5-6.5"/></svg>
          </span>
          Service Credits
        </a>
      </li>
      <li>
        <a href="userRequest.php" class="nav-item [active on request]">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="12" y2="15"/></svg>
          </span>
          Request
        </a>
      </li>
      <li>
        <a href="userSettings.php" class="nav-item [active on settings]">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
          </span>
          Settings
        </a>
      </li>
    </ul>
  </nav>
</aside>
```

---

## Shared `<head>` Template

Use in every user PHP file (title varies per page):

```html
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSHS-SAC | [Page Name]</title>
  <link rel="stylesheet" href="../../styles/user.css" />
  <!-- userProfile.php also keeps: <link rel="stylesheet" href="../../styles/user-profile.css" /> -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
          integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
          crossorigin="anonymous"></script>
</head>
```

The `<script src="../../scripts/user.js"></script>` tag goes at the **bottom of `<body>`**, after all HTML.

---

## Shared `#logoutModal` HTML

Every page that has `.btn-logout` must include this modal in the body (before the closing `</body>`):

```html
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
```

---

## Implementation Order

Steps are ordered by dependency. Each step must leave the codebase in a working state.

---

- [ ] 1. **Create `Pages/Users/logout.php`** (new file — required by JS logout handler in all pages)

  Create a minimal logout handler. No HTML output — PHP only.

  ```php
  <?php
  require_once '../../session.php';
  start_app_session();
  logout_session();
  header('Location: /PERSYS/index.php');
  exit;
  ```

  Files: `Pages/Users/logout.php`

  Verify: File exists and contains the four lines above. Navigate to it in a browser while logged in — you should be redirected to `/PERSYS/index.php` and the session should be cleared.

---

- [ ] 2. **Append CSS to `styles/user.css`** (append-only — do not touch any existing rules)

  Append the following new sections at the very bottom of the file, after all existing CSS. Do not remove or rewrite any existing rule.

  New CSS sections to append (in this order):

  ```css
  /* ============================================================
     USER PAGES — Page-specific styles
     Appended below existing shell styles. Do not edit above.
     ============================================================ */

  /* ── SHARED: Yellow CTA button ────────────────────────────── */
  .btn-yellow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background-color: var(--clr-yellow-accent);
    color: #1a1a1a;
    font-size: 0.875rem;
    font-weight: 700;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
    white-space: nowrap;
  }
  .btn-yellow:hover {
    background-color: #e0b214;
    box-shadow: 0 3px 10px rgba(245, 197, 24, 0.4);
  }
  .btn-yellow:active { transform: translateY(1px); }

  .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background-color: transparent;
    color: var(--clr-text-mid);
    font-size: 0.875rem;
    font-weight: 600;
    border: 1.5px solid var(--clr-border-light);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background-color 0.2s ease;
  }
  .btn-secondary:hover { background-color: #eee; }

  /* ── SHARED: Topbar SVG icon (yellow stroke) ──────────────── */
  .topbar-svg-icon svg {
    width: 26px;
    height: 26px;
    stroke: var(--clr-yellow-accent);
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    display: block;
  }

  /* ── SHARED: Status badges ─────────────────────────────────── */
  .status-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 12px;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 4px;
    white-space: nowrap;
  }
  .status-badge--pending  { background-color: var(--clr-yellow-badge); color: #1a1a1a; }
  .status-badge--approved { background-color: #22C55E; color: #fff; }
  .status-badge--rejected { background-color: #EF4444; color: #fff; }

  /* ── SHARED: Modal overlay + box ──────────────────────────── */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .modal-box {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 32px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    position: relative;
  }
  .modal-header {
    margin-bottom: 20px;
    border-bottom: 1px solid var(--clr-border-light);
    padding-bottom: 14px;
  }
  .modal-header h2 {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--clr-text-dark);
  }
  .modal-body { margin-bottom: 24px; }
  .modal-body p, .modal-body label {
    font-size: 0.875rem;
    color: var(--clr-text-mid);
    line-height: 1.6;
  }
  .modal-body .form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 16px;
  }
  .modal-body .form-group label {
    font-weight: 600;
    color: var(--clr-text-dark);
  }
  .modal-body .form-group input,
  .modal-body .form-group select,
  .modal-body .form-group textarea {
    padding: 9px 12px;
    border: 1.5px solid var(--clr-border-light);
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    font-family: var(--font-base);
    background: #fafafa;
    color: var(--clr-text-dark);
    transition: border-color 0.15s;
  }
  .modal-body .form-group input:focus,
  .modal-body .form-group select:focus,
  .modal-body .form-group textarea:focus {
    outline: none;
    border-color: var(--clr-purple-topbar);
  }
  .modal-body .form-group textarea { min-height: 80px; resize: vertical; }
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }
  .modal-info-note {
    background: #f0f4ff;
    border-left: 3px solid var(--clr-purple-topbar);
    border-radius: 4px;
    padding: 10px 14px;
    font-size: 0.8rem;
    color: var(--clr-text-mid);
    margin-top: 12px;
  }
  .modal-review-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--clr-border-light);
    font-size: 0.875rem;
  }
  .modal-review-row:last-of-type { border-bottom: none; }
  .modal-review-label { font-weight: 600; color: var(--clr-text-dark); }
  .modal-review-value { color: var(--clr-text-mid); }
  .modal-success-icon {
    display: flex;
    justify-content: center;
    margin-bottom: 16px;
  }
  .modal-success-icon svg {
    width: 56px;
    height: 56px;
    stroke: #22C55E;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  /* ── DASHBOARD: page-content override ─────────────────────── */
  .page-content.dashboard-content {
    display: flex;
    flex-direction: column;
    gap: var(--gap-lg);
  }

  /* ── DASHBOARD: 3-column stat cards row ───────────────────── */
  .stat-cards-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
  }
  .stat-card-h {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    padding: 22px 28px 26px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s;
  }
  .stat-card-h:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.1); }
  .stat-card-h .stat-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .stat-icon-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .stat-icon--yellow { background-color: var(--clr-yellow-accent); }
  .stat-icon--green  { background-color: #22C55E; }
  .stat-icon--red    { background-color: #EF4444; }
  .stat-icon-circle svg {
    width: 22px;
    height: 22px;
    stroke: #fff;
    fill: none;
    stroke-width: 1.75;
    stroke-linecap: round;
    stroke-linejoin: round;
  }
  .stat-icon--yellow svg { stroke: #1a1a1a; }
  .stat-label-h {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--clr-text-dark);
  }
  .stat-number-h {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--clr-text-dark);
    text-align: center;
    letter-spacing: 0.02em;
    padding: 2px 0;
  }

  /* ── DASHBOARD: bottom 2-column row ───────────────────────── */
  .dashboard-bottom-row {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 20px;
    align-items: start;
  }

  /* Service Credit Balance card */
  .sc-balance-card {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    padding: 24px 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  .sc-balance-heading {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--clr-text-dark);
  }
  .sc-balance-heading svg {
    width: 22px;
    height: 22px;
    stroke: var(--clr-yellow-accent);
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    flex-shrink: 0;
  }
  .sc-balance-rows { display: flex; flex-direction: column; gap: 10px; }
  .sc-balance-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: #f5f0ff;
    border-radius: var(--radius-sm);
  }
  .sc-balance-label { font-size: 0.875rem; font-weight: 600; color: var(--clr-text-dark); }
  .sc-balance-value { font-size: 1.125rem; font-weight: 700; color: var(--clr-purple-topbar); }

  /* Quick Action card */
  .quick-action-card {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    padding: 24px 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  .quick-action-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--clr-text-dark);
  }
  .quick-action-link {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.875rem;
    color: var(--clr-purple-mid);
    font-weight: 500;
    transition: opacity 0.15s;
  }
  .quick-action-link:hover { opacity: 0.75; text-decoration: underline; }
  .quick-action-link svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.75;
    stroke-linecap: round;
    stroke-linejoin: round;
    flex-shrink: 0;
  }

  /* ── DASHBOARD: Recent Leave table ────────────────────────── */
  .recent-leave-table {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }
  .table-header-bar {
    background-color: var(--clr-purple-notif-hd);
    padding: 14px 20px;
  }
  .table-header-bar h3 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--clr-white);
  }
  .data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }
  .data-table th {
    background-color: #f0eaf8;
    color: var(--clr-text-dark);
    font-weight: 700;
    padding: 11px 16px;
    text-align: left;
    border-bottom: 2px solid var(--clr-border-light);
  }
  .data-table td {
    padding: 11px 16px;
    border-bottom: 1px solid var(--clr-border-light);
    color: var(--clr-text-mid);
  }
  .data-table tr:last-child td { border-bottom: none; }
  .data-table tr:hover td { background-color: #faf6ee; }
  .data-table .empty-row td {
    text-align: center;
    color: var(--clr-text-light);
    font-style: italic;
    padding: 24px;
  }
  .data-table a { color: var(--clr-purple-mid); font-weight: 500; }
  .data-table a:hover { text-decoration: underline; }

  /* ── REQUEST PAGE ──────────────────────────────────────────── */
  .page-content.request-content {
    display: flex;
    flex-direction: column;
    gap: var(--gap-lg);
  }
  .request-page-heading { font-size: 1.125rem; font-weight: 700; color: var(--clr-text-dark); }
  .request-page-sub { font-size: 0.875rem; color: var(--clr-text-mid); margin-top: 4px; }
  .request-card {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    padding: 24px 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .request-card-title { font-size: 1rem; font-weight: 700; color: var(--clr-text-dark); }
  .request-card-sub   { font-size: 0.875rem; color: var(--clr-text-mid); }
  .my-request-table {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }

  /* ── SERVICE CREDITS PAGE ──────────────────────────────────── */
  .page-content.service-credits-content {
    display: flex;
    flex-direction: column;
    gap: var(--gap-lg);
  }
  .sc-page-heading { font-size: 1.125rem; font-weight: 700; color: var(--clr-text-dark); }
  .credit-balance-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  .credit-balance-card {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    padding: 24px 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .credit-balance-card-title { font-size: 0.9rem; font-weight: 600; color: var(--clr-text-mid); }
  .credit-balance-number {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--clr-purple-topbar);
  }
  .sc-log-table {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }

  /* ── SETTINGS PAGE ─────────────────────────────────────────── */
  .page-content.settings-content {
    display: flex;
    flex-direction: column;
    gap: var(--gap-lg);
  }
  .settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
  }
  .settings-card {
    background-color: var(--clr-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--clr-border-light);
    padding: 24px 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  .settings-card-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--clr-text-dark);
    padding-bottom: 12px;
    border-bottom: 1px solid var(--clr-border-light);
  }
  .settings-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .settings-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--clr-text-dark);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .settings-label span.val-display {
    font-weight: 400;
    color: var(--clr-text-mid);
  }
  .settings-row input[type="range"] {
    width: 100%;
    accent-color: var(--clr-purple-topbar);
    cursor: pointer;
  }
  .settings-divider {
    height: 1px;
    background-color: var(--clr-border-light);
  }
  /* Mode option boxes (Light / Dark) */
  .mode-options-row {
    display: flex;
    gap: 12px;
  }
  .mode-option {
    flex: 1;
    border: 2px solid var(--clr-border-light);
    border-radius: var(--radius-md);
    padding: 14px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--clr-text-dark);
    background: #fafafa;
    transition: border-color 0.15s, background 0.15s;
    user-select: none;
  }
  .mode-option.selected {
    border-color: var(--clr-yellow-accent);
    background: #fffbe8;
  }
  .mode-radio {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid var(--clr-border-light);
    flex-shrink: 0;
    transition: border-color 0.15s, background 0.15s;
  }
  .mode-option.selected .mode-radio {
    border-color: var(--clr-yellow-accent);
    background: var(--clr-yellow-accent);
  }
  /* Toggle switch (CSS-only checkbox) */
  .toggle-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
  }
  .toggle-label-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--clr-text-dark);
  }
  .toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
  }
  .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
  .toggle-track {
    position: absolute;
    inset: 0;
    background: #ccc;
    border-radius: 24px;
    transition: background 0.2s;
    cursor: pointer;
  }
  .toggle-track::after {
    content: '';
    position: absolute;
    left: 3px;
    top: 3px;
    width: 18px;
    height: 18px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
  }
  .toggle-switch input:checked + .toggle-track { background: #3B82F6; }
  .toggle-switch input:checked + .toggle-track::after { transform: translateX(20px); }

  /* ── RESPONSIVE: User pages ────────────────────────────────── */
  @media (max-width: 900px) {
    .stat-cards-row    { grid-template-columns: 1fr; }
    .dashboard-bottom-row { grid-template-columns: 1fr; }
    .credit-balance-cards { grid-template-columns: 1fr; }
    .settings-grid     { grid-template-columns: 1fr; }
  }
  @media (max-width: 768px) {
    .stat-cards-row { grid-template-columns: 1fr; }
    .modal-box { max-width: 95vw; padding: 22px; }
  }
  ```

  Files: `styles/user.css`

  Verify: Open any user page in a browser — no existing styles should break. The new selectors (`.stat-cards-row`, `.modal-overlay`, `.btn-yellow`, etc.) should be present in the computed styles panel.

---

- [ ] 3. **Append JS to `scripts/user.js`** (append-only — do not touch any existing code)

  Append the following after the last line of the existing file. Keep all existing IIFEs intact.

  ```js
  /* ============================================================
     USER PAGES — jQuery interactions
     All blocks guarded: (1) jQuery exists check, (2) unique
     element exists check so blocks are no-ops on other pages.
     ============================================================ */

  /* ── LOGOUT MODAL — present on any page with .btn-logout ──── */
  (function () {
    'use strict';
    if (typeof $ === 'undefined') return;
    if (!$('.btn-logout').length) return;

    $('.btn-logout').on('click', function () {
      $('#logoutModal').css('display', 'flex');
    });
    $('#cancelLogoutBtn').on('click', function () {
      $('#logoutModal').hide();
    });
    $('#confirmLogoutBtn').on('click', function () {
      window.location.href = '/PERSYS/Pages/Users/logout.php';
    });
    // Close on overlay click (but not on modal-box click)
    $('#logoutModal').on('click', function (e) {
      if ($(e.target).is('#logoutModal')) $(this).hide();
    });
  })();

  /* ── REQUEST PAGE — guard: #openLeaveFormBtn ───────────────── */
  (function () {
    'use strict';
    if (typeof $ === 'undefined') return;
    if (!$('#openLeaveFormBtn').length) return;

    /** Count weekday (Mon–Fri) days between two date strings inclusive. */
    function countWeekdays(startStr, endStr) {
      var start = new Date(startStr);
      var end   = new Date(endStr);
      if (isNaN(start) || isNaN(end) || end < start) return 0;
      var count = 0;
      var cur = new Date(start);
      while (cur <= end) {
        var day = cur.getDay();
        if (day !== 0 && day !== 6) count++;
        cur.setDate(cur.getDate() + 1);
      }
      return count;
    }

    /** Recalculate total leave days from current date inputs. */
    function recalcDays() {
      var s = $('#leaveStartDate').val();
      var e = $('#leaveEndDate').val();
      var days = (s && e) ? countWeekdays(s, e) : 0;
      $('#totalLeaveDays').val(days);
    }

    // Open leave form modal
    $('#openLeaveFormBtn').on('click', function () {
      $('#leaveFormModal').css('display', 'flex');
    });

    // Date change → recalculate days
    $('#leaveStartDate, #leaveEndDate').on('change', recalcDays);

    // Cancel on leave form → hide modal
    $('#cancelLeaveFormBtn').on('click', function () {
      $('#leaveFormModal').hide();
    });

    // Review button → validate, populate review modal, show it
    $('#reviewLeaveBtn').on('click', function () {
      var leaveTypeId   = $('#leaveTypeSelect').val();
      var leaveTypeName = $('#leaveTypeSelect option:selected').text();
      var startDate     = $('#leaveStartDate').val();
      var endDate       = $('#leaveEndDate').val();
      var totalDays     = $('#totalLeaveDays').val();
      var reason        = $('#leaveReason').val().trim();

      if (!leaveTypeId || !startDate || !endDate || !reason) {
        alert('Please fill in all required fields.');
        return;
      }
      if (parseInt(totalDays, 10) <= 0) {
        alert('Total leave days must be at least 1 weekday.');
        return;
      }

      // Store for AJAX submit
      $('#leaveFormModal').data('leave-type-id',   leaveTypeId);
      $('#leaveFormModal').data('leave-type-name',  leaveTypeName);
      $('#leaveFormModal').data('start-date',        startDate);
      $('#leaveFormModal').data('end-date',          endDate);
      $('#leaveFormModal').data('total-days',        totalDays);
      $('#leaveFormModal').data('reason',            reason);

      // Populate review display spans
      $('#reviewLeaveType').text(leaveTypeName);
      $('#reviewStartDate').text(startDate);
      $('#reviewEndDate').text(endDate);
      $('#reviewTotalDays').text(totalDays);

      $('#leaveFormModal').hide();
      $('#reviewLeaveModal').css('display', 'flex');
    });

    // Back to edit from review modal
    $('#backToEditBtn').on('click', function () {
      $('#reviewLeaveModal').hide();
      $('#leaveFormModal').css('display', 'flex');
    });

    // Submit leave request via AJAX
    $('#submitLeaveBtn').on('click', function () {
      var $btn = $(this);
      $btn.prop('disabled', true).text('Sending…');

      var data = {
        ajax_submit_leave: 1,
        leave_type_id:  $('#leaveFormModal').data('leave-type-id'),
        start_date:     $('#leaveFormModal').data('start-date'),
        end_date:       $('#leaveFormModal').data('end-date'),
        total_leave_days: $('#leaveFormModal').data('total-days'),
        reason_details: $('#leaveFormModal').data('reason')
      };

      $.post(window.location.href, data, function (resp) {
        if (resp && resp.success) {
          $('#reviewLeaveModal').hide();
          $('#sentRequestNumber').text(resp.request_number);
          $('#requestSentModal').css('display', 'flex');
        } else {
          alert('Error: ' + (resp.message || 'Could not submit request.'));
          $btn.prop('disabled', false).text('Request and Send');
        }
      }, 'json').fail(function () {
        alert('Server error. Please try again.');
        $btn.prop('disabled', false).text('Request and Send');
      });
    });

    // View request after send → reload page
    $('#viewRequestAfterSend').on('click', function () {
      location.reload();
    });

    // View request details from table row
    $(document).on('click', '.view-request-btn', function (e) {
      e.preventDefault();
      var $row = $(this).closest('tr');
      $('#detailRequestNumber').text($row.data('req-number'));
      $('#detailLeaveType').text($row.data('leave-type'));
      $('#detailStartDate').text($row.data('start'));
      $('#detailEndDate').text($row.data('end'));
      $('#detailTotalDays').text($row.data('days'));

      var status = $row.data('status');
      $('#detailStatus').text(status);
      var note = '';
      if (status === 'Pending')  note = 'Waiting for administrator approval.';
      if (status === 'Approved') note = 'Your leave has been approved.';
      if (status === 'Rejected') note = 'Your leave request was rejected.';
      $('#detailStatusNote').text(note);

      $('#requestDetailsModal').css('display', 'flex');
    });

    // Close request details modal
    $('#closeDetailsBtn').on('click', function () {
      $('#requestDetailsModal').hide();
    });
    $('#requestDetailsModal').on('click', function (e) {
      if ($(e.target).is('#requestDetailsModal')) $(this).hide();
    });

  })();

  /* ── SETTINGS PAGE — guard: #fontSizeSlider ───────────────── */
  (function () {
    'use strict';
    if (typeof $ === 'undefined') return;
    if (!$('#fontSizeSlider').length) return;

    var saveTimer = null;

    function debounce(fn, delay) {
      return function () {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(fn, delay);
      };
    }

    // Font size slider
    $('#fontSizeSlider').on('input', function () {
      var val = $(this).val();
      $('#fontSizeVal').text(val + 'px');
      debounce(function () {
        $.post(window.location.href, { ajax_save_font_size: 1, font_size: val + 'px' });
      }, 600)();
    });

    // Brightness slider → localStorage
    $('#brightnessSlider').on('input', function () {
      localStorage.setItem('persys_brightness', $(this).val());
    });

    // Text spacing slider → localStorage
    $('#textSpacingSlider').on('input', function () {
      localStorage.setItem('persys_text_spacing', $(this).val());
    });

    // Mode options
    $('.mode-option').on('click', function () {
      $('.mode-option').removeClass('selected');
      $(this).addClass('selected');
      localStorage.setItem('persys_color_mode', $(this).data('mode'));
    });

    // Toggle switches → localStorage
    $(document).on('change', '.pref-toggle', function () {
      localStorage.setItem('persys_toggle_' + $(this).attr('id'), $(this).is(':checked') ? '1' : '0');
    });

    // Restore all preferences on load
    $(document).ready(function () {
      // Font size
      var fs = localStorage.getItem('persys_font_size');
      if (fs) {
        var num = parseInt(fs, 10);
        if (!isNaN(num)) {
          $('#fontSizeSlider').val(num);
          $('#fontSizeVal').text(num + 'px');
        }
      }

      // Brightness
      var bv = localStorage.getItem('persys_brightness');
      if (bv !== null) $('#brightnessSlider').val(bv);

      // Text spacing
      var ts = localStorage.getItem('persys_text_spacing');
      if (ts !== null) $('#textSpacingSlider').val(ts);

      // Mode option
      var mode = localStorage.getItem('persys_color_mode');
      if (mode) {
        $('.mode-option').removeClass('selected');
        $('.mode-option[data-mode="' + mode + '"]').addClass('selected');
      }

      // Toggle switches
      $('.pref-toggle').each(function () {
        var stored = localStorage.getItem('persys_toggle_' + $(this).attr('id'));
        if (stored !== null) $(this).prop('checked', stored === '1');
      });
    });

  })();
  ```

  Files: `scripts/user.js`

  Verify: Open browser console on the Request page — no JS errors. Open Settings page — sliders and mode option clicks work.

---

- [ ] 4. **Fix `Pages/Users/userProfile.php`** (targeted changes only — do not touch profile cards, PHP queries, or CSS)

  Make exactly these 4 changes and nothing else:

  1. Change `<title>BCSHS-SAC | Admin Profile</title>` → `<title>BCSHS-SAC | Profile</title>`
  2. Replace the entire `<aside class="sidebar" ...>` block (from `<aside` down to and including its closing `</aside>`) with the shared sidebar template above — with `Profile` nav-item having `class="nav-item active"` and `aria-current="page"`, and all other nav-items having only `class="nav-item"`.
  3. Change `src="https://placehold.co/72x72/6B0FBA/FFFFFF?text=A"` → `src="https://placehold.co/72x72/6B0FBA/FFFFFF?text=U"` inside the sidebar avatar `<img>`.  *(This is already handled by replacing the sidebar block in step 2.)*
  4. Change `<script src="../../scripts/admin.js"></script>` → `<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>` followed on the next line by `<script src="../../scripts/user.js"></script>`. Also add the `#logoutModal` HTML block (from the Shared section above) immediately before the closing `</body>` tag.

  **Do NOT change**: any `.profile-card` content, `<dl>`, `<dd>`, PHP queries, the `<link>` to `user-profile.css`, or any CSS classes on the profile layout.

  Files: `Pages/Users/userProfile.php`

  Verify: Open `/PERSYS/Pages/Users/userProfile.php` in browser — profile data still shows, sidebar nav shows all 5 correct items with Profile active, browser tab title says "BCSHS-SAC | Profile", logout button opens the confirmation modal.

---

- [ ] 5. **Create `Pages/Users/userDashboard.php`** (full rebuild of the stub)

  **PHP block at top** (before DOCTYPE):

  ```php
  <?php
  require_once '../../session.php';
  start_app_session();
  require_user();

  $pdo        = db();
  $empId      = (int) $_SESSION['employee_id'];

  // --- Leave request counts ---
  $stmtPending = $pdo->prepare(
      "SELECT COUNT(*) FROM leave_requests WHERE employee_id = ? AND status = 'Pending'"
  );
  $stmtPending->execute([$empId]);
  $pendingCount = (int) $stmtPending->fetchColumn();

  $stmtApproved = $pdo->prepare(
      "SELECT COUNT(*) FROM leave_requests WHERE employee_id = ? AND status = 'Approved'"
  );
  $stmtApproved->execute([$empId]);
  $approvedCount = (int) $stmtApproved->fetchColumn();

  $stmtRejected = $pdo->prepare(
      "SELECT COUNT(*) FROM leave_requests WHERE employee_id = ? AND status = 'Rejected'"
  );
  $stmtRejected->execute([$empId]);
  $rejectedCount = (int) $stmtRejected->fetchColumn();

  // --- Service credit balances ---
  function getBalance(PDO $pdo, int $empId, int $typeId): float {
      $s = $pdo->prepare(
          "SELECT
              COALESCE(SUM(CASE WHEN transaction_type='EARNED'   THEN credit_amount ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN transaction_type='DEDUCTED' THEN credit_amount ELSE 0 END), 0)
           FROM service_credit_transactions
           WHERE employee_id = ? AND credit_type_id = ?"
      );
      $s->execute([$empId, $typeId]);
      return (float) $s->fetchColumn();
  }
  $localBalance    = getBalance($pdo, $empId, 1);
  $nationalBalance = getBalance($pdo, $empId, 2);

  // --- 5 most recent leave requests ---
  $stmtRecent = $pdo->prepare(
      "SELECT lr.leave_request_id, lr.request_number, lr.date_filed,
              lr.total_leave_days, lr.status, lt.leave_type_name
       FROM leave_requests lr
       INNER JOIN leave_types lt ON lt.leave_type_id = lr.leave_type_id
       WHERE lr.employee_id = ?
       ORDER BY lr.created_at DESC
       LIMIT 5"
  );
  $stmtRecent->execute([$empId]);
  $recentLeaves = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
  ?>
  ```

  **HTML structure**:

  ```html
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BCSHS-SAC | My Dashboard</title>
    <link rel="stylesheet" href="../../styles/user.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>
  </head>
  <body>
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
    <div class="app-shell">

      <!-- SIDEBAR: My Dashboard active -->
      [shared sidebar, nav-item active on "My Dashboard"]

      <div class="main-wrapper">
        <!-- TOPBAR -->
        <header class="topbar" role="banner">
          <span class="topbar-icon" aria-hidden="true">
            <span></span><span></span><span></span><span></span>
          </span>
          <h1 class="topbar-title">My Dashboard</h1>
          <button class="hamburger" id="hamburger" type="button"
                  aria-controls="sidebar" aria-expanded="false"
                  aria-label="Toggle navigation menu">
            <span></span><span></span><span></span>
          </button>
        </header>

        <main class="page-content dashboard-content" id="mainContent">

          <!-- SECTION A: Stat cards row -->
          <div class="stat-cards-row">
            <!-- Card 1: Pending -->
            <div class="stat-card-h">
              <div class="stat-card-header">
                <div class="stat-icon-circle stat-icon--yellow">
                  <svg viewBox="0 0 24 24">...</svg>  <!-- clock/hourglass icon -->
                </div>
                <span class="stat-label-h">Pending Leave Request</span>
              </div>
              <div class="stat-number-h"><?= $pendingCount ?></div>
            </div>
            <!-- Card 2: Approved -->
            <div class="stat-card-h">
              <div class="stat-card-header">
                <div class="stat-icon-circle stat-icon--green">
                  <svg viewBox="0 0 24 24">...</svg>  <!-- checkmark icon -->
                </div>
                <span class="stat-label-h">Approved Leave Request</span>
              </div>
              <div class="stat-number-h"><?= $approvedCount ?></div>
            </div>
            <!-- Card 3: Rejected -->
            <div class="stat-card-h">
              <div class="stat-card-header">
                <div class="stat-icon-circle stat-icon--red">
                  <svg viewBox="0 0 24 24">...</svg>  <!-- X icon -->
                </div>
                <span class="stat-label-h">Rejected Leave Request</span>
              </div>
              <div class="stat-number-h"><?= $rejectedCount ?></div>
            </div>
          </div>

          <!-- SECTION B: SC Balance + Quick Action -->
          <div class="dashboard-bottom-row">
            <div class="sc-balance-card">
              <div class="sc-balance-heading">
                <svg viewBox="0 0 24 24"><!-- badge/award SVG --></svg>
                Service Credit Balance
              </div>
              <div class="sc-balance-rows">
                <div class="sc-balance-row">
                  <span class="sc-balance-label">Local</span>
                  <span class="sc-balance-value"><?= number_format($localBalance, 2) ?></span>
                </div>
                <div class="sc-balance-row">
                  <span class="sc-balance-label">National</span>
                  <span class="sc-balance-value"><?= number_format($nationalBalance, 2) ?></span>
                </div>
              </div>
              <a href="userServiceCredits.php" class="btn-yellow" style="align-self:flex-start;">
                View Service Credits
              </a>
            </div>
            <div class="quick-action-card">
              <div class="quick-action-title">Quick Action</div>
              <a href="userRequest.php" class="quick-action-link">
                <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="12" y2="15"/></svg>
                Request a Leave
              </a>
              <a href="userRequest.php" class="btn-yellow" style="align-self:flex-start;">
                Request a Leave
              </a>
            </div>
          </div>

          <!-- SECTION C: Recent leave requests table -->
          <div class="recent-leave-table">
            <div class="table-header-bar">
              <h3>My Recent Leave Request</h3>
            </div>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Leave Request #</th>
                  <th>Date Filed</th>
                  <th>Total Days</th>
                  <th>Status</th>
                  <th>View</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentLeaves)): ?>
                <tr class="empty-row"><td colspan="5">No leave requests found.</td></tr>
                <?php else: ?>
                <?php foreach ($recentLeaves as $lr): ?>
                <tr>
                  <td><?= htmlspecialchars($lr['request_number']) ?></td>
                  <td><?= date('m/d/Y', strtotime($lr['date_filed'])) ?></td>
                  <td><?= htmlspecialchars($lr['total_leave_days']) ?></td>
                  <td>
                    <?php
                    $badgeClass = match($lr['status']) {
                        'Approved' => 'status-badge--approved',
                        'Rejected' => 'status-badge--rejected',
                        default    => 'status-badge--pending',
                    };
                    ?>
                    <span class="status-badge <?= $badgeClass ?>">
                      <?= htmlspecialchars($lr['status']) ?>
                    </span>
                  </td>
                  <td><a href="#">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

        </main>
      </div>
    </div>

    <!-- Logout modal -->
    [shared #logoutModal HTML]

    <script src="../../scripts/user.js"></script>
  </body>
  </html>
  ```

  **SVG icons to use inline** (use exact paths from the task spec for sidebar icons; for stat card icons use these):
  - Pending (yellow card): `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>` (clock)
  - Approved (green card): `<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>` (checkmark)
  - Rejected (red card): `<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>` (X)
  - SC Balance heading: `<svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="5"/><path d="M7.5 14.5L5 21l7-2.5L19 21l-2.5-6.5"/></svg>` (award/badge)

  Files: `Pages/Users/userDashboard.php`

  Verify: Log in as an employee account (e.g. `khyranzarsuela` / check the db for the hash). Navigate to `/PERSYS/Pages/Users/userDashboard.php` — page renders with 3 stat cards, 2-column middle row, and the recent leave table (or empty state if no data).

---

- [ ] 6. **Create `Pages/Users/userRequest.php`**

  **PHP block at top**:

  ```php
  <?php
  require_once '../../session.php';
  start_app_session();
  require_user();

  $pdo   = db();
  $empId = (int) $_SESSION['employee_id'];

  // ── AJAX: submit leave request ──────────────────────────────
  if (!empty($_POST['ajax_submit_leave'])) {
      header('Content-Type: application/json');

      $leaveTypeId   = filter_input(INPUT_POST, 'leave_type_id',    FILTER_VALIDATE_INT);
      $startDate     = trim($_POST['start_date']     ?? '');
      $endDate       = trim($_POST['end_date']       ?? '');
      $totalDays     = filter_input(INPUT_POST, 'total_leave_days', FILTER_VALIDATE_FLOAT);
      $reasonDetails = trim($_POST['reason_details'] ?? '');

      if (!$leaveTypeId || !$startDate || !$endDate || !$totalDays || !$reasonDetails) {
          echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
          exit;
      }

      // Generate request number: LR-YYYY-NNNN
      $year    = date('Y');
      $stmtMax = $pdo->prepare(
          "SELECT MAX(CAST(SUBSTRING_INDEX(request_number, '-', -1) AS UNSIGNED))
           FROM leave_requests
           WHERE request_number LIKE ?"
      );
      $stmtMax->execute(['LR-' . $year . '-%']);
      $maxSeq  = (int) $stmtMax->fetchColumn();
      $reqNum  = 'LR-' . $year . '-' . str_pad($maxSeq + 1, 4, '0', STR_PAD_LEFT);

      $stmtIns = $pdo->prepare(
          "INSERT INTO leave_requests
             (request_number, employee_id, leave_type_id, date_filed,
              start_datetime, end_datetime, total_leave_days, reason_details, status)
           VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, 'Pending')"
      );
      $stmtIns->execute([
          $reqNum, $empId, $leaveTypeId,
          $startDate . ' 08:00:00',
          $endDate   . ' 17:00:00',
          $totalDays, $reasonDetails
      ]);

      echo json_encode(['success' => true, 'request_number' => $reqNum, 'status' => 'Pending']);
      exit;
  }

  // ── AJAX: save font size (from Settings page JS, harmless here) ──
  if (!empty($_POST['ajax_save_font_size'])) {
      // handled by settings page only; ignore here silently
      exit;
  }

  // ── Fetch all leave requests for this employee ──────────────
  $stmtReqs = $pdo->prepare(
      "SELECT lr.leave_request_id, lr.request_number, lr.date_filed,
              lr.start_datetime, lr.end_datetime, lr.total_leave_days,
              lr.status, lt.leave_type_name
       FROM leave_requests lr
       INNER JOIN leave_types lt ON lt.leave_type_id = lr.leave_type_id
       WHERE lr.employee_id = ?
       ORDER BY lr.created_at DESC"
  );
  $stmtReqs->execute([$empId]);
  $myRequests = $stmtReqs->fetchAll(PDO::FETCH_ASSOC);

  // ── Fetch active leave types ────────────────────────────────
  $stmtLT = $pdo->prepare("SELECT leave_type_id, leave_type_name FROM leave_types WHERE is_active = 1 ORDER BY leave_type_name");
  $stmtLT->execute([]);
  $leaveTypes = $stmtLT->fetchAll(PDO::FETCH_ASSOC);
  ?>
  ```

  **HTML structure** — same `<head>` template, same sidebar (Request active), topbar with document/clipboard SVG, then:

  ```html
  <main class="page-content request-content" id="mainContent">

    <!-- Section A: heading -->
    <div>
      <h2 class="request-page-heading">Request a Leave</h2>
      <p class="request-page-sub">File a leave request using the digital leave form.</p>
    </div>

    <!-- Section B: request card with button -->
    <div class="request-card">
      <div class="request-card-title">Request a leave</div>
      <p class="request-card-sub">Click the button below to open the leave request form.</p>
      <div>
        <button type="button" id="openLeaveFormBtn" class="btn-yellow">
          📎 Request a Leave
        </button>
      </div>
    </div>

    <!-- Section C: My Request table -->
    <div class="my-request-table">
      <div class="table-header-bar">
        <h3>My Request</h3>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>Request Number</th>
            <th>Date Filed</th>
            <th>Leave Dates</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($myRequests)): ?>
          <tr class="empty-row"><td colspan="5">No requests found.</td></tr>
          <?php else: ?>
          <?php foreach ($myRequests as $r): ?>
          <tr
            data-req-number="<?= htmlspecialchars($r['request_number']) ?>"
            data-leave-type="<?= htmlspecialchars($r['leave_type_name']) ?>"
            data-start="<?= htmlspecialchars(date('Y-m-d', strtotime($r['start_datetime']))) ?>"
            data-end="<?= htmlspecialchars(date('Y-m-d', strtotime($r['end_datetime']))) ?>"
            data-days="<?= htmlspecialchars($r['total_leave_days']) ?>"
            data-status="<?= htmlspecialchars($r['status']) ?>"
          >
            <td><?= htmlspecialchars($r['request_number']) ?></td>
            <td><?= date('m/d/Y', strtotime($r['date_filed'])) ?></td>
            <td>
              <?= date('m/d/Y', strtotime($r['start_datetime'])) ?>
              – <?= date('m/d/Y', strtotime($r['end_datetime'])) ?>
            </td>
            <td>
              <?php
              $bc = match($r['status']) {
                  'Approved' => 'status-badge--approved',
                  'Rejected' => 'status-badge--rejected',
                  default    => 'status-badge--pending',
              };
              ?>
              <span class="status-badge <?= $bc ?>"><?= htmlspecialchars($r['status']) ?></span>
            </td>
            <td><a href="#" class="view-request-btn">View</a></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
  ```

  **4 modals** (all placed before `</body>`, hidden with `style="display:none;"`):

  Modal 1 — `#leaveFormModal`:
  ```html
  <div class="modal-overlay" id="leaveFormModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="leaveFormTitle">
    <div class="modal-box">
      <div class="modal-header">
        <h2 id="leaveFormTitle">Request a leave</h2>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="leaveTypeSelect">Leave Type</label>
          <select id="leaveTypeSelect" name="leave_type_id" required>
            <option value="">-- Select Leave Type --</option>
            <?php foreach ($leaveTypes as $lt): ?>
            <option value="<?= (int)$lt['leave_type_id'] ?>">
              <?= htmlspecialchars($lt['leave_type_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="leaveStartDate">Start Date</label>
          <input type="date" id="leaveStartDate" name="start_date" required />
        </div>
        <div class="form-group">
          <label for="leaveEndDate">End Date</label>
          <input type="date" id="leaveEndDate" name="end_date" required />
        </div>
        <div class="form-group">
          <label for="totalLeaveDays">Total Leave Days (Weekdays)</label>
          <input type="number" id="totalLeaveDays" name="total_leave_days" readonly />
        </div>
        <div class="form-group">
          <label for="leaveReason">Reason / Details</label>
          <textarea id="leaveReason" name="reason_details" required placeholder="Briefly describe your reason..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="cancelLeaveFormBtn" class="btn-secondary">Cancel</button>
        <button type="button" id="reviewLeaveBtn" class="btn-yellow">Review →</button>
      </div>
    </div>
  </div>
  ```

  Modal 2 — `#reviewLeaveModal`:
  ```html
  <div class="modal-overlay" id="reviewLeaveModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="reviewLeaveTitle">
    <div class="modal-box">
      <div class="modal-header">
        <h2 id="reviewLeaveTitle">Review Leave Request</h2>
      </div>
      <div class="modal-body">
        <div class="modal-review-row">
          <span class="modal-review-label">Reason (Leave Type)</span>
          <span class="modal-review-value" id="reviewLeaveType"></span>
        </div>
        <div class="modal-review-row">
          <span class="modal-review-label">Start Date</span>
          <span class="modal-review-value" id="reviewStartDate"></span>
        </div>
        <div class="modal-review-row">
          <span class="modal-review-label">End Date</span>
          <span class="modal-review-value" id="reviewEndDate"></span>
        </div>
        <div class="modal-review-row">
          <span class="modal-review-label">Total Leave Days</span>
          <span class="modal-review-value" id="reviewTotalDays"></span>
        </div>
        <div class="modal-info-note">
          ℹ️ Approved leave requests may deduct from your service credit balance if applicable.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="backToEditBtn" class="btn-secondary">← Edit</button>
        <button type="button" id="submitLeaveBtn" class="btn-yellow">Request and Send</button>
      </div>
    </div>
  </div>
  ```

  Modal 3 — `#requestSentModal`:
  ```html
  <div class="modal-overlay" id="requestSentModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="requestSentTitle">
    <div class="modal-box" style="text-align:center;">
      <div class="modal-success-icon">
        <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <div class="modal-header" style="border:none; margin-bottom:8px;">
        <h2 id="requestSentTitle">Request Sent</h2>
      </div>
      <div class="modal-body">
        <p>Your leave request has been submitted successfully.</p>
        <p style="margin-top:10px;">
          Request Number: <strong id="sentRequestNumber"></strong><br/>
          Status: <span class="status-badge status-badge--pending">Pending</span>
        </p>
      </div>
      <div class="modal-footer" style="justify-content:center;">
        <button type="button" id="viewRequestAfterSend" class="btn-yellow">View Request</button>
      </div>
    </div>
  </div>
  ```

  Modal 4 — `#requestDetailsModal`:
  ```html
  <div class="modal-overlay" id="requestDetailsModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="requestDetailsTitle">
    <div class="modal-box">
      <div class="modal-header">
        <h2 id="requestDetailsTitle">Request Details</h2>
      </div>
      <div class="modal-body">
        <div class="modal-review-row">
          <span class="modal-review-label">Request Number</span>
          <span class="modal-review-value" id="detailRequestNumber"></span>
        </div>
        <div class="modal-review-row">
          <span class="modal-review-label">Status</span>
          <span class="modal-review-value" id="detailStatus"></span>
        </div>
        <div class="modal-review-row">
          <span class="modal-review-label">Reason (Leave Type)</span>
          <span class="modal-review-value" id="detailLeaveType"></span>
        </div>
        <div class="modal-review-row">
          <span class="modal-review-label">Start Date</span>
          <span class="modal-review-value" id="detailStartDate"></span>
        </div>
        <div class="modal-review-row">
          <span class="modal-review-label">End Date</span>
          <span class="modal-review-value" id="detailEndDate"></span>
        </div>
        <div class="modal-review-row">
          <span class="modal-review-label">Total Leave Days</span>
          <span class="modal-review-value" id="detailTotalDays"></span>
        </div>
        <p id="detailStatusNote" style="margin-top:10px; font-size:0.85rem; color:#888;"></p>
        <div class="modal-info-note" style="margin-top:10px;">
          ℹ️ Approved leave requests may deduct from your service credit balance if applicable.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="closeDetailsBtn" class="btn-yellow">← Back to Request</button>
      </div>
    </div>
  </div>
  ```

  Files: `Pages/Users/userRequest.php`

  Verify: Visit the page — request card and table render. Click "Request a Leave" — `#leaveFormModal` opens. Fill fields, click "Review →" — review modal shows correct data. Click "Request and Send" — AJAX posts and `#requestSentModal` shows with a real request number. After reload, the new row appears in the table and "View" opens `#requestDetailsModal`.

---

- [ ] 7. **Create `Pages/Users/userServiceCredits.php`**

  **PHP block at top**:

  ```php
  <?php
  require_once '../../session.php';
  start_app_session();
  require_user();

  $pdo   = db();
  $empId = (int) $_SESSION['employee_id'];

  // Balance helpers (same logic as dashboard)
  function scBalance(PDO $pdo, int $empId, int $typeId): float {
      $s = $pdo->prepare(
          "SELECT
              COALESCE(SUM(CASE WHEN transaction_type='EARNED'   THEN credit_amount ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN transaction_type='DEDUCTED' THEN credit_amount ELSE 0 END), 0)
           FROM service_credit_transactions
           WHERE employee_id = ? AND credit_type_id = ?"
      );
      $s->execute([$empId, $typeId]);
      return (float) $s->fetchColumn();
  }
  $localBalance    = scBalance($pdo, $empId, 1);
  $nationalBalance = scBalance($pdo, $empId, 2);

  // All transactions for this employee
  $stmtTx = $pdo->prepare(
      "SELECT sct.transaction_id, sct.activity_name, sct.activity_date,
              sct.credit_amount, sct.transaction_type, sct.created_at,
              sty.credit_type_name
       FROM service_credit_transactions sct
       INNER JOIN service_credit_types sty ON sty.credit_type_id = sct.credit_type_id
       WHERE sct.employee_id = ?
       ORDER BY sct.created_at DESC"
  );
  $stmtTx->execute([$empId]);
  $transactions = $stmtTx->fetchAll(PDO::FETCH_ASSOC);
  ?>
  ```

  **HTML structure** — same `<head>` template, same sidebar (Service Credits active), topbar with badge/award SVG, then:

  ```html
  <main class="page-content service-credits-content" id="mainContent">

    <h2 class="sc-page-heading">Your Service Credits</h2>

    <!-- Balance cards -->
    <div class="credit-balance-cards">
      <div class="credit-balance-card">
        <div class="credit-balance-card-title">Local Service Credits</div>
        <div class="credit-balance-number"><?= number_format($localBalance, 2) ?></div>
      </div>
      <div class="credit-balance-card">
        <div class="credit-balance-card-title">National Service Credits</div>
        <div class="credit-balance-number"><?= number_format($nationalBalance, 2) ?></div>
      </div>
    </div>

    <!-- Transaction history table -->
    <div class="sc-log-table">
      <div class="table-header-bar">
        <h3>Claim Log and Usage History</h3>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>Request Date</th>
            <th>Credit Type</th>
            <th>Service Activity</th>
            <th>Status</th>
            <th>Points</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($transactions)): ?>
          <tr class="empty-row"><td colspan="5">No service credit transactions found.</td></tr>
          <?php else: ?>
          <?php foreach ($transactions as $tx): ?>
          <tr>
            <td><?= date('m/d/Y', strtotime($tx['created_at'])) ?></td>
            <td><?= htmlspecialchars($tx['credit_type_name']) ?></td>
            <td><?= htmlspecialchars($tx['activity_name']) ?></td>
            <td>
              <?php
              $bc2 = $tx['transaction_type'] === 'EARNED' ? 'status-badge--approved' : 'status-badge--rejected';
              ?>
              <span class="status-badge <?= $bc2 ?>"><?= htmlspecialchars($tx['transaction_type']) ?></span>
            </td>
            <td><?= number_format((float)$tx['credit_amount'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
  ```

  Logout modal + `<script>` at bottom.

  Files: `Pages/Users/userServiceCredits.php`

  Verify: Visit the page — two balance cards show (0.00 if no data), table shows transactions or empty state. No PHP errors.

---

- [ ] 8. **Create `Pages/Users/userSettings.php`**

  **PHP block at top**:

  ```php
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
  $savedFontSize = $stmtFS->fetchColumn() ?: '16px';
  $savedFontSizeNum = (int) filter_var($savedFontSize, FILTER_SANITIZE_NUMBER_INT);
  ?>
  ```

  **HTML structure** — same `<head>` template (also store `$savedFontSizeNum` in a JS var via a `<script>` in `<head>` or just pass it via a `data-` attribute on the slider), same sidebar (Settings active), topbar with waffle icon, then:

  ```html
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
  ```

  Logout modal + `<script>` at bottom.

  Files: `Pages/Users/userSettings.php`

  Verify: Visit the page — two-column settings grid renders, sliders are interactive, mode options switch `.selected` class on click, toggles work, font size slider AJAX-posts (check Network tab in DevTools → POST with `ajax_save_font_size=1`).

---

## File Change Summary

| File | Action |
|------|--------|
| `Pages/Users/logout.php` | CREATE (new) |
| `styles/user.css` | APPEND at bottom only |
| `scripts/user.js` | APPEND at bottom only |
| `Pages/Users/userProfile.php` | FIX ONLY (title, sidebar, script tag) |
| `Pages/Users/userDashboard.php` | FULL REBUILD (was a stub) |
| `Pages/Users/userRequest.php` | CREATE (new) |
| `Pages/Users/userServiceCredits.php` | CREATE (new) |
| `Pages/Users/userSettings.php` | CREATE (new) |

**Admin files — never touched:** `Pages/Admin/*`, `styles/admin.css`, `styles/admin-profile.css`, `scripts/admin.js`.

## Database Tables Used

| Table | Used by |
|-------|---------|
| `leave_requests` | Dashboard (counts + recent), Request (list + insert) |
| `leave_types` | Dashboard (join), Request (dropdown + join) |
| `service_credit_transactions` | Dashboard (balances), Service Credits (balances + list) |
| `service_credit_types` | Service Credits (join for type name) |
| `account_settings` | Settings (save/load font_size) |

## Key Patterns

- **Every parameterized query**: `$pdo->prepare("... WHERE col = ?"); $stmt->execute([$value]);` — never concatenate `$_POST`/`$_SESSION` values into SQL strings.
- **Session access**: `$_SESSION['employee_id']` (cast to int), `$_SESSION['account_id']` (cast to int), `$_SESSION['username']` and `$_SESSION['role_name']` (always through `htmlspecialchars()` in output).
- **PHP output escaping**: `htmlspecialchars()` for all string output, `number_format(..., 2)` for credit balances, `date('m/d/Y', strtotime(...))` for dates.
- **jQuery CDN** in `<head>` before `user.js`. The `user.js` script tag goes at the bottom of `<body>`.
- **All 5 PHP files share** the exact same sidebar HTML — the only thing that changes is which nav-item has `class="nav-item active"`.
- **`match()` expression** (PHP 8+) for mapping status → CSS badge class; the project runs PHP 8.2 (per the SQL dump header).
