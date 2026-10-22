
<?php
require_once '../../session.php';
require_once '../../database.php';

start_app_session();
require_admin();

$pdo = db();

$sql = "
    SELECT
        lr.leave_request_id,
        lr.request_number,
        lr.date_filed,
        lr.start_datetime,
        lr.end_datetime,
        lr.total_leave_days,
        lr.reason_details,
        lr.status,
        lr.credit_type_id,
        lr.reviewed_by,
        lr.reviewed_at,
        CONCAT(
            reviewer.first_name, ' ',
            COALESCE(CONCAT(reviewer.middle_name, ' '), ''),
            reviewer.last_name
        ) AS reviewer_name,
        
        COALESCE((
            SELECT SUM(
                CASE
                    WHEN sct2.transaction_type = 'EARNED'
                        THEN sct2.credit_amount
                    WHEN sct2.transaction_type = 'DEDUCTED'
                        THEN -sct2.credit_amount
                    ELSE 0
                END
            )
            FROM service_credit_transactions sct2
            WHERE sct2.employee_id = lr.employee_id
              AND sct2.credit_type_id = 1
        ), 0) AS local_balance,

        COALESCE((
            SELECT SUM(
                CASE
                    WHEN sct2.transaction_type = 'EARNED'
                        THEN sct2.credit_amount
                    WHEN sct2.transaction_type = 'DEDUCTED'
                        THEN -sct2.credit_amount
                    ELSE 0
                END
            )
            FROM service_credit_transactions sct2
            WHERE sct2.employee_id = lr.employee_id
              AND sct2.credit_type_id = 2
        ), 0) AS national_balance,

        e.employee_number,
        e.first_name,
        e.middle_name,
        e.last_name,
        e.position,

        lt.leave_type_name,
        sct.credit_type_name

    FROM leave_requests lr

    INNER JOIN employees e
        ON e.employee_id = lr.employee_id

    INNER JOIN leave_types lt
        ON lt.leave_type_id = lr.leave_type_id

    LEFT JOIN service_credit_types sct
        ON sct.credit_type_id = lr.credit_type_id

    LEFT JOIN accounts AS reviewer_account
    ON reviewer_account.account_id = lr.reviewed_by

    LEFT JOIN employees AS reviewer
        ON reviewer.employee_id = reviewer_account.employee_id

    ORDER BY
        CASE
            WHEN lr.status = 'Pending' THEN 0
            ELSE 1
        END,
        lr.date_filed DESC,
        lr.leave_request_id DESC
";

$stmt = $pdo->query($sql);
$requests = $stmt->fetchAll();

function txEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSHS-SAC | Transactions</title>
  <link rel="stylesheet" href="../../styles/admin.css" />

  <!-- ============================================================
       PAGE-SCOPED STYLES — adminTransactions.php
  ============================================================ -->
  <style>

    /* ── PAGE WRAPPER ───────────────────────────────────────── */
    .page-content.tx-page {
      display: flex;
      flex-direction: column;
      padding: 28px 32px;
      background-color: var(--clr-cream-bg);
    }

    /* ── MAIN CARD ──────────────────────────────────────────── */
    .tx-card {
      background-color: #FEFCF5;
      border: 1px solid #E8E0CC;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    /* Dark-purple header bar */
    .tx-card-header {
      background-color: #2D0057;
      padding: 16px 24px;
    }

    .tx-card-header-title {
      font-size: 0.9375rem;
      font-weight: 700;
      color: #fff;
    }

    /* Card body */
    .tx-card-body {
      padding: 24px 24px 28px;
    }

    /* ── TOOLBAR ────────────────────────────────────────────── */
    .tx-toolbar {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }

    /* Search bar */
    .tx-search-wrap {
      position: relative;
      flex: 1;
      min-width: 200px;
      max-width: 400px;
    }

    .tx-search-wrap input {
      width: 100%;
      padding: 10px 42px 10px 16px;
      border: 1.5px solid #ccc;
      border-radius: 8px;
      font-size: 0.875rem;
      color: #444;
      background-color: #fff;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .tx-search-wrap input::placeholder { color: #aaa; }

    .tx-search-wrap input:focus {
      border-color: #6B0FBA;
      box-shadow: 0 0 0 3px rgba(107,15,186,0.12);
    }

    .tx-search-icon {
      position: absolute;
      right: 13px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      stroke: #999;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      pointer-events: none;
    }

    /* Status / Type dropdown buttons */
    .tx-filter-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 18px;
      background-color: #F5C518;
      color: #1a1a1a;
      font-size: 0.875rem;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease;
      position: relative;
    }

    .tx-filter-btn:hover { background-color: #e6b800; }

    .tx-filter-btn svg {
      width: 13px;
      height: 13px;
      stroke: #1a1a1a;
      fill: none;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      transition: transform 0.2s ease;
    }

    /* Wrapper holds both the button and the dropdown panel */
    .tx-filter-wrap {
      position: relative;
      display: inline-block;
    }

    .tx-filter-btn.is-open svg { transform: rotate(180deg); }

    /* Dropdown panel — positioned under the wrapper */
    .tx-dropdown-panel {
      display: none;
      position: absolute;
      top: calc(100% + 6px);
      left: 0;
      min-width: 140px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.12);
      z-index: 20;
      overflow: hidden;
    }

    /* Show panel when wrapper has .is-open */
    .tx-filter-wrap.is-open .tx-dropdown-panel { display: block; }
    .tx-filter-wrap.is-open .tx-filter-btn svg { transform: rotate(180deg); }

    .tx-dropdown-panel button {
      display: block;
      width: 100%;
      padding: 9px 16px;
      text-align: left;
      font-size: 0.8125rem;
      color: #333;
      background: none;
      border: none;
      cursor: pointer;
    }

    .tx-dropdown-panel button:hover { background-color: #f3eeff; }

    /* ── TABLE ──────────────────────────────────────────────── */
    .tx-table-wrap {
      overflow-x: auto;
      border: 1px solid #DDD6C8;
      border-radius: 10px;
    }

    .tx-table {
      width: 100%;
      min-width: 700px;
      border-collapse: collapse;
      font-size: 0.8125rem;
    }

    .tx-table thead tr {
      background-color: #fff;
      border-bottom: 1.5px solid #E0D8C8;
    }

    .tx-table thead th {
      padding: 12px 16px;
      text-align: left;
      font-weight: 700;
      color: #222;
      white-space: nowrap;
    }

    .tx-table tbody tr:nth-child(odd)  { background-color: #EDE7F6; }
    .tx-table tbody tr:nth-child(even) { background-color: #fff; }
    .tx-table tbody tr:hover           { background-color: #E0D6EF; }

    .tx-table tbody td {
      padding: 11px 16px;
      color: #333;
      border-bottom: 1px solid #EDE8DC;
    }

    .tx-table tbody tr:last-child td { border-bottom: none; }

    /* "View" action link */
    .tx-view-btn {
      background: none;
      border: none;
      color: #6B0FBA;
      font-size: 0.8125rem;
      font-weight: 600;
      cursor: pointer;
      padding: 0;
      text-decoration: underline;
      transition: opacity 0.2s ease;
    }

    .tx-view-btn:hover { opacity: 0.7; }

    /* ── MODAL OVERLAY ──────────────────────────────────────── */
    .tx-modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.55);
      z-index: 100;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .tx-modal-overlay.is-open {
      display: flex;
    }

    /* Modal card */
    .tx-modal {
      background: #fff;
      border-radius: 14px;
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      padding: 32px 36px 28px;
      position: relative;
      box-shadow: 0 8px 40px rgba(0,0,0,0.25);
    }

    /* Scrollbar inside modal */
    .tx-modal::-webkit-scrollbar { width: 5px; }
    .tx-modal::-webkit-scrollbar-track { background: #f5f0ff; }
    .tx-modal::-webkit-scrollbar-thumb { background: #c0a8e0; border-radius: 10px; }

    /* Close button */
    .tx-modal-close {
      position: absolute;
      top: 18px;
      right: 20px;
      background: none;
      border: none;
      font-size: 1.25rem;
      color: #666;
      cursor: pointer;
      line-height: 1;
      padding: 4px 6px;
      border-radius: 4px;
      transition: background-color 0.15s ease;
    }

    .tx-modal-close:hover { background-color: #f0f0f0; color: #333; }

    /* Modal title */
    .tx-modal-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #111;
      margin-bottom: 18px;
    }

    /* Detail rows */
    .tx-detail-row {
      display: flex;
      gap: 16px;
      margin-bottom: 6px;
      font-size: 0.875rem;
    }

    .tx-detail-label {
      font-weight: 700;
      color: #111;
      min-width: 160px;
      flex-shrink: 0;
    }

    .tx-detail-value {
      color: #555;
    }

    /* Section headings inside modal */
    .tx-section-heading {
      font-size: 1rem;
      font-weight: 700;
      color: #111;
      margin: 20px 0 10px;
    }

    /* Divider */
    .tx-divider {
      height: 1px;
      background-color: #eee;
      margin: 4px 0 16px;
    }

    /* ── STATUS BADGE ───────────────────────────────────────── */
    .tx-status-dot {
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin-right: 6px;
      vertical-align: middle;
      background-color: #F5C518; /* default: pending yellow */
    }

    .tx-status-text { vertical-align: middle; }

    /* ── SUBMITTED DOCUMENT ─────────────────────────────────── */
    .tx-doc-filename {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.875rem;
      color: #333;
      text-decoration: underline;
      margin-bottom: 8px;
    }

    .tx-doc-filename svg {
      width: 14px;
      height: 14px;
      stroke: #555;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      flex-shrink: 0;
    }

    /* "File available after approval" note — pending only */
    .tx-doc-pending-note {
      font-size: 0.8rem;
      color: #888;
      font-style: italic;
      margin-bottom: 8px;
    }

    /* Download button — approved only */
    .tx-btn-download {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 22px;
      background-color: #2ECC40;
      color: #fff;
      font-size: 0.875rem;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      margin-bottom: 14px;
      transition: background-color 0.2s ease;
    }

    .tx-btn-download:hover { background-color: #27ae38; }

    .tx-btn-download svg {
      width: 15px;
      height: 15px;
      stroke: #fff;
      fill: none;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* File not available — rejected */
    .tx-doc-unavailable {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-size: 0.8125rem;
      color: #CC2200;
      margin-bottom: 14px;
    }

    .tx-doc-unavailable svg {
      width: 14px;
      height: 14px;
      stroke: #CC2200;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* ── ADMIN ACTION BUTTONS ───────────────────────────────── */
    .tx-action-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-top: 24px;
      padding-top: 16px;
      border-top: 1px solid #eee;
    }

    .tx-btn-approve {
      padding: 11px 28px;
      background-color: #2ECC40;
      color: #fff;
      font-size: 0.9rem;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .tx-btn-approve:hover { background-color: #27ae38; }

    .tx-btn-reject {
      padding: 11px 28px;
      background-color: #E31C1C;
      color: #fff;
      font-size: 0.9rem;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .tx-btn-reject:hover { background-color: #c01414; }

    /* ── AUDIT TRAIL (approved / rejected) ──────────────────── */
    .tx-audit {
      margin-top: 14px;
      font-size: 0.875rem;
    }

    .tx-audit-row {
      display: flex;
      gap: 16px;
      margin-bottom: 4px;
    }

    .tx-audit-label {
      font-weight: 700;
      color: #111;
      min-width: 110px;
    }

    .tx-audit-value { color: #555; }

    /* ═══════════════════════════════════════════════════════════
       STATE-DRIVEN VISIBILITY via [data-status] on .tx-modal
    ═══════════════════════════════════════════════════════════ */

    /* ── PENDING ────────────────────────────────────────────── */
    .tx-modal[data-status="pending"] .tx-status-dot  { background-color: #F5C518; }

    /* Show pending note, hide download + unavailable + audit */
    .tx-modal[data-status="pending"] .tx-doc-pending-note  { display: block; }
    .tx-modal[data-status="pending"] .tx-btn-download       { display: none; }
    .tx-modal[data-status="pending"] .tx-doc-unavailable    { display: none; }
    .tx-modal[data-status="pending"] .tx-audit              { display: none; }
    .tx-modal[data-status="pending"] .tx-action-row         { display: flex; }

    /* ── APPROVED ───────────────────────────────────────────── */
    .tx-modal[data-status="approved"] .tx-status-dot  { background-color: #2ECC40; }

    .tx-modal[data-status="approved"] .tx-doc-pending-note  { display: none; }
    .tx-modal[data-status="approved"] .tx-btn-download       { display: inline-flex; }
    .tx-modal[data-status="approved"] .tx-doc-unavailable    { display: none; }
    .tx-modal[data-status="approved"] .tx-audit              { display: block; }
    .tx-modal[data-status="approved"] .tx-action-row         { display: none; }
    /* Audit label for approved */
    .tx-modal[data-status="approved"] .tx-audit-by-label::after   { content: "Approved By:"; }
    .tx-modal[data-status="approved"] .tx-audit-on-label::after   { content: "Approved On:"; }

    /* ── REJECTED ───────────────────────────────────────────── */
    .tx-modal[data-status="rejected"] .tx-status-dot  { background-color: #E31C1C; }

    .tx-modal[data-status="rejected"] .tx-doc-pending-note  { display: none; }
    .tx-modal[data-status="rejected"] .tx-btn-download       { display: none; }
    .tx-modal[data-status="rejected"] .tx-doc-unavailable    { display: inline-flex; }
    .tx-modal[data-status="rejected"] .tx-audit              { display: block; }
    .tx-modal[data-status="rejected"] .tx-action-row         { display: none; }
    /* Audit label for rejected */
    .tx-modal[data-status="rejected"] .tx-audit-by-label::after   { content: "Rejected By:"; }
    .tx-modal[data-status="rejected"] .tx-audit-on-label::after   { content: "Rejected On:"; }

    /* ── RESPONSIVE ─────────────────────────────────────────── */
    @media (max-width: 768px) {
      .page-content.tx-page { padding: 20px 16px; }
      .tx-modal { padding: 24px 20px 20px; }
      .tx-detail-label { min-width: 130px; }
    }

    @media (max-width: 500px) {
      .tx-action-row { flex-direction: column; }
      .tx-btn-approve, .tx-btn-reject { width: 100%; text-align: center; }
      .tx-modal { max-width: 100%; }
    }

    .tx-credit-select {
        width: 100%;
        max-width: 240px;
        padding: 8px 10px;
        border: 1px solid #D0C8B8;
        border-radius: 6px;
        background-color: #fff;
        font: inherit;
        color: #333;
    }

    .tx-credit-select:focus {
        outline: 2px solid rgba(107, 15, 186, 0.2);
        border-color: #6B0FBA;
    }
    .tx-credit-select:disabled {
    background-color: #f1f1f1;
    color: #777;
    cursor: not-allowed;
    opacity: 0.85;
}
/* Ensure hidden transaction controls stay hidden */
#txCreditPoolGroup[hidden],
#txFinalizationNote[hidden] {
    display: none !important;
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

      <img class="sidebar-avatar" src="https://placehold.co/72x72/6B0FBA/FFFFFF?text=A" alt="Admin profile photo" />
      <p class="sidebar-name">Admin Name</p>
      <p class="sidebar-role">System Administrator</p>
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

          <li>
            <a href="adminServiceCredits.php" class="nav-item">
              <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="5"/><path d="M7.5 14.5L5 21l7-2.5L19 21l-2.5-6.5"/></svg>
              </span>
              Service Credits
            </a>
          </li>

          <!-- Transactions — active -->
          <li>
            <a href="adminTransactions.php" class="nav-item active" aria-current="page">
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
          <svg style="width:24px;height:24px;stroke:#F5C518;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round;vertical-align:middle;margin-right:8px;" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="2" width="16" height="20" rx="2"/>
            <line x1="8" y1="7" x2="16" y2="7"/>
            <line x1="8" y1="11" x2="16" y2="11"/>
            <line x1="8" y1="15" x2="12" y2="15"/>
          </svg>
          Transactions
        </h1>
        <button class="hamburger" id="hamburger" type="button"
          aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation menu">
          <span></span><span></span><span></span>
        </button>
      </header>

      <!-- Page content -->
      <main class="page-content tx-page" id="mainContent">

        <!-- ── TRANSACTION REQUEST CARD ──────────────────── -->
        <div class="tx-card">

          <!-- Dark-purple header -->
          <div class="tx-card-header">
            <p class="tx-card-header-title">Transaction Request</p>
          </div>

          <!-- Card body -->
          <div class="tx-card-body">

            <!-- Toolbar: search + filters -->
            <div class="tx-toolbar" role="toolbar" aria-label="Transaction filters">

              <!-- Search -->
              <div class="tx-search-wrap">
                <input type="search" id="txSearch" placeholder="Search..." aria-label="Search transactions" autocomplete="off" />
                <svg class="tx-search-icon" viewBox="0 0 24 24" aria-hidden="true">
                  <circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/>
                </svg>
              </div>

              <!-- Status dropdown -->
              <div class="tx-filter-wrap" id="statusDropWrap">
                <button type="button" class="tx-filter-btn" id="statusDropBtn" aria-haspopup="listbox" aria-expanded="false">
                  Status
                  <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="tx-dropdown-panel" role="listbox" id="statusDropPanel" aria-label="Status filter">
                  <button type="button" role="option">All</button>
                  <button type="button" role="option">Pending</button>
                  <button type="button" role="option">Approved</button>
                  <button type="button" role="option">Rejected</button>
                </div>
              </div>

              <!-- Type dropdown -->
              <div class="tx-filter-wrap" id="typeDropWrap">
                <button type="button" class="tx-filter-btn" id="typeDropBtn" aria-haspopup="listbox" aria-expanded="false">
                  Type
                  <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="tx-dropdown-panel" role="listbox" id="typeDropPanel" aria-label="Type filter">
                  <button type="button" role="option">All</button>
                  <button type="button" role="option">Local</button>
                  <button type="button" role="option">National</button>
                </div>
              </div>

            </div>
            <!-- END TOOLBAR -->

            <!-- Table -->
            <div class="tx-table-wrap">
              <table class="tx-table" aria-label="Transaction requests">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Request Type</th>
                    <th>Date</th>
                    <th>Service Credits</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                    
                        <?php if (empty($requests)): ?>

                            <tr>
                                <td colspan="6" style="text-align: center;">
                                    No leave requests found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($requests as $request): ?>

                                <?php
                                    $employeeName = trim(
                                        $request['first_name'] . ' ' .
                                        ($request['middle_name'] ?? '') . ' ' .
                                        $request['last_name']
                                    );

                                    $dateFiled = date(
                                        'M d, Y',
                                        strtotime($request['date_filed'])
                                    );

                                    $creditDisplay = $request['credit_type_name']
                                        ? $request['credit_type_name']
                                        : 'Not selected';
                                ?>

                                <tr
                                    data-request-id="<?= (int) $request['leave_request_id'] ?>"
                                    data-status="<?= txEscape(strtolower($request['status'])) ?>"
                                    data-credit-type="<?= txEscape($creditDisplay) ?>"
                                >
                                    <td><?= txEscape($employeeName) ?></td>

                                    <td>
                                        <?= txEscape($request['leave_type_name']) ?>
                                    </td>

                                    <td><?= txEscape($dateFiled) ?></td>

                                    <td>
                                        <?= txEscape($request['total_leave_days']) ?>
                                        <?= (float) $request['total_leave_days'] === 1.0
                                            ? 'Day'
                                            : 'Days' ?>
                                        <small>
                                            (<?= txEscape($creditDisplay) ?>)
                                        </small>
                                    </td>

                                    <td>
                                        <?= txEscape($request['status']) ?>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="tx-view-btn"
                                            data-request-id="<?= (int) $request['leave_request_id'] ?>"
                                            data-request-number="<?= txEscape($request['request_number']) ?>"
                                            data-status="<?= txEscape(strtolower($request['status'])) ?>"
                                            data-employee-name="<?= txEscape($employeeName) ?>"
                                            data-employee-number="<?= txEscape($request['employee_number']) ?>"
                                            data-position="<?= txEscape($request['position']) ?>"
                                            data-leave-type="<?= txEscape($request['leave_type_name']) ?>"
                                            data-start-date="<?= txEscape(date('F j, Y', strtotime($request['start_datetime']))) ?>"
                                            data-end-date="<?= txEscape(date('F j, Y', strtotime($request['end_datetime']))) ?>"
                                            data-total-days="<?= txEscape($request['total_leave_days']) ?>"
                                            data-credit-type="<?= txEscape($creditDisplay) ?>"
                                            data-reason="<?= txEscape($request['reason_details'] ?? '') ?>"
                                            data-local-balance="<?= txEscape($request['local_balance']) ?>"
                                            data-national-balance="<?= txEscape($request['national_balance']) ?>"
                                            data-reviewed-by="<?= txEscape($request['reviewer_name'] ?? '') ?>"
                                            data-reviewed-at="<?= txEscape(
                                                !empty($request['reviewed_at'])
                                                    ? date('F j, Y g:i A', strtotime($request['reviewed_at']))
                                                    : ''
                                            ) ?>"
                                        >
                                            View
                                        </button>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>
                
                </tbody>
              </table>
            </div>
            <!-- END TABLE -->

          </div><!-- END CARD BODY -->
        </div><!-- END TX CARD -->

      </main>
    </div>
    <!-- END MAIN WRAPPER -->

  </div>
  <!-- END APP SHELL -->

  <!-- ══════════════════════════════════════════════════════════
       REQUEST DETAILS MODAL
       State is controlled by data-status="pending|approved|rejected"
       on the .tx-modal element. CSS shows/hides sections accordingly.
  ══════════════════════════════════════════════════════════════ -->
  <div class="tx-modal-overlay" id="txModalOverlay" role="dialog" aria-modal="true" aria-labelledby="txModalTitle">

    <div class="tx-modal" id="txModal" data-status="pending">

      <!-- Close button -->
      <button type="button" class="tx-modal-close" id="txModalClose" aria-label="Close dialog">&#10005;</button>

      <!-- Title -->
      <h2 class="tx-modal-title" id="txModalTitle">Request Details</h2>

      <!-- Transaction Number + Status -->
    <div class="tx-detail-row">
        <span class="tx-detail-label">Transaction Number</span>
        <span class="tx-detail-value" id="txRequestNumber">—</span>
    </div>
      <div class="tx-detail-row" style="margin-bottom:4px;">
        <span class="tx-detail-label">Status</span>
        <span class="tx-detail-value">
          <span class="tx-status-dot" aria-hidden="true"></span>
          <!-- text injected via CSS ::after on .tx-status-text -->
          <span class="tx-status-text" aria-label="status" id="txStatusText"></span>
        </span>
      </div>

      <div class="tx-divider"></div>

      <!-- Employee Information -->
      <h3 class="tx-section-heading">Employee Information</h3>
      <div class="tx-detail-row">
        <span class="tx-detail-label">Employee Name</span>
        <span class="tx-detail-value" id="txEmployeeName">—</span>
      </div>
      <div class="tx-detail-row">
        <span class="tx-detail-label">Employee Number</span>
        <span class="tx-detail-value" id="txEmployeeNumber">—</span>
      </div>
      <div class="tx-detail-row">
        <span class="tx-detail-label">Position</span>
        <span class="tx-detail-value" id="txPosition">—</span>
      </div>

      <!-- Request Information -->
      <h3 class="tx-section-heading">Request Information</h3>

      <div class="tx-detail-row"> 
        <span class="tx-detail-label">Leave Type</span> 
        <span class="tx-detail-value" id="txLeaveType">—</span> 
    </div>
    <div class="tx-detail-row">
        <span class="tx-detail-label">Reason</span>
        <span class="tx-detail-value" id="txReason">—</span>
    </div>
      <div class="tx-detail-row">
        <span class="tx-detail-label">Start Date</span>
        <span class="tx-detail-value" id="txStartDate">—</span>
      </div>
      <div class="tx-detail-row">
        <span class="tx-detail-label">End Date</span>
        <span class="tx-detail-value" id="txEndDate">—</span>
      </div>
      <div class="tx-detail-row">
        <span class="tx-detail-label">Total Days</span>
        <span class="tx-detail-value" id="txTotalDays">—</span>
      </div>

      <!-- Service Credit Information -->
      <h3 class="tx-section-heading">Service Credit Information</h3>

      <div class="tx-detail-row">
          <span class="tx-detail-label">Service Credit Type</span>
          <span class="tx-detail-value" id="txCreditType">Not selected</span>
      </div>

      <div class="tx-detail-row">
          <span class="tx-detail-label">Current Balance</span>
          <span class="tx-detail-value" id="txCurrentBalance">
              Select a credit pool
          </span>
      </div>

      <!-- Hidden until Approved is selected -->
      <div class="tx-detail-row" id="txCreditPoolGroup" hidden>
          <label class="tx-detail-label" for="txDeductionPool">
              Deduct From
          </label>

          <select id="txDeductionPool" class="tx-credit-select">
              <option value="">-- Select Credit Pool --</option>
              <option value="1">Local Service Credits</option>
              <option value="2">National Service Credits</option>
          </select>
      </div>

      <div class="tx-detail-row">
          <span class="tx-detail-label">Deduction</span>
          <span class="tx-detail-value" id="txDeductionAmount">—</span>
      </div>

      <div class="tx-detail-row">
          <span class="tx-detail-label">Remaining Balance</span>
          <span class="tx-detail-value" id="txRemainingBalance">—</span>
      </div>

      <!-- Submitted Document -->
      <h3 class="tx-section-heading">Submitted Document</h3>

      
    <!-- Generate Form 6 for printing and signatures -->
    <div class="tx-form6-actions" id="txForm6Actions">
      <button type="button" class="tx-btn-download" id="txGenerateForm6">
        Generate / Print Form 6
      </button>
    </div>


      <!-- Filename — always visible
      <div class="tx-doc-filename">
        Paperclip icon 
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66L9.41 17.41a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
        </svg>
        Apply_for_Leave.pdf
      </div> -->

      <!-- Pending: note that file is available after approval -->
      <p class="tx-doc-pending-note">This file is available for download and print.</p>

      <!-- Approved: download button -->
      <!-- <button type="button" class="tx-btn-download"> -->
        <!-- Download icon -->
        <!-- <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Download File
      </button> -->

      <!-- Rejected: file not available -->
      <div class="tx-doc-unavailable" aria-live="polite">
        <!-- Lock icon -->
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        File download and print not available
      </div>
     
      <!-- Audit trail (approved / rejected) -->
      <div class="tx-audit" aria-live="polite">
          <div class="tx-audit-row">
              <span class="tx-audit-label tx-audit-by-label"></span>
              <span class="tx-audit-value" id="txReviewedBy"></span>
          </div>

          <div class="tx-audit-row">
              <span class="tx-audit-label tx-audit-on-label"></span>
              <span class="tx-audit-value" id="txReviewedAt">—</span>
          </div>
      </div>


      <!-- Admin Action buttons (hidden once actioned) -->
      
    <!-- Finalize the request after receiving the signed Form 6 -->
    <div class="tx-action-row" id="txFinalizationControls">
      <div class="tx-form-group">
        <label for="txFinalStatus">Final Decision</label>
        <select id="txFinalStatus" class="tx-credit-select">
          <option value="">Select decision</option>
          <option value="Approved">Approved</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>


      <button type="button" class="tx-btn-approve" id="txBtnSaveDecision">
        Save Final Decision
      </button>
    </div>

    <p id="txFinalizationNote" class="tx-muted" hidden>
      This request has already been finalized. Its decision cannot be changed here.
    </p>

    </div><!-- END .tx-modal -->
  </div>
  <!-- END MODAL -->

  <!-- Shared admin JS (hamburger / mobile sidebar) -->
  <script src="../../scripts/admin.js"></script>
  <script src="../../scripts/settings.js"></script>

  <!-- ============================================================
       PAGE-SCOPED JS — adminTransactions.html
       Handles:
         1. "View" button → opens modal in pending state
         2. Close (×) / overlay click / Escape → closes modal
         3. Approve button → sets data-status="approved"
         4. Reject  button → sets data-status="rejected"
         5. Status / Type dropdown toggles
       No fake fetches, no alerts, no timeouts.
  ============================================================ -->
  <script>
  (function () {
    'use strict';

    /* ── Element refs ──────────────────────────────────────── */
    var overlay    = document.getElementById('txModalOverlay');
    var modal      = document.getElementById('txModal');
    var closeBtn   = document.getElementById('txModalClose');
    var viewBtns   = document.querySelectorAll('.tx-view-btn');
    
    var deductionPool = document.getElementById('txDeductionPool');
    var currentBalanceEl = document.getElementById('txCurrentBalance');
    var deductionAmountEl = document.getElementById('txDeductionAmount');
    var remainingBalanceEl = document.getElementById('txRemainingBalance');

    var selectedRequest = null;

    var deductionPool = document.getElementById('txDeductionPool');
    var currentBalanceEl = document.getElementById('txCurrentBalance');
    var deductionAmountEl = document.getElementById('txDeductionAmount');
    var remainingBalanceEl = document.getElementById('txRemainingBalance');

    var selectedRequest = null;

    var finalStatusSelect = document.getElementById('txFinalStatus');
    var creditPoolGroup = document.getElementById('txCreditPoolGroup');
    var saveDecisionBtn = document.getElementById('txBtnSaveDecision');
    var finalizationControls = document.getElementById('txFinalizationControls');
    var finalizationNote = document.getElementById('txFinalizationNote');
    
    var generateForm6Btn = document.getElementById('txGenerateForm6');

    finalStatusSelect.addEventListener('change', function () {
        var isApproval = this.value === 'Approved';

        creditPoolGroup.hidden = !isApproval;

        if (!isApproval) {
            deductionPool.value = '';
        }

        updateCreditPreview();
    });

  finalStatusSelect.addEventListener('change', function () {
      var isApproval = this.value === 'Approved';

      creditPoolGroup.hidden = !isApproval;

      if (!isApproval) {
          deductionPool.value = '';
      }

      updateCreditPreview();
  });

/* 1. OPEN MODAL — display the selected database request */

viewBtns.forEach(function (btn) {

    btn.addEventListener('click', function () {

        // Get the selected request's information.
        var request = btn.dataset;

        selectedRequest = {
            requestId: request.requestId,
            status: request.status,
            totalDays: request.totalDays,
            localBalance: request.localBalance,
            nationalBalance: request.nationalBalance,
            creditType: request.creditType
        };

        // Display request details.
        document.getElementById('txRequestNumber').textContent =
            request.requestNumber || '—';

        document.getElementById('txStatusText').textContent =
            request.status
                ? request.status.charAt(0).toUpperCase() +
                  request.status.slice(1)
                : 'Unknown';

        document.getElementById('txEmployeeName').textContent =
            request.employeeName || '—';

        document.getElementById('txEmployeeNumber').textContent =
            request.employeeNumber || '—';

        document.getElementById('txPosition').textContent =
            request.position || '—';

        document.getElementById('txLeaveType').textContent =
            request.leaveType || '—';

        document.getElementById('txStartDate').textContent =
            request.startDate || '—';

        document.getElementById('txEndDate').textContent =
            request.endDate || '—';

        document.getElementById('txTotalDays').textContent =
            request.totalDays
                ? request.totalDays + ' Days'
                : '—';

        document.getElementById('txCreditType').textContent =
            request.creditType || 'Not selected';

        document.getElementById('txReason').textContent =
            request.reason || 'Not provided';

        // Use the real status from the database.
        var actualStatus = request.status || 'pending';

        if (!['pending', 'approved', 'rejected'].includes(actualStatus)) {
            actualStatus = 'pending';
        }

      modal.dataset.status = actualStatus;

      document.getElementById('txReviewedBy').textContent = '(Admin) ' +
      request.reviewedBy || 'Unknown admin';

      document.getElementById('txReviewedAt').textContent =
      request.reviewedAt || 'Not available';

      
        /* Show Form 6 generation for Pending and Approved requests.
          Do not offer it for Rejected requests. */
        if (generateForm6Btn) {
            generateForm6Btn.style.display =
                actualStatus === 'rejected' ? 'none' : 'inline-flex';
        }

        
/* Generate Form 6 without changing the request status or credits. */
if (generateForm6Btn) {
    generateForm6Btn.addEventListener('click', function () {
        if (!selectedRequest || !selectedRequest.requestId) {
            alert('Please open a leave request first.');
            return;
        }

        if (modal.dataset.status === 'rejected') {
            alert('Form 6 generation is unavailable for rejected requests.');
            return;
        }

        var url = '../../generateForm6.php?request_id=' +
            encodeURIComponent(selectedRequest.requestId);

        window.open(url, '_blank', 'noopener');
    });
}


      var isPending = actualStatus === 'pending';

      finalizationControls.hidden = !isPending;
      finalizationNote.hidden = isPending;

      finalStatusSelect.value = '';
      creditPoolGroup.hidden = true;

      deductionPool.disabled = !isPending;
      deductionPool.value = '';

      currentBalanceEl.textContent = 'Select a credit pool';
      deductionAmountEl.textContent = '—';
      remainingBalanceEl.textContent = '—';
      remainingBalanceEl.style.color = '#555';

      openModal();
    });

});

    /* ── 2. CLOSE MODAL ────────────────────────────────────── */
    if (closeBtn) {
      closeBtn.addEventListener('click', closeModal);
    }

    // Click on the dark overlay (outside the card) closes modal
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });

    // Escape key closes modal
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
        closeModal();
      }
    });

/* ── 3. SAVE FINAL DECISION THROUGH PHP ─────────────── */

async function processFinalDecision() {
    if (!selectedRequest || !selectedRequest.requestId) {
        alert('Please open a leave request first.');
        return;
    }

    if (selectedRequest.status !== 'pending') {
        alert('This request has already been finalized.');
        return;
    }

    var targetStatus = finalStatusSelect.value;

    if (!['Approved', 'Rejected'].includes(targetStatus)) {
        alert('Please select Approved or Rejected.');
        return;
    }

    // A credit pool is required only for approval.
    if (targetStatus === 'Approved' && !deductionPool.value) {
        alert('Please select a service credit pool.');
        return;
    }

    var confirmation = targetStatus === 'Approved'
        ? 'Confirm official approval and deduct service credits?'
        : 'Confirm official rejection? No credits will be deducted.';

    if (!window.confirm(confirmation)) {
        return;
    }

    var formData = new FormData();
    formData.append('target_status', targetStatus);
    formData.append('request_id', selectedRequest.requestId);

    if (targetStatus === 'Approved') {
        formData.append('credit_type_id', deductionPool.value);
    }

    saveDecisionBtn.disabled = true;
    saveDecisionBtn.textContent = 'Saving...';

    try {
        var response = await fetch('processTransaction.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        var result = await response.json();

        if (!response.ok || !result.success) {
            alert(result.message || 'Unable to save the final decision.');
            return;
        }

        alert(result.message || 'Final decision saved successfully.');

        // Reload the page to display the updated database status
        // and refreshed service-credit balances.
        window.location.reload();

    } catch (error) {
        console.error('Final decision error:', error);
        alert('A server or network error occurred. Please try again.');
    } finally {
        saveDecisionBtn.disabled = false;
        saveDecisionBtn.textContent = 'Save Final Decision';
    }
}

if (saveDecisionBtn) {
    saveDecisionBtn.addEventListener('click', processFinalDecision);
}

    
    /* ── 5. SEARCH + STATUS / TYPE FILTERS ─────────────────── */

    var searchInput = document.getElementById('txSearch');
    var transactionRows = Array.from(
      document.querySelectorAll('.tx-table tbody tr[data-request-id]')
    );

    var selectedStatus = 'all';
    var selectedType = 'all';

    var statusWrap = document.getElementById('statusDropWrap');
    var typeWrap = document.getElementById('typeDropWrap');

    var filterWraps = document.querySelectorAll('.tx-filter-wrap');

    function applyFilters() {
      var searchTerm = searchInput.value.trim().toLowerCase();

      transactionRows.forEach(function (row) {
        var rowStatus = (row.dataset.status || '').toLowerCase();
        var rowType = (row.dataset.creditType || '').toLowerCase();
        var rowText = row.textContent.toLowerCase();

        var matchesSearch = rowText.includes(searchTerm);

        var matchesStatus =
          selectedStatus === 'all' ||
          rowStatus === selectedStatus;

        var matchesType =
          selectedType === 'all' ||
          rowType === selectedType;

        row.hidden = !(
          matchesSearch &&
          matchesStatus &&
          matchesType
        );
      });
    }

    // Search by employee name, leave type, date, credits, or status.
    if (searchInput) {
      searchInput.addEventListener('input', applyFilters);
    }

    // Open and close filter dropdowns.
    filterWraps.forEach(function (wrap) {
      var button = wrap.querySelector('.tx-filter-btn');

      button.addEventListener('click', function (event) {
        event.stopPropagation();

        var wasOpen = wrap.classList.contains('is-open');

        filterWraps.forEach(function (otherWrap) {
          otherWrap.classList.remove('is-open');
          otherWrap.querySelector('.tx-filter-btn')
            .setAttribute('aria-expanded', 'false');
        });

        if (!wasOpen) {
          wrap.classList.add('is-open');
          button.setAttribute('aria-expanded', 'true');
        }
      });
    });

    // Apply the selected option from either dropdown.
    document.querySelectorAll('.tx-dropdown-panel button')
      .forEach(function (option) {
        option.addEventListener('click', function (event) {
          event.stopPropagation();

          var wrap = option.closest('.tx-filter-wrap');
          var value = option.textContent.trim().toLowerCase();

          if (wrap === statusWrap) {
            selectedStatus = value;
            document.getElementById('statusDropBtn').firstChild.textContent =
              'Status: ' + option.textContent.trim() + ' ';
          }

          if (wrap === typeWrap) {
            selectedType = value;
            document.getElementById('typeDropBtn').firstChild.textContent =
              'Type: ' + option.textContent.trim() + ' ';
          }

          wrap.classList.remove('is-open');
          wrap.querySelector('.tx-filter-btn')
            .setAttribute('aria-expanded', 'false');

          applyFilters();
        });
      });

    // Clicking outside closes dropdowns.
    document.addEventListener('click', function () {
      filterWraps.forEach(function (wrap) {
        wrap.classList.remove('is-open');
        wrap.querySelector('.tx-filter-btn')
          .setAttribute('aria-expanded', 'false');
      });
    });

    // Click outside → close all dropdowns
    document.addEventListener('click', function () {
      filterWraps.forEach(function (w) {
        w.classList.remove('is-open');
        w.querySelector('.tx-filter-btn').setAttribute('aria-expanded', 'false');
      });
    });

    // Option click inside dropdown closes the parent wrapper
    document.querySelectorAll('.tx-dropdown-panel button').forEach(function (opt) {
      opt.addEventListener('click', function (e) {
        e.stopPropagation();
        var parentWrap = opt.closest('.tx-filter-wrap');
        if (parentWrap) {
          parentWrap.classList.remove('is-open');
          parentWrap.querySelector('.tx-filter-btn').setAttribute('aria-expanded', 'false');
        }
      });
    });

    /* ── HELPERS ───────────────────────────────────────────── */
    function openModal() {
      overlay.classList.add('is-open');
      // Prevent background scroll
      document.body.style.overflow = 'hidden';
      // Move focus to modal for accessibility
      modal.focus();
    }

    function closeModal() {
      overlay.classList.remove('is-open');
      document.body.style.overflow = '';
    }

    
function updateCreditPreview() {
    if (!selectedRequest) {
        return;
    }

    var poolId = deductionPool.value;
    var balance;

    if (poolId === '1') {
        balance = Number(selectedRequest.localBalance);
    } else if (poolId === '2') {
        balance = Number(selectedRequest.nationalBalance);
    } else {
        currentBalanceEl.textContent = 'Select a credit pool';
        deductionAmountEl.textContent = '—';
        remainingBalanceEl.textContent = '—';
        return;
    }

    var leaveDays = Number(selectedRequest.totalDays);

    if (!Number.isFinite(balance) || !Number.isFinite(leaveDays)) {
        currentBalanceEl.textContent = 'Unable to calculate';
        deductionAmountEl.textContent = '—';
        remainingBalanceEl.textContent = '—';
        return;
    }

    currentBalanceEl.textContent = balance.toFixed(2) + ' Credits';
   deductionAmountEl.innerHTML = `<span style="color: red;">- ${leaveDays.toFixed(2)} Credits</span>`;

    var remaining = balance - leaveDays;

    remainingBalanceEl.textContent =
        remaining.toFixed(2) + ' Credits';

    remainingBalanceEl.style.color =
        remaining < 0 ? '#CC2200' : '#555';
}

deductionPool.addEventListener('change', updateCreditPreview);

  })();
  </script>

</body>
</html>
