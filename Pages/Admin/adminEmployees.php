<?php

require_once '../../session.php';

start_app_session();
require_admin();

$flashType    = '';
$flashMessage = '';
if (isset($_GET['added']) && $_GET['added'] === '1') {
    $flashType = 'success';
} elseif (isset($_GET['error'])) {
    $flashType    = 'error';
    $flashMessage = match($_GET['error']) {
        'duplicate_employee_number' => 'An employee with that employee number already exists.',
        'duplicate_email'           => 'An employee with that DepEd email already exists.',
        'validation'                => htmlspecialchars($_GET['msg'] ?? 'Please check all required fields.'),
        'db_error'                  => 'A database error occurred. Please try again.',
        'duplicate_username'        => 'That username is already taken.',
        'already_has_account'       => 'This employee already has an account.',
        'empty_fields'              => 'All fields are required to create an account.',
        default                     => 'An error occurred. Please try again.',
    };
}

$pdo = db();


$stmt = $pdo->prepare("
    SELECT * FROM employees WHERE personnel_type = 'Teaching';
");

/*  SELECT
        e.employee_id,
        e.employee_number,
        e.last_name,
        e.first_name,
        e.middle_name,
        e.plantilla_item_number,
        e.position,
        e.salary_grade,
        e.step_increment,
        e.date_original_appointment,
        e.date_last_promotion,
        e.deped_email,
        e.personnel_type,

        a.username,
        r.role_name

    FROM employees e

    LEFT JOIN accounts a
        ON a.employee_id = e.employee_id

    INNER JOIN roles r
        ON r.role_id = a.role_id
        AND personnel_type = 'Teaching'
*/
$stmt->execute();

$TeachingEmployees = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT * FROM employees WHERE personnel_type = 'Non-Teaching';
");

$stmt->execute();

$NonTeachingEmployees = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
    employees.employee_id,
    employees.employee_number, 
    employees.first_name, 
    employees.middle_name, 
    employees.last_name, 
    employees.personnel_type, 
    employees.deped_email,
    accounts.is_active
    FROM employees
    LEFT JOIN accounts
    ON employees.employee_id = accounts.employee_id;
");
$stmt->execute();

$Accounts = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSHS-SAC | Employees</title>
  <link rel="stylesheet" href="../../styles/admin.css" />

  <!-- ============================================================
       PAGE-SCOPED STYLES — adminEmployees.html only
       All variables inherited from admin.css via :root
  ============================================================ -->
  <style>
    /* ── EMPLOYEES SUB-MENU ────────────────────────────────── */

    /* Parent "Employees" item — needs space for the caret */
    .nav-item--employees {
      justify-content: space-between;
    }

    .nav-item--employees .nav-item-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Animated caret ▼ */
    .employees-caret {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      transition: transform 0.25s ease;
      flex-shrink: 0;
    }

    .employees-caret.is-open {
      transform: rotate(180deg);
    }

    /* Sub-menu container — hidden by default, slides open */
    .employees-submenu {
      list-style: none;
      overflow: hidden;
      max-height: 0;
      transition: max-height 0.3s ease;
      padding: 0 10px;
    }

    .employees-submenu.is-open {
      max-height: 180px; /* enough for 3 items */
    }

    /* Sub-menu items */
    .submenu-item {
      display: block;
      width: 100%;
      padding: 9px 14px;
      border-radius: 6px;
      font-size: 0.78rem;
      font-weight: 600;
      color: #D4C5E8;
      cursor: pointer;
      background: none;
      border: none;
      text-align: left;
      transition: background-color 0.2s ease, color 0.2s ease;
      margin-bottom: 2px;
    }

    .submenu-item:hover {
      background-color: rgba(255, 255, 255, 0.1);
      color: #fff;
    }

    /* Active sub-menu item — yellow pill */
    .submenu-item.is-active {
      background-color: #F5C518;
      color: #1a1a1a;
    }

    /* ── PAGE CONTENT — EMPLOYEES OVERRIDE ────────────────── */

    /* Override the dashboard's 2-col grid for this page */
    .page-content.employees-page {
      display: flex;
      flex-direction: column;
      gap: 24px;
      padding: 28px 32px;
    }

    /* ── TOOLBAR ROW ────────────────────────────────────────── */

    .emp-toolbar {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    /* "+ Add Employee" button */
    .btn-add-employee {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 20px;
      background-color: #F5C518;
      color: #1a1a1a;
      font-size: 0.875rem;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      white-space: nowrap;
      transition: background-color 0.2s ease, box-shadow 0.2s ease;
      flex-shrink: 0;
    }

    .btn-add-employee:hover {
      background-color: #e6b800;
      box-shadow: 0 3px 10px rgba(245, 197, 24, 0.4);
    }

    /* Search bar wrapper */
    .search-wrap {
      position: relative;
      flex: 1;
      min-width: 200px;
      max-width: 540px;
    }

    .search-wrap input {
      width: 100%;
      padding: 10px 42px 10px 16px;
      border: 1.5px solid #D0C8B8;
      border-radius: 8px;
      font-size: 0.875rem;
      color: #444;
      background-color: #fff;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .search-wrap input::placeholder {
      color: #aaa;
    }

    .search-wrap input:focus {
      border-color: #6B0FBA;
      box-shadow: 0 0 0 3px rgba(107, 15, 186, 0.12);
    }

    /* Search icon inside input */
    .search-icon {
      position: absolute;
      right: 13px;
      top: 50%;
      transform: translateY(-50%);
      width: 17px;
      height: 17px;
      stroke: #999;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      pointer-events: none;
    }

    /* Filter button */
    .btn-filter {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background-color: #F5C518;
      color: #1a1a1a;
      font-size: 0.875rem;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      white-space: nowrap;
      transition: background-color 0.2s ease, box-shadow 0.2s ease;
      flex-shrink: 0;
    }

    .btn-filter:hover {
      background-color: #e6b800;
      box-shadow: 0 3px 10px rgba(245, 197, 24, 0.4);
    }

    .btn-filter svg {
      width: 14px;
      height: 14px;
      stroke: #1a1a1a;
      fill: none;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* ── RECORDS CARD ───────────────────────────────────────── */

    .records-card {
      background-color: #FEFCF5;
      border: 1px solid #E8E0CC;
      border-radius: 16px;
      padding: 28px 24px 32px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .records-title {
      font-size: 1.0625rem;
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 20px;
    }

    /* ── PANEL VISIBILITY ───────────────────────────────────── */

    /* Each sub-section (teaching / non-teaching) */
    .emp-panel {
      display: none;
    }

    .emp-panel.is-active {
      display: block;
    }

    /* ── TABLE WRAPPER — horizontal scroll ─────────────────── */

    .table-scroll-wrap {
      overflow-x: auto;
      border: 1px solid #DDD6C8;
      border-radius: 10px;
    }

    /* Scrollbar styling */
    .table-scroll-wrap::-webkit-scrollbar {
      height: 6px;
    }

    .table-scroll-wrap::-webkit-scrollbar-track {
      background: #f0ebe0;
      border-radius: 10px;
    }

    .table-scroll-wrap::-webkit-scrollbar-thumb {
      background: #c0b0d0;
      border-radius: 10px;
    }

    /* ── TABLE ──────────────────────────────────────────────── */

    .emp-table {
      width: 100%;
      min-width: 1100px;
      border-collapse: collapse;
      font-size: 0.8125rem;
    }

    /* Header row */
    .emp-table thead tr {
      background-color: #ffffff;
      border-bottom: 2px solid #DDD6C8;
    }

    .emp-table thead th {
      padding: 12px 16px;
      text-align: left;
      font-size: 0.8125rem;
      font-weight: 700;
      color: #222;
      white-space: nowrap;
    }

    /* Body rows — alternating stripe */
    .emp-table tbody tr:nth-child(odd) {
      background-color: #EDE7F6; /* light lavender */
    }

    .emp-table tbody tr:nth-child(even) {
      background-color: #ffffff;
    }

    .emp-table tbody tr:hover {
      background-color: #E0D6EF;
    }

    .emp-table tbody td {
      padding: 11px 16px;
      color: #333;
      white-space: nowrap;
      border-bottom: 1px solid #EDE8DC;
    }

    .emp-table tbody tr:last-child td {
      border-bottom: none;
    }

    /* ── RESPONSIVE ─────────────────────────────────────────── */
    @media (max-width: 768px) {
      .page-content.employees-page {
        padding: 20px 16px;
      }

      .emp-toolbar {
        gap: 10px;
      }

      .search-wrap {
        min-width: 140px;
      }
    }

    @media (max-width: 480px) {
      .emp-toolbar {
        flex-direction: column;
        align-items: stretch;
      }

      .btn-add-employee,
      .btn-filter {
        justify-content: center;
      }

      .search-wrap {
        max-width: 100%;
      }
    }

    /* Style the main button */
#openBtn {
  padding: 12px 24px;
  font-size: 16px;
  cursor: pointer;
}

/* Style the big card window */
dialog {
  padding: 24px;
  border: none;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  max-width: 500px;
  width: 90%;
}

/* Style the gray/faded background behind the card */
dialog::backdrop {
  background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
  backdrop-filter: blur(4px);            /* Optional: blurs the background */
}
/* ============================================================
   MASTER FILE IMPORT
============================================================ */

#importFileStatus {
  margin-top: 12px;
}


/* Summary */

.import-summary {
  margin: 20px 0 12px;
  padding: 12px 14px;
  background-color: #f5f0fb;
  border: 1px solid #ddd0ec;
  border-radius: 8px;
  font-size: 0.875rem;
}


/* Preview table */

.import-table-wrap {
  width: 100%;
  overflow-x: auto;
  border: 1px solid #DDD6C8;
  border-radius: 10px;
  margin-top: 12px;
}


.import-preview-table {
  min-width: 1700px;
}

.import-input {
  width: 100%;
  min-width: 120px;
  box-sizing: border-box;

  padding: 7px 9px;

  border: 1px solid #D0C8B8;
  border-radius: 6px;

  background-color: #ffffff;

  font-family: inherit;
  font-size: 0.8rem;
  color: #333;

  outline: none;
}


.import-input:focus {
  border-color: #6B0FBA;
  box-shadow: 0 0 0 2px rgba(107, 15, 186, 0.12);
}


.import-input[type="number"] {
  min-width: 80px;
}


.import-input[type="date"] {
  min-width: 140px;
}


.import-input[type="email"] {
  min-width: 220px;
}


.import-preview-table td {
  vertical-align: top;
}


.import-status {
  white-space: nowrap;
}


/* Valid / invalid rows */

.import-valid {
  background-color: #ffffff;
}


.import-invalid {
  background-color: #fff1f1 !important;
}


/* Error messages */

.import-errors {
  margin-top: 5px;
  color: #b42318;
  font-size: 0.72rem;
  line-height: 1.4;
}


/* Disabled upload button */

#btnUploadMaster:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ── ADD EMPLOYEE MODAL ─────────────────────────────────────────────────── */
dialog#addEmployeeDialog {
  max-width: 680px;
  width: 95%;
  padding: 28px 28px 24px;
}

.add-emp-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-top: 16px;
}

