/**
 * students.js — Full CRUD with AJAX, live search, pagination, sorting
 * Smart Student Management System
 */

'use strict';

/* ═══════════════════════════════════════════════════════
   STATE
════════════════════════════════════════════════════════ */
const State = {
    page:      1,
    limit:     10,
    total:     0,
    pages:     1,
    search:    '',
    course:    '',
    status:    '',
    days:      0,
    sortCol:   'id',
    sortDir:   'DESC',
    deleteId:  null,
    editMode:  false,
};

/* ═══════════════════════════════════════════════════════
   DOM REFS
════════════════════════════════════════════════════════ */
const $  = id => document.getElementById(id);
const D  = {
    body:         $('studentsBody'),
    count:        $('recordCount'),
    prevPage:     $('prevPage'),
    nextPage:     $('nextPage'),
    pageInfo:     $('pageInfo'),
    search:       $('searchInput'),
    clearSearch:  $('clearSearch'),
    course:       $('filterCourse'),
    status:       $('filterStatus'),
    days:         $('filterDate'),
    globalSearch: $('globalSearch'),

    // Modal - add/edit
    overlay:      $('studentModalOverlay'),
    modal:        $('studentModal'),
    modalTitle:   $('modalTitle'),
    form:         $('studentForm'),
    studentId:    $('studentId'),
    formAction:   $('formAction'),
    fName:        $('fName'),
    fEmail:       $('fEmail'),
    fCourse:      $('fCourse'),
    fStatus:      $('fStatus'),
    submitBtn:    $('submitBtn'),
    submitText:   $('submitText'),
    submitSpinner:$('submitSpinner'),

    nameError:    $('nameError'),
    emailError:   $('emailError'),
    courseError:  $('courseError'),

    // Delete modal
    delOverlay:   $('deleteModalOverlay'),
    delName:      $('deleteStudentName'),
    confirmDel:   $('confirmDelete'),

    exportBtn:    $('exportBtn'),
    addBtn:       $('addStudentBtn'),
};

/* ═══════════════════════════════════════════════════════
   FETCH STUDENTS (AJAX)
════════════════════════════════════════════════════════ */
async function fetchStudents() {
    D.body.innerHTML = `<tr><td colspan="7" class="table-loading"><span class="spinner"></span> Loading records…</td></tr>`;

    const params = new URLSearchParams({
        action:  'fetch',
        page:    State.page,
        limit:   State.limit,
        search:  State.search,
        course:  State.course,
        status:  State.status,
        days:    State.days,
        sort:    State.sortCol,
        dir:     State.sortDir,
    });

    try {
        const res  = await fetch(`process.php?${params}`);
        const data = await res.json();

        if (!data.success && data.students === undefined) {
            D.body.innerHTML = `<tr><td colspan="7" class="table-empty">Failed to load data. Please try again.</td></tr>`;
            return;
        }

        State.total = data.total;
        State.pages = data.pages;

        renderTable(data.students || []);
        renderPagination();
        updateNavCount();
    } catch (err) {
        D.body.innerHTML = `<tr><td colspan="7" class="table-empty">Network error. Please refresh.</td></tr>`;
    }
}

