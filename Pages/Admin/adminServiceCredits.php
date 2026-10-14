<?php
require_once '../../session.php';

start_app_session();
require_admin();

$pdo = db();

// 1. Change ->query() to ->prepare()
$localCreditsStatement = $pdo->prepare("
    SELECT COALESCE(SUM(sct.credit_amount), 0) AS total_local_credits 
    FROM service_credit_transactions sct 
    INNER JOIN service_credit_types sctt ON sct.credit_type_id = sctt.credit_type_id 
    WHERE sct.employee_id = ? 
      AND sctt.credit_type_name = 'LOCAL' 
      AND sct.transaction_type = 'EARNED'
");

// 2. Use the correct variable name ($localCreditsStatement) to execute
$localCreditsStatement->execute([ 
  $_SESSION['employee_id'] 
]);

// 3. Fetch the result
$result = $localCreditsStatement->fetch(PDO::FETCH_ASSOC);
$totalLocalCredits = $result['total_local_credits'];




$nationalCreditsStatement = $pdo->prepare("
  SELECT COALESCE(SUM(sct.credit_amount), 0) AS total_national_credits
FROM service_credit_transactions sct
INNER JOIN service_credit_types sctt
    ON sct.credit_type_id = sctt.credit_type_id
WHERE sct.employee_id = ?
  AND sctt.credit_type_name = 'NATIONAL'
  AND sct.transaction_type = 'EARNED'
");

$nationalCreditsStatement->execute([
  $_SESSION['employee_id']
]);

$result = $nationalCreditsStatement->fetch(PDO::FETCH_ASSOC);
$totalNationalCredits = $result['total_national_credits'];

$uploadServiceStatement = $pdo->query("
    SELECT COUNT(*) AS total_upload_records
    FROM service_credit_transactions
");

$uploadService = $uploadServiceStatement->fetch();


// Load Service Credit Upload History
$historyStatement = $pdo->query("
    SELECT
        b.upload_batch_id,
        b.file_name,
        b.uploaded_at,
        b.status,
        b.posted_at,

        CONCAT(
            e.first_name,
            ' ',
            e.last_name
        ) AS uploaded_by_name,

        COUNT(t.transaction_id) AS record_count

    FROM credit_upload_batches b

    INNER JOIN accounts a
        ON a.account_id = b.uploaded_by

    INNER JOIN employees e
        ON e.employee_id = a.employee_id

    LEFT JOIN service_credit_transactions t
        ON t.upload_batch_id = b.upload_batch_id

    GROUP BY
        b.upload_batch_id,
        b.file_name,
        b.uploaded_at,
        b.status,
        b.posted_at,
        e.first_name,
        e.last_name

    ORDER BY
        b.uploaded_at DESC
");

$uploadHistory = $historyStatement->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSHS-SAC | Service Credits</title>
  <link rel="stylesheet" href="../../styles/admin.css" />

  <!-- ============================================================
       PAGE-SCOPED STYLES — adminServiceCredits.php
  ============================================================ -->
  <style>

    /* ── PAGE CONTENT OVERRIDE ────────────────────────────── */
    .page-content.sc-page {
      display: flex;
      flex-direction: column;
      gap: 20px;
      padding: 28px 32px;
      background-color: var(--clr-cream-bg);
    }

    /* ── STAT CARDS ROW ───────────────────────────────────── */
    .sc-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }

    .sc-stat-card {
      background-color: #EDE7F6;
      border-radius: 14px;
      padding: 22px 28px 26px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .sc-stat-label {
      font-size: 0.875rem;
      font-weight: 600;
      color: #333;
    }

    .sc-stat-number {
      font-size: 2.25rem;
      font-weight: 700;
      color: #111;
      text-align: center;
    }

    /* ── SHARED CARD SHELL ────────────────────────────────── */
    .sc-card {
      background-color: #EDE7F6;
      border-radius: 16px;
      padding: 24px 28px 28px;
    }

    .sc-card-title {
      font-size: 1rem;
      font-weight: 700;
      color: #111;
      margin-bottom: 20px;
    }

    /* ── IMPORT / UPLOAD CARD ─────────────────────────────── */
    .sc-upload-inner {
      background-color: #EDE7F6;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      padding: 36px 20px 40px;
    }

    .sc-upload-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #111;
    }

    /* Hidden native file input */
    #excelFileInput {
      display: none;
    }

    /* "Choose Excel File" styled label acts as button */
    .btn-choose-file {
      display: inline-block;
      padding: 10px 28px;
      background-color: #fff;
      color: #111;
      font-size: 0.9rem;
      font-weight: 600;
      border: 1.5px solid #ccc;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-choose-file:hover {
      background-color: #f0ecff;
      box-shadow: 0 2px 8px rgba(107,15,186,0.15);
    }

    /* Selected file name display */
    .sc-file-name {
      font-size: 0.8rem;
      color: #666;
      min-height: 1.2em;
    }

    /* ── DATA PREVIEW CARD ────────────────────────────────── */

    /* Error banner */
    .sc-error-banner {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 18px;
      background-color: #FFF8E7;
      border: 2px solid #F5C518;
      border-radius: 10px;
      margin-bottom: 18px;
    }

    .sc-error-banner svg {
      width: 20px;
      height: 20px;
      flex-shrink: 0;
      stroke: #E09B00;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .sc-error-text {
      font-size: 0.875rem;
      font-weight: 600;
      color: #7A5C00;
    }

        /* ── VALIDATION BANNER STATES ─────────────────────────── */

    /* No errors */
    .sc-success-banner {
      display: flex;
      align-items: center;
      gap: 10px;

      padding: 12px 18px;

      background-color: #ECF9F0;
      border: 2px solid #3BA55C;

      border-radius: 10px;

      margin-bottom: 18px;
    }

    .sc-success-banner svg {
      width: 20px;
      height: 20px;

      flex-shrink: 0;

      stroke: #218838;
      fill: none;

      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .sc-success-text {
      font-size: 0.875rem;
      font-weight: 600;

      color: #218838;
    }


    /* Errors found */
    .sc-error-banner {
      display: flex;
      align-items: center;
      gap: 10px;

      padding: 12px 18px;

      background-color: #FFF8E7;

      border: 2px solid #F5C518;

      border-radius: 10px;

      margin-bottom: 18px;
    }

    /* Preview table wrapper */
    .sc-table-wrap {
      background-color: #fff;
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid #DDD6C8;
    }

    .sc-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.8125rem;
    }

    .sc-table thead tr {
      background-color: #fff;
      border-bottom: 1.5px solid #E0D8C8;
    }

    .sc-table thead th {
      padding: 11px 16px;
      text-align: left;
      font-weight: 600;
      color: #333;
      white-space: nowrap;
    }

    /* Body rows — alternating */
    .sc-table tbody tr:nth-child(odd) {
      background-color: #F3EEFF;
    }

    .sc-table tbody tr:nth-child(even) {
      background-color: #fff;
    }

    .sc-table tbody tr:hover {
      background-color: #E8E0F5;
    }

    .sc-table tbody td {
      padding: 10px 16px;
      color: #333;
      border-bottom: 1px solid #EDE8DC;
    }

    .sc-table tbody tr:last-child td {
      border-bottom: none;
    }
    /* ── DYNAMIC SERVICE CREDIT PREVIEW ───────────────────── */

      .sc-preview-input {
        width: 100%;
        min-width: 110px;
        box-sizing: border-box;

        padding: 7px 8px;

        border: 1px solid #D0C8B8;
        border-radius: 6px;

        background-color: #fff;

        font-family: inherit;
        font-size: 0.8rem;
        color: #333;

        outline: none;
      }

      .sc-preview-input:focus {
        border-color: #6B0FBA;
        box-shadow:
          0 0 0 2px rgba(107, 15, 186, 0.12);
      }

      .sc-preview-input.activity {
        min-width: 180px;
      }

      .sc-preview-input.name {
        min-width: 200px;
      }

      .sc-preview-input.number {
        min-width: 150px;
      }

      .sc-preview-input.credit {
        min-width: 90px;
      }

      .sc-preview-input.date {
        min-width: 135px;
      }

      .sc-preview-select {
        width: 100%;
        min-width: 110px;

        padding: 7px 8px;

        border: 1px solid #D0C8B8;
        border-radius: 6px;

        background-color: #fff;

        font-family: inherit;
        font-size: 0.8rem;

        outline: none;
      }

      .sc-row-valid {
        background-color: #F3EEFF !important;
      }

      .sc-row-invalid {
        background-color: #FFF0F0 !important;
      }

      .sc-status-valid {
        color: #218838;
        font-weight: 700;
        white-space: nowrap;
      }

      .sc-status-invalid {
        color: #CC0000;
        font-weight: 700;
        white-space: nowrap;
      }

      .sc-row-errors {
        margin-top: 5px;
        color: #CC0000;
        font-size: 0.72rem;
        line-height: 1.4;
      }

      .sc-remove-row {
        color: #CC0000;
        font-size: 1rem;
        font-weight: 700;

        cursor: pointer;
        user-select: none;
      }

      .sc-remove-row:hover {
        color: #900000;
      }

      .sc-summary {
        margin-bottom: 15px;

        padding: 10px 14px;

        background-color: #F3EEFF;

        border-radius: 8px;

        font-size: 0.85rem;
        color: #333;
      }

    /* Row with error — highlighted balance cell */
    .sc-table tbody tr.row-error td.cell-highlight {
      color: #CC0000;
      font-weight: 600;
    }

    /* Edit cell — red × icon */
    .edit-delete {
      color: #CC0000;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      user-select: none;
    }

    .edit-none {
      color: #888;
      font-size: 1rem;
    }

    /* ── UPLOAD HISTORY CARD ──────────────────────────────── */
    /* Card is shown as a partial peek at the bottom, same shell */

    /* ── RESPONSIVE ─────────────────────────────────────────── */
    @media (max-width: 900px) {
      .sc-stats {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 600px) {
      .sc-stats {
        grid-template-columns: 1fr;
      }

      .page-content.sc-page {
        padding: 20px 16px;
      }
    }

        /* ============================================================
      SERVICE CREDIT IMPORT VALIDATION UI
    ============================================================ */

    /* Error state for an individual input */
    .sc-import-input.input-error {
      border: 2px solid #D32F2F !important;
      background-color: #FFF5F5;
      outline: none;
    }

    /* Valid/fixed input */
    .sc-import-input.input-valid {
      border: 2px solid #2E9B57 !important;
      background-color: #F5FFF8;
    }

    /* Small error message directly under the problematic field */
    .sc-field-error {
      margin-top: 6px;
      color: #D32F2F;
      font-size: 0.72rem;
      font-weight: 600;
      line-height: 1.35;
    }

    /* Validation row */
    .sc-table tbody tr.sc-row-error {
      background-color: #FFF1F1 !important;
    }

    .sc-table tbody tr.sc-row-valid {
      background-color: #F7FFF9 !important;
    }

    /* Status column */
    .sc-status {
      min-width: 120px;
      vertical-align: middle;
    }

    .sc-status-valid {
      color: #168542;
      font-weight: 700;
      white-space: nowrap;
    }

    .sc-status-invalid {
      color: #D32F2F;
      font-weight: 700;
      white-space: nowrap;
    }

    /* Error summary inside status column */
    .sc-error-summary {
      margin-top: 6px;
      color: #D32F2F;
      font-size: 0.72rem;
      line-height: 1.4;
    }

    /* Action column */
    .sc-action {
      text-align: center;
      vertical-align: middle;
    }

    /* Delete/remove button */
    .sc-remove-row {
      border: none;
      background: transparent;
      color: #D32F2F;
      font-size: 1.25rem;
      font-weight: 700;
      cursor: pointer;
      padding: 4px 8px;
    }

    .sc-remove-row:hover {
      color: #A40000;
    }

    /* Save & Post area */
    .sc-post-actions {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 12px;
      margin-top: 18px;
    }

    /* Save & Post button */
    #btnSaveServiceCredits {
      padding: 10px 22px;
      border: none;
      border-radius: 8px;
      background-color: #6B0FBA;
      color: #fff;
      font-size: 0.875rem;
      font-weight: 700;
      cursor: pointer;
      transition: 0.2s ease;
    }

    #btnSaveServiceCredits:hover:not(:disabled) {
      background-color: #560A96;
    }

    /* Disabled while errors exist */
    #btnSaveServiceCredits:disabled {
      background-color: #BDBDBD;
      color: #F5F5F5;
      cursor: not-allowed;
    }

    /* ============================================================
      SUCCESS / ERROR BANNER
    ============================================================ */

    .sc-validation-banner {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 18px;
      border-radius: 10px;
      margin-bottom: 18px;
      font-size: 0.875rem;
      font-weight: 600;
    }

    /* Error */
    .sc-validation-banner.error {
      background-color: #FFF8E7;
      border: 2px solid #F5C518;
      color: #7A5C00;
    }

    /* Good */
    .sc-validation-banner.success {
      background-color: #F0FFF5;
      border: 2px solid #2E9B57;
      color: #176B37;
    }

    /* Banner icon */
    .sc-validation-icon {
      font-size: 1.15rem;
      font-weight: 700;
    }

          /* ============================================================
        SERVICE CREDIT LIVE VALIDATION
      ============================================================ */

      /* Green success banner */

      .sc-success-banner {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 12px 18px;

        background-color: #ECFDF3;

        border: 2px solid #22A06B;

        border-radius: 10px;

        margin-bottom: 18px;
      }

      .sc-success-banner svg {
        width: 20px;
        height: 20px;

        flex-shrink: 0;

        stroke: #16804F;

        fill: none;

        stroke-width: 2;

        stroke-linecap: round;

        stroke-linejoin: round;
      }

      .sc-success-text {
        font-size: 0.875rem;

        font-weight: 600;

        color: #176B45;
      }


      /* Error banner */

      .sc-error-banner {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 12px 18px;

        background-color: #FFF8E7;

        border: 2px solid #F5C518;

        border-radius: 10px;

        margin-bottom: 18px;
      }

      .sc-error-banner svg {
        width: 20px;
        height: 20px;

        flex-shrink: 0;

        stroke: #E09B00;

        fill: none;

        stroke-width: 2;

        stroke-linecap: round;

        stroke-linejoin: round;
      }

      .sc-error-text {
        font-size: 0.875rem;

        font-weight: 600;

        color: #7A5C00;
      }


      /* ============================================================
        ROW VALIDATION
      ============================================================ */

      .sc-row-valid {
        background-color: #F3FFF8 !important;
      }

      .sc-row-invalid {
        background-color: #FFF1F1 !important;
      }


      /* ============================================================
        INPUT ERROR
      ============================================================ */

      .sc-input-error {
        border: 2px solid #D93025 !important;

        background-color: #FFF8F8 !important;

        box-shadow: 0 0 0 2px rgba(217, 48, 37, 0.08);
      }


      /* ============================================================
        STATUS
      ============================================================ */

      .sc-status-cell {
        min-width: 230px;

        vertical-align: middle !important;
      }

      .sc-status-valid {
        color: #16804F;

        font-weight: 700;

        white-space: nowrap;
      }

      .sc-status-invalid {
        color: #C5221F;

        font-weight: 700;

        white-space: nowrap;
      }


      /* ============================================================
        ERROR LIST
      ============================================================ */

      .sc-row-errors {
        margin-top: 7px;

        display: flex;

        flex-direction: column;

        gap: 4px;

        color: #C5221F;

        font-size: 0.75rem;

        line-height: 1.35;

        max-width: 240px;
      }

      .sc-error-item {
        padding-left: 12px;

        position: relative;
      }

      .sc-error-item::before {
        content: "•";

        position: absolute;

        left: 0;

        font-weight: 700;
      }


      /* ============================================================
        SUMMARY
      ============================================================ */

      .sc-summary-valid {
        color: #16804F;
      }

      .sc-summary-invalid {
        color: #C5221F;
      }
      .sc-history-status-posted {
  color: #16803A;
  font-weight: 700;
}

    .sc-history-status-other {
      color: #A66A00;
      font-weight: 700;
    }

    .sc-history-wrap {
      overflow-x: auto;
    }

    .sc-history-wrap .sc-table {
      min-width: 800px;
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

          <li>
            <a href="adminDashboard.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
              </span>
              My Dashboard
            </a>
          </li>

          <li>
            <a href="adminProfile.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
              </span>
              Profile
            </a>
          </li>

          <li>
            <a href="adminEmployees.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.3 3.1-6 7-6s7 2.7 7 6"/><circle cx="17" cy="8" r="2.5"/><path d="M20 20c0-2.5-1.9-4.5-4.5-5"/></svg>
              </span>
              Employees
            </a>
          </li>

          <!-- Service Credits — active -->
          <li>
            <a href="adminServiceCredits.php" class="nav-item active" aria-current="page">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="5"/><path d="M7.5 14.5L5 21l7-2.5L19 21l-2.5-6.5"/></svg>
              </span>
              Service Credits
            </a>
          </li>

          <li>
            <a href="adminTransactions.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="12" y2="15"/></svg>
              </span>
              Transactions
            </a>
          </li>

          <li>
            <a href="adminSettings.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
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
          <span></span><span></span><span></span><span></span>
        </div>
        <h1 class="topbar-title">
          <!-- Badge / ribbon icon -->
          <svg style="width:26px;height:26px;stroke:#F5C518;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round;vertical-align:middle;margin-right:8px;" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="9" r="5"/>
            <path d="M7.5 14.5L5 21l7-2.5L19 21l-2.5-6.5"/>
          </svg>
          Service Credits
        </h1>
        <button class="hamburger" id="hamburger" type="button"
          aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation menu">
          <span></span><span></span><span></span>
        </button>
      </header>

      <!-- Page content -->
      <main class="page-content sc-page" id="mainContent">

        <!-- ── STAT CARDS ─────────────────────────────────── -->
          <div class="sc-stats" role="region" aria-label="Service credit statistics">

          <div class="sc-stat-card">
            <p class="sc-stat-label">Local Service Credits</p>
            <p class="sc-stat-number"><?= $totalLocalCredits; ?></p>
          </div>

          <div class="sc-stat-card">
            <p class="sc-stat-label">National Service Credits</p>
            <p class="sc-stat-number"><?= $totalNationalCredits; ?></p>
          </div>

          <div class="sc-stat-card">
            <p class="sc-stat-label">Service Credits Uploads</p>
            <p class="sc-stat-number"><?= $uploadService['total_upload_records']; ?></p>
          </div>

        </div>
        <!-- END STAT CARDS -->

        <!-- ── IMPORT SERVICE CREDITS CARD ───────────────── -->
        <section class="sc-card" aria-labelledby="importTitle">
          <h2 class="sc-card-title" id="importTitle">Import Service Credits</h2>

          <div class="sc-upload-inner">
            <p class="sc-upload-title">Upload Excel File</p>

            <form action="uploadServiceCredits.php" method="POST" enctype="multipart/form-data" aria-label="Upload service credits form">
            <!-- Hidden real file input -->
            <input type="file" id="excelFileInput" name="service_credits_file" accept=".xlsx,.xls,.csv" aria-label="Choose Excel file to upload"/>

            <!-- Styled label triggers the hidden input -->
            <label for="excelFileInput" class="btn-choose-file" role="button" tabindex="0">
              Choose Excel File
            </label>

            <button type="submit" class="btn-upload" aria-label="Upload selected Excel file">Upload & Preview</button>
            </form>

            <!-- Shows selected filename after picking -->
            <p class="sc-file-name" id="scFileName" aria-live="polite"></p>
          </div>
        </section>
        <!-- END IMPORT CARD -->

        <!-- ── DATA PREVIEW CARD ──────────────────────────── -->
        <section class="sc-card" aria-labelledby="previewTitle">
          <h2 class="sc-card-title" id="previewTitle">Data Preview</h2>

          <!-- Error banner -->
            <div class="sc-error-banner" id="serviceCreditValidationBanner" role="alert" aria-label="Validation status">

        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
          <line x1="12" y1="9" x2="12" y2="13"/>
          <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>

        <span class="sc-error-text">
          Upload an Excel file to begin validation.
        </span>

      </div>

          <!-- Preview table -->
        <!-- Preview table wrapper -->
          <div class="sc-table-wrap">

            <table class="sc-table" aria-label="Service credit data preview">
              <thead>
                <tr>
                  <th>Employee Number</th>
                  <th>Employee Name</th>
                  <th>Activity</th>
                  <th>Start Date</th>
                  <th>End Date</th>
                  <th>Service Credits</th>
                  <th>Nature</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>

              <tbody id="serviceCreditPreviewBody">

                <tr>
                  <td colspan="9" style="text-align:center; padding:30px;">
                    No service credit file uploaded yet.
                  </td>
                </tr>

              </tbody>

            </table>

          </div>

          <!-- Save and Post button -->
          <div id="serviceCreditActions"
            style="display:none;
              justify-content:flex-end;
              margin-top:18px;
              gap:10px;">

            <button type="button" class="btn-upload" id="btnSaveServiceCredits">
              Save &amp; Post
            </button>

          </div>
        </section>
        <!-- END DATA PREVIEW CARD -->

        <!-- ── UPLOAD HISTORY CARD (partial peek) ────────── -->
       <section class="sc-card" aria-labelledby="historyTitle">

  <h2 class="sc-card-title" id="historyTitle">
    Upload History
  </h2>

  <div class="sc-table-wrap sc-history-wrap">

    <table class="sc-table" aria-label="Service credit upload history">

      <thead>

        <tr>
          <th>Upload Date</th>
          <th>File Name</th>
          <th>Uploaded By</th>
          <th>Records</th>
          <th>Status</th>
          <th>Posted Date</th>
        </tr>

      </thead>

      <tbody>

        <?php if (empty($uploadHistory)): ?>

          <tr>

            <td
              colspan="6"
              style="
                text-align:center;
                padding:30px;
                color:#777;
              "
            >
              No service credit uploads yet.
            </td>

          </tr>

        <?php else: ?>

          <?php foreach ($uploadHistory as $history): ?>

            <tr>

              <td>
                <?php
                  echo htmlspecialchars(
                    date(
                      'M d, Y h:i A',
                      strtotime($history['uploaded_at'])
                    )
                  );
                ?>
              </td>

              <td>
                <?php
                  echo htmlspecialchars(
                    $history['file_name']
                  );
                ?>
              </td>

              <td>
                <?php
                  echo htmlspecialchars(
                    $history['uploaded_by_name']
                  );
                ?>
              </td>

              <td>
                <?php
                  echo (int) $history['record_count'];
                ?>
              </td>

              <td>

                <?php if ($history['status'] === 'POSTED'): ?>

                  <strong class="sc-history-status-posted">
                    ✓ Posted
                  </strong>

                <?php else: ?>

                  <strong class="sc-history-status-other">
                    <?php
                      echo htmlspecialchars(
                        $history['status']
                      );
                    ?>
                  </strong>

                <?php endif; ?>

              </td>

              <td>

                <?php if (!empty($history['posted_at'])): ?>

                  <?php
                    echo htmlspecialchars(
                      date(
                        'M d, Y h:i A',
                        strtotime($history['posted_at'])
                      )
                    );
                  ?>

                <?php else: ?>

                  —

                <?php endif; ?>

              </td>

            </tr>

          <?php endforeach; ?>

        <?php endif; ?>

      </tbody>

    </table>

  </div>

</section>
        <!-- END UPLOAD HISTORY CARD -->

      </main>
    </div>
    <!-- END MAIN WRAPPER -->

  </div>
  <!-- END APP SHELL -->

  <!-- Shared admin JS -->
  <script src="../../scripts/admin.js"></script>

  <!-- ============================================================
       PAGE-SCOPED JS — adminServiceCredits.html
       Handles:
         1. File picker — show selected filename
         2. Label keyboard accessibility (Enter/Space triggers input)
       No fake fetches, no alerts, no timeouts.
  ============================================================ -->
  <script>
  (function () {
    'use strict';

    var fileInput  = document.getElementById('excelFileInput');
    var fileLabel  = document.querySelector('label[for="excelFileInput"]');
    var fileNameEl = document.getElementById('scFileName');

    /* ── 1. SHOW SELECTED FILE NAME ────────────────────── */
    if (fileInput && fileNameEl) {
      fileInput.addEventListener('change', function () {
        var name = this.files && this.files[0] ? this.files[0].name : '';
        fileNameEl.textContent = name ? 'Selected: ' + name : '';
      });
    }

    /* ── 2. KEYBOARD ACCESSIBILITY FOR FILE LABEL ──────── */
    // Allow Enter or Space to trigger the hidden file input
    // when the label itself has keyboard focus.
    if (fileLabel) {
      fileLabel.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          if (fileInput) fileInput.click();
        }
      });
    }

  })();

    /*
  |------------------------------------------------------------------
  | SERVICE CREDIT EXCEL UPLOAD + PREVIEW
  |------------------------------------------------------------------
  */

  var serviceCreditForm =
    document.querySelector(
      'form[action="uploadServiceCredits.php"]'
    );

  var serviceCreditPreviewBody =
    document.getElementById(
      'serviceCreditPreviewBody'
    );

  var serviceCreditActions =
    document.getElementById(
      'serviceCreditActions'
    );

  var saveServiceCreditsButton =
    document.getElementById(
      'btnSaveServiceCredits'
    );


  /*
  |------------------------------------------------------------------
  | Escape HTML
  |------------------------------------------------------------------
  */

  function escapeHtml(value) {

    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');

  }


  /*
  |------------------------------------------------------------------
  | Upload Excel
  |------------------------------------------------------------------
  */

  if (serviceCreditForm) {

    serviceCreditForm.addEventListener(
      'submit',
      function (event) {

        event.preventDefault();


        var formData =
          new FormData(serviceCreditForm);


        serviceCreditPreviewBody.innerHTML = `

          <tr>

            <td
              colspan="9"
              style="text-align:center; padding:30px;"
            >
              Reading Excel file...
            </td>

          </tr>

        `;


        serviceCreditActions.style.display = 'none';


        var uploadButton =
          serviceCreditForm.querySelector(
            'button[type="submit"]'
          );


        if (uploadButton) {
          uploadButton.disabled = true;
          uploadButton.textContent = 'Reading...';
        }


        fetch(
          'uploadServiceCredits.php',
          {
            method: 'POST',
            body: formData
          }
        )

        .then(function (response) {

          return response.text();

        })

        .then(function (text) {

          /*
           * This is useful while developing.
           * If PHP produces a warning/fatal error,
           * we can see the actual response.
           */

          console.log(
            'Service credit upload response:',
            text
          );


          var data;


          try {

            data = JSON.parse(text);

          } catch (error) {

            throw new Error(
              'The server returned an invalid response. Check the browser console.'
            );

          }


          if (!data.success) {

            throw new Error(
              data.message ||
              'The Excel file could not be processed.'
            );

          }
          window.serviceCreditFileName =
          data.file_name;


          renderServiceCreditPreview(data);

        })

        .catch(function (error) {

          console.error(error);


          serviceCreditPreviewBody.innerHTML = `

            <tr>

              <td
                colspan="9"
                style="
                  text-align:center;
                  padding:30px;
                  color:#CC0000;
                "
              >
                ${escapeHtml(error.message)}

              </td>

            </tr>

          `;


          serviceCreditActions.style.display = 'none';

        })

        .finally(function () {

          if (uploadButton) {

            uploadButton.disabled = false;

            uploadButton.textContent =
              'Upload & Preview';

          }

        });

      }
    );

  }