.add-emp-grid .full-width {
  grid-column: 1 / -1;
}

.add-emp-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.add-emp-field label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #222;
}

.add-emp-field input,
.add-emp-field select {
  width: 100%;
  padding: 9px 12px;
  border: 1.5px solid #D0C8B8;
  border-radius: 6px;
  font-size: 0.875rem;
  outline: none;
  box-sizing: border-box;
  font-family: inherit;
}

.add-emp-field input:focus,
.add-emp-field select:focus {
  border-color: #6B0FBA;
  box-shadow: 0 0 0 3px rgba(107,15,186,0.12);
}

.add-emp-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

.btn-add-emp-submit {
  background-color: #F5C518;
  color: #1a1a1a;
  font-weight: 700;
  padding: 10px 24px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 0.875rem;
}

.btn-add-emp-submit:hover {
  background-color: #e6b800;
}

.btn-add-emp-cancel {
  background: transparent;
  border: 1.5px solid #ccc;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.875rem;
}

/* ── FLASH BANNER ──────────────────────────────────────────────────────── */
.flash-banner {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.flash-banner.flash-error {
  background-color: #fff1f1;
  border: 1.5px solid #f5c6c6;
  color: #b42318;
}

.flash-banner .flash-dismiss {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  color: inherit;
  line-height: 1;
  padding: 0;
}

  </style>
</head>
<body>

  <!-- Mobile sidebar overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

  <div class="app-shell">

    <!-- ══════════════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar" aria-label="Admin navigation">

      <!-- Profile block -->
      <img
        class="sidebar-avatar"
        src="https://placehold.co/72x72/6B0FBA/FFFFFF?text=A"
        alt="Admin profile photo"
      />
      <p class="sidebar-name"><?php echo $_SESSION['username']; ?></p>
      <p class="sidebar-role"> <?php echo $_SESSION['role_name']; ?></p>

      <div class="sidebar-divider" role="separator"></div>

      <nav aria-label="Main menu">
        <ul class="sidebar-nav">

          <!-- My Dashboard -->
          <li>
            <a href="adminDashboard.php" class="nav-item">
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

          <!-- Profile -->
          <li>
            <a href="adminProfile.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <circle cx="12" cy="8" r="4"/>
                  <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
              </span>
              Profile
            </a>
          </li>

          <!-- Employees — active parent with sub-menu -->
          <li>
            <button
              type="button"
              class="nav-item nav-item--employees active"
              id="employeesToggle"
              aria-expanded="false"
              aria-controls="employeesSubmenu"
            >
              <span class="nav-item-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <circle cx="9" cy="8" r="3.5"/>
                    <path d="M2 20c0-3.3 3.1-6 7-6s7 2.7 7 6"/>
                    <circle cx="17" cy="8" r="2.5"/>
                    <path d="M20 20c0-2.5-1.9-4.5-4.5-5"/>
                  </svg>
                </span>
                Employees
              </span>
              <!-- Caret arrow -->
              <svg class="employees-caret" id="employeesCaret" viewBox="0 0 24 24" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </button>

            <!-- Sub-menu -->
            <ul class="employees-submenu" id="employeesSubmenu" role="menu">
              <li role="none">
                <button
                  type="button"
                  class="submenu-item is-active"
                  id="btnTeaching"
                  role="menuitem"
                  aria-pressed="true"
                  data-panel="teaching"
                >
                  Teaching Employees
                </button>
              </li>
              <li role="none">
                <button
                  type="button"
                  class="submenu-item"
                  id="btnNonTeaching"
                  role="menuitem"
                  aria-pressed="false"
                  data-panel="non-teaching"
                >
                  Non-Teaching Employees
                </button>
              </li>
               <li role="none">
                <button
                  type="button"
                  class="submenu-item"
                  id="btnAccounts"
                  role="menuitem"
                  aria-pressed="false"
                  data-panel="accounts"
                >
                  Accounts
                </button>
              </li>
            </ul>
          </li>

          <!-- Service Credits -->
          <li>
            <a href="adminServiceCredits.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <circle cx="12" cy="9" r="5"/>
                  <path d="M7.5 14.5L5 21l7-2.5L19 21l-2.5-6.5"/>
                </svg>
              </span>
              Service Credits
            </a>
          </li>

          <!-- Transactions -->
          <li>
            <a href="adminTransactions.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <rect x="4" y="2" width="16" height="20" rx="2"/>
                  <line x1="8" y1="7" x2="16" y2="7"/>
                  <line x1="8" y1="11" x2="16" y2="11"/>
                  <line x1="8" y1="15" x2="12" y2="15"/>
                </svg>
              </span>
              Transactions
            </a>
          </li>

          <!-- Settings -->
          <li>
            <a href="adminSettings.html" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="3"/>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83
                           2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33
                           1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09
                           A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33
                           l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06
                           A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1
                           H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9
                           a1.65 1.65 0 0 0-.33-1.82l-.06-.06
                           a2 2 0 0 1 2.83-2.83l.06.06
                           A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51
                           V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51
                           1.65 1.65 0 0 0 1.82-.33l.06-.06
                           a2 2 0 0 1 2.83 2.83l-.06.06
                           A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1
                           H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
                </svg>
              </span>
              Settings
            </a>
          </li>

        </ul>
      </nav>
    </aside>
    <!-- END SIDEBAR -->

    <!-- ══════════════════════════════════════════════════════
         MAIN WRAPPER
    ══════════════════════════════════════════════════════ -->
    <div class="main-wrapper">

      <!-- Topbar -->
      <header class="topbar" role="banner">
        <div class="topbar-icon" aria-hidden="true">
          <span></span><span></span>
          <span></span><span></span>
        </div>
        <h1 class="topbar-title">
          <!-- People icon inline before title -->
          <svg style="width:26px;height:26px;stroke:#F5C518;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round;vertical-align:middle;margin-right:8px;" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="9" cy="8" r="3.5"/>
            <path d="M2 20c0-3.3 3.1-6 7-6s7 2.7 7 6"/>
            <circle cx="17" cy="8" r="2.5"/>
            <path d="M20 20c0-2.5-1.9-4.5-4.5-5"/>
          </svg>
          Employees
        </h1>
        <button class="hamburger" id="hamburger" type="button"
          aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation menu">
          <span></span><span></span><span></span>
        </button>
      </header>

      <!-- Page content -->
      <main class="page-content employees-page" id="mainContent">

<?php if ($flashType === 'error'): ?>
<div class="flash-banner flash-error" role="alert" id="flashBanner">
  <span><?php echo $flashMessage; ?></span>
  <button type="button" class="flash-dismiss" aria-label="Dismiss" onclick="document.getElementById('flashBanner').remove()">&times;</button>
</div>
<?php endif; ?>

        <!-- ── TOOLBAR ──────────────────────────────────────── -->
        <div class="emp-toolbar" role="toolbar" aria-label="Employee actions">

          <!-- Add Employee button (UI only — no action) -->
          <button type="button" class="btn-add-employee" aria-label="Add new employee">
            + Add Employee
          </button>

          <!-- Search bar -->
          <div class="search-wrap">
            <input
              type="search"
              id="empSearch"
              placeholder="Search..."
              aria-label="Search employees"
              autocomplete="off"
            />
            <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="11" cy="11" r="7"/>
              <line x1="16.5" y1="16.5" x2="22" y2="22"/>
            </svg>
          </div>

          <!-- Filter button -->
          <button type="button" class="btn-filter" id="btnFilter" aria-label="Filter records" aria-expanded="false">
            Filter
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>

           <!-- Trigger Button -->
        <button id="openBtn" class="btn-add-employee" aria-label="Upload Master File">Master File Upload</button>

      <!-- The Dialog / Big Card -->
        <dialog id="myDialog">
           <h2>Import Employee Master File</h2>
            <p>Upload Excel file</p>
            <p>Supported: .xlsx / .xls</p>

           <form action="uploadMasterFile.php" method="post" enctype="multipart/form-data" id="masterFileForm" aria-label="Upload Excel file form">
    <!-- File Upload button -->
          <input type="file" name="masterfile" id="fileUpload" accept=".xlsx,.xls"required aria-label="Select Excel file to upload">
          <button type="submit" class="btn-filter" id="btnUploadMaster" aria-label="File Upload" aria-expanded="false">
            File Upload & Preview
          </button>
    </form>

         <!-- Upload/validation message -->
    <p id="masterFileStatus" role="status"></p>

    <!-- Excel preview will appear here -->
    <div id="masterFilePreview"></div>
     
        <button id="closeBtn">Close</button>
      </dialog>
        </div>
        
        <!-- END TOOLBAR -->
        <!-- Records Card -->
        <div class="records-card">

          <!-- Teaching panel (shown by default) -->
          <div class="emp-panel is-active" id="panel-teaching">
            <h2 class="records-title">Teaching Employee Records</h2>
            <div class="table-scroll-wrap">
              <table class="emp-table" aria-label="Teaching employee records">
              <thead>
                <tr>
                  <th>Last Name</th>
                  <th>First Name</th>
                  <th>Middle Name</th>
                  <th>Plantilla Number</th>
                  <th>Position</th>
                  <th>Salary Grade</th>
                  <th>Step Increment</th>
                  <th>Date of Original Appointment</th>
                  <th>Date of Last Promotion</th>
                  <th>DepEd Email</th>
                </tr>
              </thead>
                <tbody>
                      <?php if (empty($TeachingEmployees)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">No teaching employee records found.</td>
                        </tr>

                <?php else: ?>
                     <?php foreach ($TeachingEmployees as $request): ?>  
              <tr>
             

              <td><?= htmlspecialchars($request['last_name']) ?></td><td><?= htmlspecialchars($request['first_name']) ?></td><td><?= htmlspecialchars($request['middle_name']) ?></td><td><?= htmlspecialchars($request['plantilla_item_number']) ?></td><td><?= htmlspecialchars($request['position']) ?></td><td><?= htmlspecialchars($request['salary_grade']) ?></td><td><?= htmlspecialchars($request['step_increment']) ?></td>
              <td>
                 <?= date(
                    'm/d/y',
                    strtotime($request['date_original_appointment'])
                ) ?>
              </td><td><?= !empty($request['date_last_promotion']) 
        ? date('m/d/y', strtotime($request['date_last_promotion'])) 
        : 'None' ?></td><td><?= htmlspecialchars($request['deped_email']) ?></td></tr>

               <?php endforeach; ?>

<?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Non-Teaching panel (hidden by default) -->
          <div class="emp-panel" id="panel-non-teaching">
            <h2 class="records-title">Non-Teaching Employee Records</h2>
            <div class="table-scroll-wrap">
              <table class="emp-table" aria-label="Non-teaching employee records">
              <thead>
                <tr>
                  <th>Last Name</th>
                  <th>First Name</th>
                  <th>Middle Name</th>
                  <th>Plantilla Number</th>
                  <th>Position</th>
                  <th>Salary Grade</th>
                  <th>Step Increment</th>
                  <th>Date of Original Appointment</th>
                  <th>Date of Last Promotion</th>
                  <th>DepEd Email</th>
                </tr>
              </thead>
                <tbody>
                     <?php if (empty($NonTeachingEmployees)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">No teaching employee records found.</td>
                        </tr>

                <?php else: ?>
                     <?php foreach ($NonTeachingEmployees as $request): ?>  
              <tr>
             

              <td><?= htmlspecialchars($request['last_name']) ?></td><td><?= htmlspecialchars($request['first_name']) ?></td><td><?= htmlspecialchars($request['middle_name']) ?></td><td><?= htmlspecialchars($request['plantilla_item_number']) ?></td><td><?= htmlspecialchars($request['position']) ?></td><td><?= htmlspecialchars($request['salary_grade']) ?></td><td><?= htmlspecialchars($request['step_increment']) ?></td>
              <td>
                 <?= date(
                    'm/d/y',
                    strtotime($request['date_original_appointment'])
                ) ?>
              </td><td><?= !empty($request['date_last_promotion']) 
        ? date('m/d/y', strtotime($request['date_last_promotion'])) 
        : 'None' ?></td><td><?= htmlspecialchars($request['deped_email']) ?></td></tr>

               <?php endforeach; ?>

<?php endif; ?>
              
                </tbody>
              </table>
            </div>
            
             
          </div>

          <div class="emp-panel" id="panel-accounts">
              <h2 class="records-title">Account Records</h2>
            <div class="table-scroll-wrap">
              <table class="emp-table" aria-label="Account records">
              <thead>
                <tr>
                  <th>Employee Number</th>
                  <th>Full Name</th>
                  <th>Personnel Type</th>
                  <th>DepEd Email</th>
                  <th>Account Status</th>
                  <th>Action</th>
                </tr>
              </thead>
               <tbody>
     <?php if (empty($Accounts)): ?>
        <tr>
            <td colspan="6" style="text-align: center;">No account records found.</td>
        </tr>
     <?php else: ?>
         <?php foreach ($Accounts as $row): ?>  
              <tr>
                  <td><?= htmlspecialchars($row['employee_number']) ?></td>
                  <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) ?></td>
                  <td><?= htmlspecialchars($row['personnel_type']) ?></td>
                  <td><?= htmlspecialchars($row['deped_email']) ?></td>
                  <td><?= htmlspecialchars($row['is_active'] === 1 ? 'Active' : 'No Account') ?></td>
                  <td>
                      <?php if ($row['is_active'] === 1): ?>
                          <button type="button">View</button>
                      <?php else: ?>
                          <!-- Added unique class and data attributes to supply the data dynamically -->
                          <button type="button" 
                                  class="btn-add-employee openCreateAccountBtn" 
                                  aria-label="Create Account"
                                  data-id="<?= htmlspecialchars($row['employee_id']) ?>"
                                  data-number="<?= htmlspecialchars($row['employee_number']) ?>"
                                  data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) ?>"
                                  data-type="<?= htmlspecialchars($row['personnel_type']) ?>"
                                  data-email="<?= htmlspecialchars($row['deped_email']) ?>">
                              Create Account</button>
                      <?php endif; ?>
                  </td>
              </tr>
         <?php endforeach; ?>
     <?php endif; ?>

              
                </tbody>
              </table>
      <!-- The Dialog / Big Card -->
      <dialog id="createAccountDialog">
    <h2>Register Account</h2>
    <p>Register a new account for the selected employee.</p>

    <form action="createAccount.php" method="POST" id="accountForm" aria-label="Create Account form">
        <!-- Kept empty; JavaScript will fill these values -->
        <input type="hidden" id="modal_employee_id" name="employee_id" value="">

        <div>
            <label for="employee_number">Employee Number</label>
            <input type="text" id="employee_number" name="employee_number" readonly>
        </div>

        <div>
            <label for="employee_name">Employee Name</label>
            <input type="text" id="employee_name" name="employee_name" readonly>
        </div>

        <div>
            <label for="role_id">Account Role</label>
            <select id="role_id" name="role_id" required>
                <option value="">-- Select a Role --</option>
                <option value="1">Admin</option>
                <option value="2">Teaching</option>
                <option value="3">Non-Teaching</option>
            </select>
        </div>
                  
        <div>
            <label for="deped_email">DepEd Email</label>
            <input type="email" id="deped_email" name="deped_email" readonly>
        </div>

        <div>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="juandelacruz">
        </div>

        <div>
            <label for="password">Temporary Password</label>
            <input type="password" id="password" name="password" placeholder="********">
        </div>

        <div>
            <button id="closeCreateAccount" type="button">Close</button>
            <button type="submit">Create Account</button>
        </div>
    </form>
</dialog>

            </div>
            
             
          </div>
             </div>

        </div>
        <!-- END RECORDS CARD -->

<!-- ── ADD EMPLOYEE DIALOG ─────────────────────────────────────────────── -->
<dialog id="addEmployeeDialog">
  <h2>Add Employee</h2>
  <p>Fill in the employee details below. All fields marked * are required.</p>

  <form action="AddEmployees.php" method="POST" id="addEmployeeForm">
    <div class="add-emp-grid">

      <!-- Row 1: Last Name | First Name | Middle Name -->
      <div class="add-emp-field">
        <label for="ae_last_name">Last Name *</label>
        <input type="text" id="ae_last_name" name="last_name" required autocomplete="off">
      </div>
      <div class="add-emp-field">
        <label for="ae_first_name">First Name *</label>
        <input type="text" id="ae_first_name" name="first_name" required autocomplete="off">
      </div>
      <div class="add-emp-field">
        <label for="ae_middle_name">Middle Name</label>
        <input type="text" id="ae_middle_name" name="middle_name" autocomplete="off">
      </div>

      <!-- Row 2: Employee Number | Personnel Type -->
      <div class="add-emp-field">
        <label for="ae_employee_number">Employee Number *</label>
        <input type="text" id="ae_employee_number" name="employee_number" required placeholder="EMP-YYYY-XXXX" autocomplete="off">
      </div>
      <div class="add-emp-field">
        <label for="ae_personnel_type">Personnel Type *</label>
        <select id="ae_personnel_type" name="personnel_type" required>
          <option value="">-- Select --</option>
          <option value="Teaching">Teaching</option>
          <option value="Non-Teaching">Non-Teaching</option>
        </select>
      </div>

      <!-- Row 3: Plantilla Item Number | Position -->
      <div class="add-emp-field">
        <label for="ae_plantilla">Plantilla Item No. *</label>
        <input type="text" id="ae_plantilla" name="plantilla_item_number" required autocomplete="off">
      </div>
      <div class="add-emp-field">
        <label for="ae_position">Position *</label>
        <input type="text" id="ae_position" name="position" required autocomplete="off">
      </div>

      <!-- Row 4: Salary Grade | Step Increment -->
      <div class="add-emp-field">
        <label for="ae_salary_grade">Salary Grade *</label>
        <input type="number" id="ae_salary_grade" name="salary_grade" min="1" required>
      </div>
      <div class="add-emp-field">
        <label for="ae_step_increment">Step Increment *</label>
        <input type="number" id="ae_step_increment" name="step_increment" min="1" required>
      </div>

      <!-- Row 5: Date of Original Appointment | Date of Last Promotion -->
      <div class="add-emp-field">
        <label for="ae_date_orig">Date of Original Appointment *</label>
        <input type="date" id="ae_date_orig" name="date_original_appointment" required>
      </div>
      <div class="add-emp-field">
        <label for="ae_date_promo">Date of Last Promotion</label>
        <input type="date" id="ae_date_promo" name="date_last_promotion">
      </div>

      <!-- Row 6: DepEd Email (full width) -->
      <div class="add-emp-field full-width">
        <label for="ae_deped_email">DepEd Email *</label>
        <input type="email" id="ae_deped_email" name="deped_email" required autocomplete="off">
      </div>

    </div>

    <div class="add-emp-actions">
      <button type="button" id="closeAddEmployee" class="btn-add-emp-cancel">Cancel</button>
      <button type="submit" class="btn-add-emp-submit">Add Employee</button>
    </div>
  </form>
</dialog>

<!-- ── SUCCESS ADD DIALOG ──────────────────────────────────────────────── -->
<dialog id="successAddDialog">
  <div style="text-align:center; padding: 12px 0 8px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"
         fill="none" stroke="#22a85a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
         style="display:block;margin:0 auto 12px;">
      <circle cx="12" cy="12" r="10"/>
      <polyline points="9 12 11 14 15 10"/>
    </svg>
    <h2 style="margin:0 0 8px;">Employee Added</h2>
    <p style="color:#555;margin:0 0 6px;">The employee has been successfully added and is now available in the employee records.</p>
    <p style="color:#555;margin:0 0 20px;">You can now create an account for them from the Accounts tab.</p>
    <button type="button" id="closeSuccessDialog" class="btn-add-emp-submit">Done</button>
  </div>
</dialog>

      </main>
    </div>
    <!-- END MAIN WRAPPER -->

  </div>
  <!-- END APP SHELL -->
  <!-- Shared admin JS (hamburger, sidebar, nav highlight) -->
  <script src="../../scripts/admin.js"></script>

  <!-- ============================================================
       PAGE-SCOPED JS � adminEmployees.html
       Handles:
         1. Employees sub-menu open/close toggle
         2. Teaching / Non-Teaching panel swap
       No fake fetches, no alerts, no timeouts.
  ============================================================ -->
  <script>
  (function () {
    'use strict';

    /* -- Element refs ------------------------------------ */
    var toggle   = document.getElementById('employeesToggle');
    var submenu  = document.getElementById('employeesSubmenu');
    var caret    = document.getElementById('employeesCaret');
    var subBtns  = document.querySelectorAll('.submenu-item');
    var panels   = document.querySelectorAll('.emp-panel');

    /* -- 1. EMPLOYEES SUB-MENU TOGGLE -------------------- */
    // Open the sub-menu immediately on page load (Employees is active)
    openSubmenu();

    if (toggle) {
      toggle.addEventListener('click', function () {
        var isOpen = submenu.classList.contains('is-open');
        isOpen ? closeSubmenu() : openSubmenu();
      });
    }

    function openSubmenu() {
      if (!submenu) return;
      submenu.classList.add('is-open');
      if (caret) caret.classList.add('is-open');
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
    }

    function closeSubmenu() {
      if (!submenu) return;
      submenu.classList.remove('is-open');
      if (caret) caret.classList.remove('is-open');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    /* -- 2. TEACHING / NON-TEACHING PANEL SWAP ----------- */
    subBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetPanel = this.dataset.panel;

        // Update sub-button active states
        subBtns.forEach(function (b) {
          b.classList.remove('is-active');
          b.setAttribute('aria-pressed', 'false');
        });
        this.classList.add('is-active');
        this.setAttribute('aria-pressed', 'true');

        // Show matching panel, hide others
        panels.forEach(function (panel) {
          if (panel.id === 'panel-' + targetPanel) {
            panel.classList.add('is-active');
          } else {
            panel.classList.remove('is-active');
          }
        });

        // Re-apply search filter whenever the panel changes
        filterRows();
      });
    });

    /* -- 3. SEARCH BAR ----------------------------------- */
    // Filters rows in whichever panel is currently visible.
    // Matches against every cell's text content, case-insensitive.

    var searchInput = document.getElementById('empSearch');

    function filterRows() {
      var term = searchInput ? searchInput.value.trim().toLowerCase() : '';

      // Find the currently active panel
      var activePanel = document.querySelector('.emp-panel.is-active');
      if (!activePanel) return;

      var rows = activePanel.querySelectorAll('tbody tr');

      rows.forEach(function (row) {
        // Skip the "no records" placeholder row
        if (row.cells.length === 1) {
          row.hidden = false;
          return;
        }

        var text = row.textContent.toLowerCase();
        row.hidden = term.length > 0 && !text.includes(term);
      });
    }

    if (searchInput) {
      searchInput.addEventListener('input', filterRows);
    }

  })();

  const dialog = document.getElementById('myDialog');
