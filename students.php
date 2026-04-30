<?php
/**
 * Students Management Page — Full CRUD with AJAX
 */
require_once 'includes/auth_check.php';

// ── Fetch distinct courses for filter dropdown ─────────
$pdo     = getDB();
$courses = $pdo->query("SELECT DISTINCT course FROM students ORDER BY course")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle    = 'Students';
$pageSubtitle = 'Manage all student records';
$extraScript  = '<script src="js/students.js"></script>';

include 'partials/header.php';
?>

<!-- ── TOOLBAR ────────────────────────────────────────── -->
<div class="toolbar">
    <div class="toolbar-left">
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name or email…" autocomplete="off">
            <button id="clearSearch" class="clear-btn" style="display:none">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <select id="filterCourse" class="filter-select">
            <option value="">All Courses</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filterStatus" class="filter-select">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>

        <select id="filterDate" class="filter-select">
            <option value="">All Time</option>
            <option value="7">Last 7 Days</option>
            <option value="30">Last 30 Days</option>
        </select>
    </div>

    <div class="toolbar-right">
        <button class="btn btn-outline" id="exportBtn" title="Export to CSV">
            <i class="fas fa-download"></i> Export CSV
        </button>
        <button class="btn btn-primary" id="addStudentBtn">
            <i class="fas fa-plus"></i> Add Student
        </button>
    </div>
</div>

<!-- ── TABLE CARD ─────────────────────────────────────── -->
<div class="card table-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-table"></i> Student Records</h3>
        <span class="record-count" id="recordCount">Loading…</span>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="studentsTable">
            <thead>
                <tr>
                    <th class="sortable" data-col="id">#</th>
                    <th class="sortable" data-col="name">Name</th>
                    <th class="sortable" data-col="email">Email</th>
                    <th class="sortable" data-col="course">Course</th>
                    <th class="sortable" data-col="status">Status</th>
                    <th class="sortable" data-col="created_at">Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentsBody">
                <tr><td colspan="7" class="table-loading">
                    <span class="spinner"></span> Loading records…
                </td></tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="paginationWrap">
        <button id="prevPage" class="page-btn" disabled>
            <i class="fas fa-chevron-left"></i> Prev
        </button>
        <div class="page-info" id="pageInfo">Page 1 of 1</div>
        <button id="nextPage" class="page-btn">
            Next <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     ADD / EDIT MODAL
════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="studentModalOverlay">
    <div class="modal" id="studentModal">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-user-plus"></i> Add New Student</h2>
            <button class="modal-close" id="closeModal"><i class="fas fa-times"></i></button>
        </div>

        <form id="studentForm" novalidate>
            <input type="hidden" id="studentId" name="id" value="">
            <input type="hidden" name="action" id="formAction" value="add">

            <div class="form-grid">
                <div class="form-group">
                    <label for="fName">Full Name <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" id="fName" name="name" placeholder="e.g. Alice Johnson" maxlength="100">
                    </div>
                    <div class="field-error" id="nameError"></div>
                </div>

                <div class="form-group">
                    <label for="fEmail">Email Address <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="fEmail" name="email" placeholder="student@university.edu" maxlength="150">
                    </div>
                    <div class="field-error" id="emailError"></div>
                </div>

                <div class="form-group">
                    <label for="fCourse">Course <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-book"></i>
                        <input type="text" id="fCourse" name="course" placeholder="e.g. Computer Science" list="courseList" maxlength="100">
                        <datalist id="courseList">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="field-error" id="courseError"></div>
                </div>

                <div class="form-group">
                    <label for="fStatus">Status <span class="req">*</span></label>
                    <div class="input-wrap select-wrap">
                        <i class="fas fa-toggle-on"></i>
                        <select id="fStatus" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="cancelModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span id="submitText"><i class="fas fa-save"></i> Save Student</span>
                    <span id="submitSpinner" style="display:none"><span class="spinner spinner-sm"></span> Saving…</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="modal-overlay" id="deleteModalOverlay">
    <div class="modal modal-sm">
        <div class="modal-header danger">
            <h2><i class="fas fa-trash-alt"></i> Confirm Delete</h2>
            <button class="modal-close" id="closeDeleteModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteStudentName"></strong>?</p>
            <p class="del-warning"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancelDelete">Cancel</button>
            <button class="btn btn-danger" id="confirmDelete">
                <i class="fas fa-trash-alt"></i> Delete
            </button>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