/*
|------------------------------------------------------------------
| Update Validation Banner
|------------------------------------------------------------------
*/

function updateServiceCreditValidationBanner(data) {

    var banner =
        document.querySelector(
            '.sc-error-banner'
        );


    if (!banner) {
        return;
    }


    var invalidCount =
        Number(data.invalid_count || 0);


    var validCount =
        Number(data.valid_count || 0);


    var totalRecords =
        Number(data.total_records || 0);


    /*
     * INVALID
     */

    if (invalidCount > 0) {

        banner.classList.remove(
            'sc-success-banner'
        );

        banner.classList.add(
            'sc-error-banner'
        );


        banner.innerHTML = `

            <svg viewBox="0 0 24 24" aria-hidden="true">

                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>

                <line
                    x1="12"
                    y1="9"
                    x2="12"
                    y2="13"
                />

                <line
                    x1="12"
                    y1="17"
                    x2="12.01"
                    y2="17"
                />

            </svg>

            <span class="sc-error-text">

                ${invalidCount}
                ${invalidCount === 1 ? 'Error' : 'Errors'}
                Found. Please review the highlighted fields.

            </span>

        `;

        return;
    }


    /*
     * SUCCESS
     */

    if (
        totalRecords > 0 &&
        invalidCount === 0 &&
        validCount > 0
    ) {

        banner.classList.remove(
            'sc-error-banner'
        );

        banner.classList.add(
            'sc-success-banner'
        );


        banner.innerHTML = `

            <svg viewBox="0 0 24 24" aria-hidden="true">

                <circle
                    cx="12"
                    cy="12"
                    r="9"
                />

                <path
                    d="M8 12.5l2.5 2.5L16 9"
                />

            </svg>

            <span class="sc-success-text">

                No errors found. Please double-check
                the information before posting.

            </span>

        `;

        return;
    }


    /*
     * EMPTY
     */

    banner.classList.remove(
        'sc-success-banner'
    );

    banner.classList.add(
        'sc-error-banner'
    );


    banner.innerHTML = `

        <svg viewBox="0 0 24 24" aria-hidden="true">

            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>

            <line
                x1="12"
                y1="9"
                x2="12"
                y2="13"
            />

            <line
                x1="12"
                y1="17"
                x2="12.01"
                y2="17"
            />

        </svg>

        <span class="sc-error-text">
            No service credit records are available.
        </span>

    `;

}