const openBtn = document.getElementById('openBtn');
const closeBtn = document.getElementById('closeBtn');

// Open the dialog when trigger is clicked
openBtn.addEventListener('click', () => {
  dialog.showModal(); 
});

// Close the dialog when close button is clicked
closeBtn.addEventListener('click', () => {
  dialog.close();
});


//   const createAccountDialog = document.getElementById('createAccountDialog');
// const openCreateAccountBtn = document.getElementById('openCreateAccount');
// const closeCreateAccountBtn = document.getElementById('closeCreateAccount');

// // Open the dialog when trigger is clicked
// openCreateAccountBtn.addEventListener('click', () => {
//   createAccountDialog.showModal(); 
// });

// // Close the dialog when close button is clicked
// closeCreateAccountBtn.addEventListener('click', () => {
//   createAccountDialog.close();
// });

const createAccountDialog = document.getElementById('createAccountDialog');
const closeCreateAccountBtn = document.getElementById('closeCreateAccount');

// 1. Listen for clicks on ALL "Create Account" buttons across the table
document.querySelectorAll('.openCreateAccountBtn').forEach(button => {
    button.addEventListener('click', function() {
        // 2. Extract values from the clicked button's data attributes
        const empId = this.getAttribute('data-id');
        const empNumber = this.getAttribute('data-number');
        const empName = this.getAttribute('data-name');
        const empEmail = this.getAttribute('data-email');

        // 3. Inject values straight into your dialog form fields
        document.getElementById('modal_employee_id').value = empId;
        document.getElementById('employee_number').value = empNumber;
        document.getElementById('employee_name').value = empName;
        document.getElementById('deped_email').value = empEmail;

        // 4. Reset entry inputs from previous attempts
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
        document.getElementById('role_id').value = '';

        // 5. Filter role options based on the employee's personnel type.
        //    Teaching   → Admin (1) or Teaching Personnel (2)
        //    Non-Teaching → Admin (1) or Non-Teaching Personnel (3)
        var empType = this.getAttribute('data-type'); // "Teaching" or "Non-Teaching"
        var roleSelect = document.getElementById('role_id');
        var roleOptions = roleSelect.querySelectorAll('option');

        roleOptions.forEach(function (opt) {
            if (opt.value === '') {
                // Always show the placeholder option
                opt.hidden = false;
            } else if (opt.value === '1') {
                // Admin is always allowed for any employee type
                opt.hidden = false;
            } else if (opt.value === '2') {
                // Teaching role — only for Teaching employees
                opt.hidden = (empType !== 'Teaching');
            } else if (opt.value === '3') {
                // Non-Teaching role — only for Non-Teaching employees
                opt.hidden = (empType !== 'Non-Teaching');
            }
        });

        // 5. Open the native HTML dialog box properly
        createAccountDialog.showModal();
    });
});

