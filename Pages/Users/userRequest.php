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

// ── AJAX: save font size (handled by settings page only, ignore here) ──
if (!empty($_POST['ajax_save_font_size'])) {
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
$stmtLT = $pdo->prepare(
    "SELECT leave_type_id, leave_type_name FROM leave_types WHERE is_active = 1 ORDER BY leave_type_name"
);
$stmtLT->execute([]);
$leaveTypes = $stmtLT->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSHS-SAC | Request</title>
  <link rel="stylesheet" href="../../styles/user.css" />
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
          integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
          crossorigin="anonymous"></script>
</head>
<body>
  <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
  <div class="app-shell">

    <!-- ── SIDEBAR: Request active ───────────────────────────── -->
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
            <a href="userRequest.php" class="nav-item active" aria-current="page">
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
            <a href="userSettings.php" class="nav-item">
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
        <span class="topbar-svg-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="12" y2="15"/></svg>
        </span>
        <h1 class="topbar-title">Request</h1>
        <button class="hamburger" id="hamburger" type="button"
                aria-controls="sidebar" aria-expanded="false"
                aria-label="Toggle navigation menu">
          <span></span><span></span><span></span>
        </button>
      </header>

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
              &#128206; Request a Leave
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
                  &ndash; <?= date('m/d/Y', strtotime($r['end_datetime'])) ?>
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
    </div>
  </div>

  <!-- Modal 1: Leave Form -->
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
        <button type="button" id="reviewLeaveBtn" class="btn-yellow">Review &#8594;</button>
      </div>
    </div>
  </div>

  <!-- Modal 2: Review Leave -->
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
          &#8505;&#65039; Approved leave requests may deduct from your service credit balance if applicable.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="backToEditBtn" class="btn-secondary">&#8592; Edit</button>
        <button type="button" id="submitLeaveBtn" class="btn-yellow">Request and Send</button>
      </div>
    </div>
  </div>

  <!-- Modal 3: Request Sent -->
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

  <!-- Modal 4: Request Details -->
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
          &#8505;&#65039; Approved leave requests may deduct from your service credit balance if applicable.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="closeDetailsBtn" class="btn-yellow">&#8592; Back to Request</button>
      </div>
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