function validateServiceCreditRow(rowElement) {

    const errors = [];

    const employeeNumber = rowElement
        .querySelector('[data-field="employee_number"]')
        ?.value
        .trim() || '';

    const employeeName = rowElement
        .querySelector('[data-field="employee_name"]')
        ?.value
        .trim() || '';

    const activity = rowElement
        .querySelector('[data-field="name_of_activity"]')
        ?.value
        .trim() || '';

    const startDate = rowElement
        .querySelector('[data-field="activity_start_date"]')
        ?.value
        .trim() || '';

    const endDate = rowElement
        .querySelector('[data-field="activity_end_date"]')
        ?.value
        .trim() || '';

    const serviceCredits = rowElement
        .querySelector('[data-field="number_of_service_credits"]')
        ?.value
        .trim() || '';

    const nature = rowElement
        .querySelector('[data-field="nature_of_service_credits"]')
        ?.value
        .trim() || '';


    /* --------------------------------------------------------
       Required fields
    -------------------------------------------------------- */

    if (!employeeNumber) {
        errors.push({
            field: 'employee_number',
            message: 'Employee number is required.'
        });
    }

    if (!employeeName) {
        errors.push({
            field: 'employee_name',
            message: 'Employee name is required.'
        });
    }

    if (!activity) {
        errors.push({
            field: 'name_of_activity',
            message: 'Activity name is required.'
        });
    }

    if (!startDate) {
        errors.push({
            field: 'activity_start_date',
            message: 'Start date is required.'
        });
    }

    if (!endDate) {
        errors.push({
            field: 'activity_end_date',
            message: 'End date is required.'
        });
    }

    if (!serviceCredits) {
        errors.push({
            field: 'number_of_service_credits',
            message: 'Service credit is required.'
        });
    }

    if (!nature) {
        errors.push({
            field: 'nature_of_service_credits',
            message: 'Nature of service credit is required.'
        });
    }


    /* --------------------------------------------------------
       Service credit amount
    -------------------------------------------------------- */

    if (serviceCredits !== '') {

        const creditValue = Number(serviceCredits);

        if (
            Number.isNaN(creditValue) ||
            creditValue <= 0
        ) {
            errors.push({
                field: 'number_of_service_credits',
                message: 'Must be greater than 0.'
            });
        }
    }


    /* --------------------------------------------------------
       Date validation
    -------------------------------------------------------- */

    if (startDate && endDate) {

        if (endDate < startDate) {

            errors.push({
                field: 'activity_end_date',
                message: 'End date cannot be earlier than start date.'
            });
        }
    }


    /* --------------------------------------------------------
       Nature validation
    -------------------------------------------------------- */

    if (
        nature &&
        nature !== 'LOCAL' &&
        nature !== 'NATIONAL'
    ) {

        errors.push({
            field: 'nature_of_service_credits',
            message: 'Must be LOCAL or NATIONAL.'
        });
    }


    return errors;
}
  /*
  |------------------------------------------------------------------
  | Render Preview
  |------------------------------------------------------------------
  */

  function renderServiceCreditPreview(data) {

    if (
        !data.rows ||
        data.rows.length === 0
    ) {

        serviceCreditPreviewBody.innerHTML = `

            <tr>

                <td
                    colspan="9"
                    style="text-align:center; padding:30px;"
                >
                    No service credit records were found.
                </td>

            </tr>

        `;

        serviceCreditActions.style.display = 'none';

        updateServiceCreditValidationBanner({
            total_records: 0,
            valid_count: 0,
            invalid_count: 0
        });

        return;
    }


    /*
     * Summary
     */

    var summaryHtml = `

        <div class="sc-summary">

            <strong>
                ${data.total_records}
            </strong>
            record(s)

            &nbsp; | &nbsp;

            <strong class="sc-summary-valid">
                ${data.valid_count}
            </strong>
            valid

            &nbsp; | &nbsp;

            <strong class="sc-summary-invalid">
                ${data.invalid_count}
            </strong>
            invalid

        </div>

    `;


    /*
     * Put summary before table.
     */

    var tableWrap =
        document.querySelector('.sc-table-wrap');


    var existingSummary =
        document.querySelector('.sc-summary');


    if (existingSummary) {
        existingSummary.remove();
    }


    tableWrap.insertAdjacentHTML(
        'beforebegin',
        summaryHtml
    );


    /*
     * Generate table rows.
     */

    var html = '';


    data.rows.forEach(
        function (row, index) {

            var isValid =
                row.status === 'valid';


            var rowClass =
                isValid
                    ? 'sc-row-valid'
                    : 'sc-row-invalid';


            var statusClass =
                isValid
                    ? 'sc-status-valid'
                    : 'sc-status-invalid';


            var statusText =
                isValid
                    ? '✓ Valid'
                    : '✗ Invalid';


            var errorsHtml = '';


            if (
                row.errors &&
                row.errors.length > 0
            ) {

                errorsHtml = `

                    <div class="sc-row-errors">

                        ${row.errors
                            .map(function (error) {
                                return `
                                    <div class="sc-error-item">
                                        ${escapeHtml(error)}
                                    </div>
                                `;
                            })
                            .join('')}

                    </div>

                `;

            }


            html += `

                <tr
                    class="${rowClass}"
                    data-excel-row="${escapeHtml(row.excel_row)}"
                    data-employee-id="${escapeHtml(row.employee_id || '')}"
                >

                    <!-- Employee Number -->

                    <td>

                        <input
                            type="text"
                            class="sc-preview-input number"
                            data-field="employee_number"
                            value="${escapeHtml(row.employee_number)}"
                        >

                    </td>


                    <!-- Employee Name -->

                    <td>

                        <input
                            type="text"
                            class="sc-preview-input name"
                            data-field="employee_name"
                            value="${escapeHtml(row.employee_name)}"
                        >

                    </td>


                    <!-- Activity -->

                    <td>

                        <input
                            type="text"
                            class="sc-preview-input activity"
                            data-field="name_of_activity"
                            value="${escapeHtml(row.name_of_activity)}"
                        >

                    </td>


                    <!-- Start Date -->

                    <td>

                        <input
                            type="date"
                            class="sc-preview-input date"
                            data-field="activity_start_date"
                            value="${escapeHtml(row.activity_start_date || '')}"
                        >

                    </td>


                    <!-- End Date -->

                    <td>

                        <input
                            type="date"
                            class="sc-preview-input date"
                            data-field="activity_end_date"
                            value="${escapeHtml(row.activity_end_date || '')}"
                        >

                    </td>


                    <!-- Service Credits -->

                    <td>

                        <input
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="sc-preview-input credit"
                            data-field="number_of_service_credits"
                            value="${escapeHtml(row.number_of_service_credits)}"
                        >

                    </td>


                    <!-- Nature -->

                    <td>

                        <select
                            class="sc-preview-select"
                            data-field="nature_of_service_credits"
                        >

                            <option
                                value="LOCAL"
                                ${
                                    row.nature_of_service_credits === 'LOCAL'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                Local
                            </option>

                            <option
                                value="NATIONAL"
                                ${
                                    row.nature_of_service_credits === 'NATIONAL'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                National
                            </option>

                        </select>

                    </td>


                    <!-- Status -->

                    <td class="sc-status-cell">

                        <strong class="${statusClass}">
                            ${statusText}
                        </strong>

                        ${errorsHtml}

                    </td>


                    <!-- Remove -->

                    <td>

                        <span
                            class="sc-remove-row"
                            title="Remove this row"
                            aria-label="Remove row"
                        >
                            &#10005;
                        </span>

                    </td>

                </tr>

            `;

        }
    );


    serviceCreditPreviewBody.innerHTML =
        html;


    /*
     * IMPORTANT:
     *
     * Save & Post should appear ONLY
     * when ALL records are valid.
     */

    if (
        data.invalid_count === 0 &&
        data.valid_count > 0
    ) {

        serviceCreditActions.style.display =
            'flex';

    } else {

        serviceCreditActions.style.display =
            'none';

    }


    /*
     * Run frontend validation once
     * after the table has been created.
     */

    validateServiceCreditPreview();

}
serviceCreditPreviewBody.addEventListener(
    'input',
    function (event) {

        if (
            !event.target.matches(
                '[data-field]'
            )
        ) {
            return;
        }

        validateServiceCreditPreview();

    }
);