// 6. Close dialog handler
closeCreateAccountBtn.addEventListener('click', () => {
    createAccountDialog.close();
});


//FILE UPLOAD
const masterFileForm = document.getElementById('masterFileForm');
const masterFileStatus = document.getElementById('masterFileStatus');
const masterFilePreview = document.getElementById('masterFilePreview');
const uploadMasterButton = document.getElementById('btnUploadMaster');


masterFileForm.addEventListener('submit', function (event) {

    event.preventDefault();

    const formData = new FormData(masterFileForm);

    masterFileStatus.textContent = 'Reading Excel file...';
    masterFilePreview.innerHTML = '';

    uploadMasterButton.disabled = true;


    fetch('uploadMasterFile.php', {
        method: 'POST',
        body: formData
    })

    .then(function (response) {

        return response.json();

    })

    .then(function (data) {

        if (!data.success) {

            masterFileStatus.textContent =
                data.message || 'The Excel file could not be processed.';

            return;
        }


        masterFileStatus.textContent =
            'Excel file successfully read: ' + data.file_name;


        renderMasterFilePreview(data);

    })

    .catch(function (error) {

        console.error(error);

        masterFileStatus.textContent =
            'An error occurred while processing the Excel file.';

    })

    .finally(function () {

        uploadMasterButton.disabled = false;

    });

});