/* ─── Render table rows ─────────────────────────────── */
function renderTable(students) {
    if (!students.length) {
        D.body.innerHTML = `<tr><td colspan="7" class="table-empty">
            <i class="fas fa-users-slash" style="font-size:32px;color:var(--border);display:block;margin-bottom:10px;"></i>
            No students found matching your filters.
        </td></tr>`;
        D.count.textContent = '0 records';
        return;
    }

    D.count.textContent = `${State.total.toLocaleString()} record${State.total !== 1 ? 's' : ''}`;

    const offset = (State.page - 1) * State.limit;
    D.body.innerHTML = students.map((s, i) => {
        const initials  = s.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
        const avatarBg  = AVATAR_COLORS[hashStr(s.name) % AVATAR_COLORS.length];
        const statusBadge = s.status === 'Active'
            ? '<span class="badge badge-active">Active</span>'
            : '<span class="badge badge-inactive">Inactive</span>';
        const date = new Date(s.created_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });

        return `
        <tr data-id="${s.id}">
            <td>${offset + i + 1}</td>
            <td>
                <div class="td-name">
                    <div class="student-avatar" style="background:${avatarBg}">${initials}</div>
                    <div>
                        <strong>${escHtml(s.name)}</strong>
                    </div>
                </div>
            </td>
            <td class="td-email">${escHtml(s.email)}</td>
            <td>${escHtml(s.course)}</td>
            <td>${statusBadge}</td>
            <td>${date}</td>
            <td>
                <div class="td-actions">
                    <button class="btn-icon edit" title="Edit" onclick="openEditModal(${s.id})">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button class="btn-icon del" title="Delete" onclick="openDeleteModal(${s.id}, '${escHtml(s.name).replace(/'/g,"\\'")}')">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

/* ─── Pagination ────────────────────────────────────── */
function renderPagination() {
    D.pageInfo.textContent = `Page ${State.page} of ${State.pages}`;
    D.prevPage.disabled    = State.page <= 1;
    D.nextPage.disabled    = State.page >= State.pages;
}

/* ─── Sorting ───────────────────────────────────────── */
document.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', () => {
        const col = th.dataset.col;
        if (State.sortCol === col) {
            State.sortDir = State.sortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            State.sortCol = col;
            State.sortDir = 'ASC';
        }
        // Update header classes
        document.querySelectorAll('th.sortable').forEach(t => t.classList.remove('asc','desc'));
        th.classList.add(State.sortDir.toLowerCase());
        State.page = 1;
        fetchStudents();
    });
});

/* ═══════════════════════════════════════════════════════
   FILTERS & SEARCH
════════════════════════════════════════════════════════ */
let searchTimer;

D.search?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const val = D.search.value.trim();
    D.clearSearch.style.display = val ? 'block' : 'none';
    searchTimer = setTimeout(() => {
        State.search = val;
        State.page   = 1;
        fetchStudents();
    }, 350);
});

D.clearSearch?.addEventListener('click', () => {
    D.search.value            = '';
    D.clearSearch.style.display = 'none';
    State.search              = '';
    State.page                = 1;
    fetchStudents();
    D.search.focus();
});

D.course?.addEventListener('change', () => { State.course = D.course.value; State.page = 1; fetchStudents(); });
D.status?.addEventListener('change', () => { State.status = D.status.value; State.page = 1; fetchStudents(); });
D.days?.addEventListener('change',   () => { State.days   = parseInt(D.days.value) || 0; State.page = 1; fetchStudents(); });

// Global topbar search
D.globalSearch?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const val = D.globalSearch.value.trim();
    searchTimer = setTimeout(() => {
        D.search.value = val;
        State.search   = val;
        State.page     = 1;
        fetchStudents();
    }, 350);
});

/* ═══════════════════════════════════════════════════════
   PAGINATION BUTTONS
════════════════════════════════════════════════════════ */
D.prevPage?.addEventListener('click', () => { if (State.page > 1)           { State.page--; fetchStudents(); } });
D.nextPage?.addEventListener('click', () => { if (State.page < State.pages) { State.page++; fetchStudents(); } });

/* ═══════════════════════════════════════════════════════
   ADD MODAL
════════════════════════════════════════════════════════ */
D.addBtn?.addEventListener('click', openAddModal);

function openAddModal() {
    State.editMode = false;
    D.modalTitle.innerHTML  = '<i class="fas fa-user-plus"></i> Add New Student';
    D.formAction.value      = 'add';
    D.studentId.value       = '';
    D.form.reset();
    clearErrors();
    openModal();
}

/* ═══════════════════════════════════════════════════════
   EDIT MODAL
════════════════════════════════════════════════════════ */
async function openEditModal(id) {
    State.editMode = true;

    try {
        const res  = await fetch(`process.php?action=get&id=${id}`);
        const data = await res.json();

        if (!data.success || !data.student) {
            Toast.error('Could not load student data.');
            return;
        }

        const s = data.student;
        D.modalTitle.innerHTML = '<i class="fas fa-pencil-alt"></i> Edit Student';
        D.formAction.value     = 'update';
        D.studentId.value      = s.id;
        D.fName.value          = s.name;
        D.fEmail.value         = s.email;
        D.fCourse.value        = s.course;
        D.fStatus.value        = s.status;

        clearErrors();
        openModal();
    } catch {
        Toast.error('Failed to load student data. Please try again.');
    }
}

function openModal() {
    D.overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => D.fName.focus(), 300);
}

function closeModal() {
    D.overlay.classList.remove('open');
    document.body.style.overflow = '';
}

$('closeModal')?.addEventListener('click', closeModal);
$('cancelModal')?.addEventListener('click', closeModal);

D.overlay?.addEventListener('click', e => { if (e.target === D.overlay) closeModal(); });

/* ═══════════════════════════════════════════════════════
   FORM VALIDATION (Real-time + on submit)
════════════════════════════════════════════════════════ */
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function validateField(field, errorEl, rules) {
    const val = field.value.trim();
    let msg = '';

    for (const rule of rules) {
        const result = rule(val);
        if (result) { msg = result; break; }
    }

    if (msg) {
        field.classList.add('error');
        errorEl.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg}`;
        return false;
    } else {
        field.classList.remove('error');
        errorEl.textContent = '';
        return true;
    }
}