serviceCreditPreviewBody.addEventListener(
    'change',
    function (event) {

        if (
            !event.target.matches(
                '[data-field]'
            )
        ) {
            return;
        }

        validateServiceCreditPreview();

    }
);
function validateServiceCreditPreview() {

    var tableRows =
        serviceCreditPreviewBody.querySelectorAll(
            'tr[data-excel-row]'
        );


    var totalRecords =
        tableRows.length;


    var validCount = 0;
    var invalidCount = 0;


    tableRows.forEach(function (row) {

        var errors = [];


        /*
         * Get fields
         */

        var employeeNumber =
            row.querySelector(
                '[data-field="employee_number"]'
            );


        var employeeName =
            row.querySelector(
                '[data-field="employee_name"]'
            );


        var activity =
            row.querySelector(
                '[data-field="name_of_activity"]'
            );


        var startDate =
            row.querySelector(
                '[data-field="activity_start_date"]'
            );


        var endDate =
            row.querySelector(
                '[data-field="activity_end_date"]'
            );


        var serviceCredits =
            row.querySelector(
                '[data-field="number_of_service_credits"]'
            );


        var nature =
            row.querySelector(
                '[data-field="nature_of_service_credits"]'
            );


        /*
         * Reset field error styles
         */

        row.querySelectorAll(
            '[data-field]'
        ).forEach(function (field) {

            field.classList.remove(
                'sc-input-error'
            );

        });


        /*
         * Employee Number
         */

        if (
            !employeeNumber ||
            employeeNumber.value.trim() === ''
        ) {

            errors.push(
                'Employee Number is required.'
            );

            if (employeeNumber) {
                employeeNumber.classList.add(
                    'sc-input-error'
                );
            }

        }


        /*
         * Employee Name
         */

        if (
            !employeeName ||
            employeeName.value.trim() === ''
        ) {

            errors.push(
                'Employee Name is required.'
            );

            if (employeeName) {
                employeeName.classList.add(
                    'sc-input-error'
                );
            }

        }


        /*
         * Activity
         */

        if (
            !activity ||
            activity.value.trim() === ''
        ) {

            errors.push(
                'Activity is required.'
            );

            if (activity) {
                activity.classList.add(
                    'sc-input-error'
                );
            }

        }


        /*
         * Start Date
         */

        if (
            !startDate ||
            startDate.value === ''
        ) {

            errors.push(
                'Start Date is required.'
            );

            if (startDate) {
                startDate.classList.add(
                    'sc-input-error'
                );
            }

        }


        /*
         * End Date
         */

        if (
            !endDate ||
            endDate.value === ''
        ) {

            errors.push(
                'End Date is required.'
            );

            if (endDate) {
                endDate.classList.add(
                    'sc-input-error'
                );
            }

        }


        /*
         * Check date order
         */

        if (
            startDate &&
            endDate &&
            startDate.value !== '' &&
            endDate.value !== ''
        ) {

            if (
                endDate.value <
                startDate.value
            ) {

                errors.push(
                    'End Date cannot be earlier than Start Date.'
                );

                endDate.classList.add(
                    'sc-input-error'
                );

            }

        }


        /*
         * Service Credits
         */

        var creditValue =
            serviceCredits
                ? parseFloat(serviceCredits.value)
                : NaN;


        if (
            serviceCredits &&
            (
                serviceCredits.value === '' ||
                isNaN(creditValue) ||
                creditValue <= 0
            )
        ) {

            errors.push(
                'Service Credits must be greater than 0.'
            );

            serviceCredits.classList.add(
                'sc-input-error'
            );

        }


        /*
         * Nature
         */

        if (
            !nature ||
            (
                nature.value !== 'LOCAL' &&
                nature.value !== 'NATIONAL'
            )
        ) {

            errors.push(
                'Nature of service credit must be LOCAL or NATIONAL.'
            );

            if (nature) {
                nature.classList.add(
                    'sc-input-error'
                );
            }

        }


        /*
         * Update row
         */

        var statusElement =
            row.querySelector(
                '.sc-status-cell strong'
            );


        var errorContainer =
            row.querySelector(
                '.sc-row-errors'
            );


        if (errors.length === 0) {

            /*
             * VALID
             */

            row.classList.remove(
                'sc-row-invalid'
            );

            row.classList.add(
                'sc-row-valid'
            );


            if (statusElement) {

                statusElement.className =
                    'sc-status-valid';

                statusElement.textContent =
                    '✓ Valid';

            }


            if (errorContainer) {
                errorContainer.remove();
            }


            validCount++;

        } else {

            /*
             * INVALID
             */

            row.classList.remove(
                'sc-row-valid'
            );

            row.classList.add(
                'sc-row-invalid'
            );


            if (statusElement) {

                statusElement.className =
                    'sc-status-invalid';

                statusElement.textContent =
                    '✗ Invalid';

            }


            if (errorContainer) {

                errorContainer.innerHTML =
                    errors
                        .map(function (error) {

                            return `
                                <div class="sc-error-item">
                                    ${escapeHtml(error)}
                                </div>
                            `;

                        })
                        .join('');

            } else {

                var newErrorContainer =
                    document.createElement('div');


                newErrorContainer.className =
                    'sc-row-errors';


                newErrorContainer.innerHTML =
                    errors
                        .map(function (error) {

                            return `
                                <div class="sc-error-item">
                                    ${escapeHtml(error)}
                                </div>
                            `;

                        })
                        .join('');


                var statusCell =
                    row.querySelector(
                        '.sc-status-cell'
                    );


                if (statusCell) {
                    statusCell.appendChild(
                        newErrorContainer
                    );
                }

            }


            invalidCount++;

        }

    });


    /*
     * Update summary
     */

    var summary =
        document.querySelector(
            '.sc-summary'
        );


    if (summary) {

        summary.innerHTML = `

            <strong>
                ${totalRecords}
            </strong>
            record(s)

            &nbsp; | &nbsp;

            <strong class="sc-summary-valid">
                ${validCount}
            </strong>
            valid

            &nbsp; | &nbsp;

            <strong class="sc-summary-invalid">
                ${invalidCount}
            </strong>
            invalid

        `;

    }


    /*
     * Update top validation banner
     */

    updateServiceCreditValidationBanner({

        total_records: totalRecords,

        valid_count: validCount,

        invalid_count: invalidCount

    });


    /*
     * Save & Post
     *
     * ONLY visible when EVERY
     * row is valid.
     */

    if (
        totalRecords > 0 &&
        invalidCount === 0
    ) {

        serviceCreditActions.style.display =
            'flex';

    } else {

        serviceCreditActions.style.display =
            'none';

    }

}
function collectServiceCreditData() {

    var rows = [];

    var tableRows =
        serviceCreditPreviewBody.querySelectorAll(
            'tr[data-excel-row]'
        );


    tableRows.forEach(function (tableRow) {

        var row = {

            excel_row:
                tableRow.dataset.excelRow,

            employee_id:
                tableRow.dataset.employeeId || '',

            employee_number: '',

            employee_name: '',

            name_of_activity: '',

            activity_start_date: '',

            activity_end_date: '',

            number_of_service_credits: '',

            nature_of_service_credits: ''

        };


        var fields =
            tableRow.querySelectorAll(
                '[data-field]'
            );


        fields.forEach(function (field) {

            var fieldName =
                field.dataset.field;


            row[fieldName] =
                field.value.trim();

        });


        rows.push(row);

    });


    return rows;
}