function renderMasterFilePreview(data) {

    if (!data.rows || data.rows.length === 0) {

        masterFilePreview.innerHTML = `
            <p style="text-align:center;">
                No employee records were found in the Excel file.
            </p>
        `;

        return;
    }


    let html = `

        <div class="import-summary">

            <strong>${data.total_records}</strong>
            records found

            &nbsp; | &nbsp;

            <strong>${data.valid_count}</strong>
            valid

            &nbsp; | &nbsp;

            <strong>${data.invalid_count}</strong>
            invalid

        </div>


        <div class="import-table-wrap">

            <table class="emp-table import-preview-table">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Employee Number</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Plantilla Item Number</th>
                        <th>Position</th>
                        <th>Salary Grade</th>
                        <th>Step Increment</th>
                        <th>Original Appointment</th>
                        <th>Last Promotion</th>
                        <th>DepEd Email</th>
                        <th>Personnel Type</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>
    `;


    data.rows.forEach(function (row, index) {

        const isValid = row.status === 'valid';

        const statusText = isValid
            ? '✓ Valid'
            : '✗ Invalid';


        const errorText = row.errors && row.errors.length
            ? row.errors.map(escapeHtml).join('<br>')
            : '';


        html += `

            <tr
                class="${isValid ? 'import-valid' : 'import-invalid'}"
                data-excel-row="${row.excel_row}"
            >

                <td>
                    ${index + 1}
                </td>


                <!-- Employee Number -->
                <td>
                    <input
                        type="text"
                        class="import-input"
                        data-field="employee_number"
                        value="${escapeHtml(row.employee_number)}"
                    >
                </td>


                <!-- Last Name -->
                <td>
                    <input
                        type="text"
                        class="import-input"
                        data-field="last_name"
                        value="${escapeHtml(row.last_name)}"
                    >
                </td>


                <!-- First Name -->
                <td>
                    <input
                        type="text"
                        class="import-input"
                        data-field="first_name"
                        value="${escapeHtml(row.first_name)}"
                    >
                </td>


                <!-- Middle Name -->
                <td>
                    <input
                        type="text"
                        class="import-input"
                        data-field="middle_name"
                        value="${escapeHtml(row.middle_name)}"
                    >
                </td>


                <!-- Plantilla Item Number -->
                <td>
                    <input
                        type="text"
                        class="import-input"
                        data-field="plantilla_item_number"
                        value="${escapeHtml(row.plantilla_item_number)}"
                    >
                </td>


                <!-- Position -->
                <td>
                    <input
                        type="text"
                        class="import-input"
                        data-field="position"
                        value="${escapeHtml(row.position)}"
                    >
                </td>


                <!-- Salary Grade -->
                <td>
                    <input
                        type="number"
                         min="0"
                        class="import-input"
                        data-field="salary_grade"
                        value="${escapeHtml(row.salary_grade)}"
                    >
                </td>


                <!-- Step Increment -->
                <td>
                    <input
                        type="number"
                         min="0" 
                        class="import-input"
                        data-field="step_increment"
                        value="${escapeHtml(row.step_increment)}"
                    >
                </td>


                <!-- Original Appointment -->
                <td>
                    <input
                        type="date"
                        class="import-input"
                        data-field="date_original_appointment"
                        value="${escapeHtml(row.date_original_appointment || '')}"
                    >
                </td>


                <!-- Last Promotion -->
                <td>
                    <input
                        type="date"
                        class="import-input"
                        data-field="date_last_promotion"
                        value="${escapeHtml(row.date_last_promotion || '')}"
                    >
                </td>


                <!-- DepEd Email -->
                <td>
                    <input
                        type="email"
                        class="import-input"
                        data-field="deped_email"
                        value="${escapeHtml(row.deped_email)}"
                    >
                </td>


                <!-- Personnel Type -->
                <td>

                    <select
                        class="import-input"
                        data-field="personnel_type"
                    >

                        <option
                            value="Teaching"
                            ${row.personnel_type === 'Teaching' ? 'selected' : ''}
                        >
                            Teaching
                        </option>

                        <option
                            value="Non-Teaching"
                            ${row.personnel_type === 'Non-Teaching' ? 'selected' : ''}
                        >
                            Non-Teaching
                        </option>

                    </select>

                </td>


                <!-- Status -->
                <td>

                    <strong class="import-status">
                        ${statusText}
                    </strong>

                    ${
                        errorText
                            ? `<div class="import-errors">${errorText}</div>`
                            : ''
                    }

                </td>

            </tr>
        `;
    });


    html += `

                </tbody>

            </table>

        </div>


        <div style="
            display:flex;
            justify-content:flex-end;
            margin-top:16px;
            gap:10px;
        ">

            <button
                type="button"
                class="btn-filter"
                id="btnSaveMasterFile"
            >
                Save & Import
            </button>

        </div>
    `;


    masterFilePreview.innerHTML = html;
}