const nameRules   = [v => !v && 'Name is required.', v => v.length < 2 && 'Minimum 2 characters.'];
const emailRules  = [v => !v && 'Email is required.', v => !EMAIL_RE.test(v) && 'Enter a valid email address.'];
const courseRules = [v => !v && 'Course is required.', v => v.length < 2 && 'Minimum 2 characters.'];

// Real-time validation
D.fName?.addEventListener('input',   () => validateField(D.fName,   D.nameError,   nameRules));
D.fEmail?.addEventListener('input',  () => validateField(D.fEmail,  D.emailError,  emailRules));
D.fCourse?.addEventListener('input', () => validateField(D.fCourse, D.courseError, courseRules));

function clearErrors() {
    [D.fName, D.fEmail, D.fCourse].forEach(f => f?.classList.remove('error'));
    [D.nameError, D.emailError, D.courseError].forEach(e => { if(e) e.textContent = ''; });
}

function validateForm() {
    const v1 = validateField(D.fName,   D.nameError,   nameRules);
    const v2 = validateField(D.fEmail,  D.emailError,  emailRules);
    const v3 = validateField(D.fCourse, D.courseError, courseRules);
    return v1 && v2 && v3;
}

/* ═══════════════════════════════════════════════════════
   FORM SUBMIT (AJAX)
════════════════════════════════════════════════════════ */
D.form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    // Loading state
    D.submitText.style.display    = 'none';
    D.submitSpinner.style.display = 'inline-flex';
    D.submitBtn.disabled          = true;

    const formData = new FormData(D.form);

    try {
        const res  = await fetch('process.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            Toast.success(data.message);
            closeModal();
            fetchStudents();
        } else {
            Toast.error(data.message || 'An error occurred.');
        }
    } catch {
        Toast.error('Network error. Please try again.');
    } finally {
        D.submitText.style.display    = '';
        D.submitSpinner.style.display = 'none';
        D.submitBtn.disabled          = false;
    }
});

/* ═══════════════════════════════════════════════════════
   DELETE MODAL
════════════════════════════════════════════════════════ */
function openDeleteModal(id, name) {
    State.deleteId      = id;
    D.delName.textContent = name;
    D.delOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    D.delOverlay.classList.remove('open');
    document.body.style.overflow = '';
    State.deleteId = null;
}

$('closeDeleteModal')?.addEventListener('click', closeDeleteModal);
$('cancelDelete')?.addEventListener('click', closeDeleteModal);
D.delOverlay?.addEventListener('click', e => { if (e.target === D.delOverlay) closeDeleteModal(); });

D.confirmDel?.addEventListener('click', async () => {
    if (!State.deleteId) return;

    D.confirmDel.disabled   = true;
    D.confirmDel.innerHTML  = '<span class="spinner spinner-sm"></span> Deleting…';

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', State.deleteId);

    try {
        const res  = await fetch('process.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            Toast.success(data.message);
            closeDeleteModal();
            // Go back a page if last item on page was deleted
            if (State.page > 1 && ((State.total - 1) <= (State.page - 1) * State.limit)) {
                State.page--;
            }
            fetchStudents();
        } else {
            Toast.error(data.message || 'Delete failed.');
        }
    } catch {
        Toast.error('Network error. Please try again.');
    } finally {
        D.confirmDel.disabled  = false;
        D.confirmDel.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
    }
});

/* ═══════════════════════════════════════════════════════
   EXPORT CSV
════════════════════════════════════════════════════════ */
D.exportBtn?.addEventListener('click', () => {
    const params = new URLSearchParams({
        action: 'export',
        search: State.search,
        course: State.course,
        status: State.status,
        days:   State.days,
    });
    window.location.href = `process.php?${params}`;
    Toast.info('Preparing CSV download…', 3000);
});

/* ═══════════════════════════════════════════════════════
   KEYBOARD SHORTCUTS
════════════════════════════════════════════════════════ */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
    }
    // Ctrl+N = new student
    if ((e.ctrlKey || e.metaKey) && e.key === 'n' && !e.target.matches('input,textarea,select')) {
        e.preventDefault();
        openAddModal();
    }
});

/* ═══════════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════════════ */
const AVATAR_COLORS = [
    '#4F46E5','#06B6D4','#10B981','#F59E0B',
    '#EF4444','#8B5CF6','#EC4899','#F97316',
];

function hashStr(str) {
    let h = 0;
    for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) >>> 0;
    return h;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ═══════════════════════════════════════════════════════
   INIT
════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    fetchStudents();
});