serviceCreditActions.addEventListener(
    'click',
    function (event) {

        var button =
            event.target.closest(
                '#btnSaveServiceCredits'
            );


        if (!button) {
            return;
        }


        /*
         * Validate one more time
         * before sending anything.
         */

        validateServiceCreditPreview();


        /*
         * Don't continue if anything
         * is invalid.
         */

        var invalidRows =
            serviceCreditPreviewBody.querySelectorAll(
                'tr.sc-row-invalid'
            );


        if (invalidRows.length > 0) {

            alert(
                'Please fix all errors before posting the service credits.'
            );

            return;

        }


        /*
         * Collect edited data
         */

        var rows =
            collectServiceCreditData();


        if (rows.length === 0) {

            alert(
                'There are no service credit records to post.'
            );

            return;

        }


        /*
         * Disable button while saving
         */

        button.disabled = true;

        button.textContent =
            'Posting...';


        /*
         * Send JSON to PHP
         */

        fetch(
            'saveServiceCredits.php',
            {

                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/json'
                },

                body: JSON.stringify({

                    file_name:
                        window.serviceCreditFileName
                        || 'service_credits.xlsx',

                    rows:
                        rows

                })

            }
        )

        .then(function (response) {

            return response.json();

        })

        .then(function (data) {

            if (!data.success) {

                console.error(
                    'Save Service Credits Error:',
                    data
                );


                alert(
                    data.message
                    ||
                    'The service credits could not be posted.'
                );


                return;

            }


            /*
             * SUCCESS
             */

            alert(
                data.message
                +
                '\n\nPosted records: '
                +
                data.posted_count
            );


            /*
             * Hide Save & Post
             */

            serviceCreditActions.style.display =
                'none';


            /*
             * Optional:
             * reload page after successful post.
             */

            window.location.reload();

        })

        .catch(function (error) {

            console.error(error);


            alert(
                'An error occurred while posting the service credits.'
            );

        })

        .finally(function () {

            button.disabled = false;

            button.textContent =
                'Save & Post';

        });

    }
);
  </script>

</body>
</html>
