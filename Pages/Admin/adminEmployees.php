<?php

require_once '../../session.php';

start_app_session();
require_admin();

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
    SELECT employees.employee_number, 
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
      max-height: 120px; /* enough for 2 items */
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
                  <th>Employee Number</th>
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
                  <th>Employee Number</th>
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
                            <td colspan="10" style="text-align: center;">No account records found.</td>
                        </tr>

                <?php else: ?>
                     <?php foreach ($Accounts as $request): ?>  
              <tr>
             

              <td><?= htmlspecialchars($request['employee_number']) ?></td><td><?= htmlspecialchars($request['first_name']) . ' ' . htmlspecialchars($request['middle_name']) . ' ' . htmlspecialchars($request['last_name']) ?></td><td><?= htmlspecialchars($request['personnel_type']) ?></td><td><?= htmlspecialchars($request['deped_email']) ?></td>
              <td><?= htmlspecialchars($request['is_active'] === 1 ? 'Active' : 'No Account') ?></td>
              <td><?= $request['is_active'] === 1 ? "<button>View</button>" : " <button id='openCreateAccount' class='btn-add-employee' aria-label='Create Account'>Create Account</button>" ?></td>

            </tr>

               <?php endforeach; ?>

<?php endif; ?>
              
                </tbody>
              </table>
      <!-- The Dialog / Big Card -->
        <dialog id="createAccountDialog">
           <h2>Register Account</h2>
            <p>Register a new account for the selected employee.</p>

           <form action="createAccount.php" method="post" id="accountForm" aria-label="Create Account form">
    <!-- File Upload button -->
           <div>
                <label for="employee_number" class="block text-sm font-medium text-gray-700 mb-1">Employee Number</label>
                <input type="text" id="employee_number" name="employee_number" value="<?= htmlspecialchars($request['employee_number']) ?>" readonly>
            </div>

            <!-- Employee Name -->
            <div>
                <label for="employee_name" class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                <input type="text" id="employee_name" name="employee_name" value="<?= htmlspecialchars($request['first_name']) . ' ' . htmlspecialchars($request['middle_name']) . ' ' . htmlspecialchars($request['last_name']) ?>" readonly>
            </div>

            <!-- Personnel Type -->
            <div>
                <label for="personnel_type" class="block text-sm font-medium text-gray-700 mb-1">Personnel Type</label>
                <input type="text" id="personnel_type" name="personnel_type" value="<?= htmlspecialchars($request['personnel_type']) ?>" readonly>
            </div>
                      
            <!-- DepEd Email -->
            <div>
                <label for="deped_email" class="block text-sm font-medium text-gray-700 mb-1">DepEd Email</label>
                <input type="email" id="deped_email" name="deped_email" value="juan.test@deped.gov.ph" readonly>
                      </div>

            <!-- Username -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" placeholder="juandelacruz">
            </div>

            <!-- Temporary Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Temporary Password</label>
                <input type="password" id="password" name="password" placeholder="********">
            </div>

            <!-- Buttons Layout -->
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
      });
    });

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


  const createAccountDialog = document.getElementById('createAccountDialog');
const openCreateAccountBtn = document.getElementById('openCreateAccount');
const closeCreateAccountBtn = document.getElementById('closeCreateAccount');

// Open the dialog when trigger is clicked
openCreateAccountBtn.addEventListener('click', () => {
  createAccountDialog.showModal(); 
});

// Close the dialog when close button is clicked
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

  </script>

</body>
</html>
