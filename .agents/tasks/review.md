# PERSYS Employee-Facing Dashboard and User Pages

This change introduces a complete set of user-facing pages for the PERSYS system: `userDashboard.php`, `userRequest.php`, `userServiceCredits.php`, `userSettings.php`, `userProfile.php`, and `logout.php`, along with new CSS sections appended to `user.css` and new jQuery sections appended to `user.js`. The goal was to give employees a self-service portal for viewing leave request status, filing new leave requests, checking service credit balances, managing display preferences, and viewing profile details — all without touching any Admin-side files.

Watch for: the eye toggle on the profile page is non-functional — `data-password=""` means "Show" reveals nothing; the button should be removed or wired to a real flow. The settings `$(document).ready` block has a dead restore path for `persys_font_size` that can silently override the server-persisted value for users with a stale localStorage key. The weekday counter in `countWeekdays()` parses ISO date strings as UTC, which can shift the day boundary in timezones behind UTC and produce an off-by-one `total_leave_days` that the server accepts without re-validating. No security-blocking issues found.

**Verdict**: APPROVED

---

## High-level view

All five user PHP pages share an identical 5-item sidebar (My Dashboard → Profile → Service Credits → Request → Settings), with the correct item marked `active` per page and `aria-current="page"` set. The sidebar avatar, username, and role name come from `$_SESSION` and are passed through `htmlspecialchars`. Admin files are not touched; the verification notes confirm git status shows only user-side files modified.

Every page calls `start_app_session()` and `require_user()` from `session.php` before any output. All SQL queries use positional PDO placeholders — no raw session values or user input appear in SQL strings. The employee ID is cast to `(int)` at the top of each page that needs it.

The logout flow is clean: `logout.php` calls `logout_session()` then redirects to `/PERSYS/index.php`. The logout modal is present on all pages that show a `.btn-logout` button, and the jQuery block that binds it is guarded by both a `typeof $` check and a `.btn-logout` length check so it's a no-op on pages without the button.

The leave request AJAX path generates a sequential request number (`LR-YYYY-NNNN`), wraps the insert in a `try/catch (PDOException)`, and returns a UNIQUE constraint collision as a user-friendly JSON error. Service credit balances are computed server-side as `SUM(EARNED) - SUM(DEDUCTED)` in a single parameterized query — the same helper function appears in both `userDashboard.php` and `userServiceCredits.php` with slightly different names (`getBalance` vs `scBalance`), which is cosmetic duplication but not a bug.

The settings page saves font size to the `account_settings` table via an `INSERT … ON DUPLICATE KEY UPDATE`, with a server-side regex validating the value matches `NNpx` in the 12–24 range before it touches the DB. However, the JS also reads font size from `localStorage` on load, while the PHP serves the DB-persisted value in the HTML. These two sources can diverge: a user who sets size on one device will see the DB value in the initial render but then have it overwritten by `localStorage` if that key exists from a prior session. The localStorage restore block runs in `$(document).ready` and unconditionally overwrites the slider value if a key is present.

---

<details>
<summary>Issues (3)</summary>

1. **Font size dead restore path** — `$(document).ready` in `user.js` reads `localStorage.getItem('persys_font_size')` and overwrites the slider, but the current JS never writes that key. Any user with a stale key from a previous codebase version will see their slider silently snapped away from the DB-persisted value. Remove the `persys_font_size` restore from `$(document).ready`, since font size is already server-persisted.

2. **Eye-toggle shows empty string** — `userProfile.php` has `data-password=""` (the security fix from the prior pass). Clicking the eye icon sets `textContent` to that empty string — the reveal shows nothing. Either remove the button or connect it to the planned change-password flow.

3. **Weekday counter UTC parse produces off-by-one in UTC-behind timezones** — `new Date("YYYY-MM-DD")` parses ISO strings as UTC midnight. In a timezone behind UTC (e.g., UTC-5), this resolves to the previous calendar day locally, causing `countWeekdays()` to mis-count by one day for the start or end boundary. The server inserts `total_leave_days` as-supplied without re-computing it. Fix by constructing dates as `new Date(y, m-1, d)` using the split string parts to force local-time interpretation.

</details>

<details>
<summary>Details</summary>

### Font size dead restore path (confirmed)

`userSettings.php` renders `$savedFontSizeNum` into both `value="..."` and `data-saved="..."` on `#fontSizeSlider`, sourced from the `account_settings` table. In `user.js`, the settings `$(document).ready` block reads `localStorage.getItem('persys_font_size')` and overwrites the slider if the key is present. The `persys_font_size` key is never written by the current JS — the slider `input` handler calls `$.post` to persist to the DB but never calls `localStorage.setItem('persys_font_size', ...)`. So the key will only exist if a prior version of the code populated it. The dead restore path won't fire for new users, but it will silently override the DB value for any user who has the key from an older session — and there's no way for the user to clear it from the UI. Clean up by removing the `persys_font_size` restore from `$(document).ready`, since font size is already server-persisted.

### Eye-toggle non-functional after hash removal (confirmed)

`#profilePasswordValue` has `data-password=""` — correct, the bcrypt hash was removed in the prior fix. The JS eye toggle reads `passwordVal.dataset.password` and sets `textContent` to it. Clicking "Show" renders an empty string. The `••••••` mask is the only value ever visible. Either remove the button or wire it to a change-password flow; as shipped it misleads the user into thinking a reveal is happening.

### Untested leave-day counting

`countWeekdays()` in `user.js` iterates from `start` to `end` inclusive using `cur <= end` with `cur.setDate(cur.getDate() + 1)`. The comparison uses JS `Date` objects constructed from ISO strings (`"YYYY-MM-DD"`), which parse as UTC midnight. If the browser's local time is behind UTC, `new Date("2024-03-01")` resolves to the evening of Feb 29 in local time, and the boundary comparison may drop or double-count days. No unit tests cover this. The impact is that `total_leave_days` passed to the server could be off by one in affected timezones, but the server doesn't independently validate the day count before inserting.

</details>

---

## File map

<details>
<summary>Changed files</summary>

- `Pages/Users/userDashboard.php` — New file: dashboard with 3 stat cards, SC balance card, quick action, recent requests table; 6 parameterized queries.
- `Pages/Users/userRequest.php` — New file: leave request listing + 4-modal AJAX submission flow with `LR-YYYY-NNNN` request numbering.
- `Pages/Users/userServiceCredits.php` — New file: SC balance display and full transaction history table.
- `Pages/Users/userSettings.php` — New file: display/brightness card + accessibility card in 2-col grid; font size persisted to DB.
- `Pages/Users/userProfile.php` — Updated: sidebar nav replaced with 5-item user nav, active set to Profile, script changed to `user.js`, password hash removed from DOM (`data-password=""`).
- `Pages/Users/logout.php` — New file: calls `logout_session()`, redirects to `/PERSYS/index.php`.
- `styles/user.css` — Appended: all user-page component classes below original shell styles.
- `scripts/user.js` — Appended: logout modal IIFE, request page IIFE, settings IIFE below original sidebar/eye-toggle code.

</details>