function collectMasterFileData() {

    const rows = [];

    const tableRows = masterFilePreview.querySelectorAll(
        'tbody tr[data-excel-row]'
    );


    tableRows.forEach(function (tableRow) {

        const row = {
            excel_row: tableRow.dataset.excelRow
        };


        const inputs = tableRow.querySelectorAll(
            '[data-field]'
        );


        inputs.forEach(function (input) {

            const field = input.dataset.field;

            row[field] = input.value.trim();

        });


        rows.push(row);

    });


    return rows;
}

masterFilePreview.addEventListener('click', function (event) {

    if (event.target.id !== 'btnSaveMasterFile') {
        return;
    }

    const editedRows = collectMasterFileData();

    console.log('Sending employee data:');
    console.log(editedRows);

    fetch('saveMasterFile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            rows: editedRows
        })
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (data) {

        console.log('Response from PHP:');
        console.log(data);

        if (!data.success) {
            alert(data.message);
            return;
        }

        alert(
            'PHP received ' +
            data.received_count +
            ' employee record(s).'
        );

    })
    .catch(function (error) {

        console.error(error);

        alert(
            'An error occurred while sending the employee data.'
        );

    });

});


function escapeHtml(value) {

    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ── ADD EMPLOYEE MODAL ──────────────────────────────────────────────── */
var addEmpDialog   = document.getElementById('addEmployeeDialog');
var openAddEmpBtn  = document.querySelector('.btn-add-employee');
var closeAddEmpBtn = document.getElementById('closeAddEmployee');

// Note: openAddEmpBtn matches the first .btn-add-employee (the toolbar "+ Add Employee" button).
// The "Master File Upload" button uses id="openBtn" and is wired separately above.
if (openAddEmpBtn && addEmpDialog) {
    openAddEmpBtn.addEventListener('click', function() {
        document.getElementById('addEmployeeForm').reset();
        addEmpDialog.showModal();
    });
}
if (closeAddEmpBtn) {
    closeAddEmpBtn.addEventListener('click', function() {
        addEmpDialog.close();
    });
}

/* ── SUCCESS DIALOG CLOSE ────────────────────────────────────────────── */
var closeSuccessBtn = document.getElementById('closeSuccessDialog');
if (closeSuccessBtn) {
    closeSuccessBtn.addEventListener('click', function() {
        document.getElementById('successAddDialog').close();
        var url = new URL(window.location.href);
        url.searchParams.delete('added');
        window.history.replaceState({}, '', url.toString());
    });
}

  </script>

<?php if ($flashType === 'success'): ?>
<script>
  (function() {
    var d = document.getElementById('successAddDialog');
    if (d) d.showModal();
  })();
</script>
<?php endif; ?>

</body>
</html>
