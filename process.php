<?php
/**
 * AJAX Process Handler — handles all CRUD + fetch operations
 * All responses are JSON
 */
require_once 'includes/auth_check.php';
header('Content-Type: application/json');

// Only accept POST (GET for fetch/export)
$action = sanitize($_REQUEST['action'] ?? '');
$pdo    = getDB();

try {
    switch ($action) {

        // ──────────────────────────────────────────────────
        // FETCH - paginated, filtered student list
        // ──────────────────────────────────────────────────
        case 'fetch':
            $page    = max(1, (int)($_GET['page']    ?? 1));
            $limit   = max(5, min(50, (int)($_GET['limit'] ?? 10)));
            $search  = sanitize($_GET['search']  ?? '');
            $course  = sanitize($_GET['course']  ?? '');
            $status  = sanitize($_GET['status']  ?? '');
            $days    = (int)($_GET['days'] ?? 0);
            $sortCol = in_array($_GET['sort'] ?? '', ['id','name','email','course','status','created_at'])
                       ? $_GET['sort'] : 'id';
            $sortDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

            $where  = [];
            $params = [];

            if ($search) {
                $where[]  = "(name LIKE :search OR email LIKE :search)";
                $params[':search'] = "%$search%";
            }
            if ($course) {
                $where[]  = "course = :course";
                $params[':course'] = $course;
            }
            if ($status && in_array($status, ['Active', 'Inactive'])) {
                $where[]  = "status = :status";
                $params[':status'] = $status;
            }
            if ($days > 0) {
                $where[]  = "created_at >= NOW() - INTERVAL :days DAY";
                $params[':days'] = $days;
            }

            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            // Count total matching
            $countSQL = "SELECT COUNT(*) FROM students $whereSQL";
            $countStmt = $pdo->prepare($countSQL);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            // Fetch page
            $offset = ($page - 1) * $limit;
            $dataSQL = "SELECT * FROM students $whereSQL ORDER BY $sortCol $sortDir LIMIT :limit OFFSET :offset";
            $dataStmt = $pdo->prepare($dataSQL);
            foreach ($params as $k => $v) $dataStmt->bindValue($k, $v);
            $dataStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
            $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $dataStmt->execute();
            $students = $dataStmt->fetchAll();

            jsonResponse(true, '', [
                'students'   => $students,
                'total'      => $total,
                'page'       => $page,
                'pages'      => max(1, (int)ceil($total / $limit)),
                'limit'      => $limit,
            ]);

        // ──────────────────────────────────────────────────
        // ADD student
        // ──────────────────────────────────────────────────
        case 'add':
            $name   = sanitize($_POST['name']   ?? '');
            $email  = sanitize($_POST['email']  ?? '');
            $course = sanitize($_POST['course'] ?? '');
            $status = sanitize($_POST['status'] ?? 'Active');

            // Validate
            $errors = validateStudent($name, $email, $course, $status);
            if ($errors) jsonResponse(false, implode(' ', $errors));

            // Check duplicate email
            $check = $pdo->prepare("SELECT id FROM students WHERE email = :email");
            $check->execute([':email' => $email]);
            if ($check->fetch()) jsonResponse(false, 'A student with this email already exists.');

            $stmt = $pdo->prepare(
                "INSERT INTO students (name, email, course, status) VALUES (:name, :email, :course, :status)"
            );
            $stmt->execute([':name'=>$name, ':email'=>$email, ':course'=>$course, ':status'=>$status]);
            $newId = $pdo->lastInsertId();

            logActivity('ADD', "Added student: $name ($email)", (int)$newId);
            jsonResponse(true, "Student '$name' added successfully!", ['id' => $newId]);

        // ──────────────────────────────────────────────────
        // UPDATE student
        // ──────────────────────────────────────────────────
        case 'update':
            $id     = (int)($_POST['id'] ?? 0);
            $name   = sanitize($_POST['name']   ?? '');
            $email  = sanitize($_POST['email']  ?? '');
            $course = sanitize($_POST['course'] ?? '');
            $status = sanitize($_POST['status'] ?? 'Active');

            if (!$id) jsonResponse(false, 'Invalid student ID.');
            $errors = validateStudent($name, $email, $course, $status);
            if ($errors) jsonResponse(false, implode(' ', $errors));

            // Check duplicate email (exclude this student)
            $check = $pdo->prepare("SELECT id FROM students WHERE email = :email AND id != :id");
            $check->execute([':email' => $email, ':id' => $id]);
            if ($check->fetch()) jsonResponse(false, 'Another student with this email already exists.');

            $stmt = $pdo->prepare(
                "UPDATE students SET name=:name, email=:email, course=:course, status=:status WHERE id=:id"
            );
            $stmt->execute([':name'=>$name,':email'=>$email,':course'=>$course,':status'=>$status,':id'=>$id]);

            logActivity('UPDATE', "Updated student: $name (ID: $id)", $id);
            jsonResponse(true, "Student '$name' updated successfully!");

        // ──────────────────────────────────────────────────
        // DELETE student
        // ──────────────────────────────────────────────────
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) jsonResponse(false, 'Invalid student ID.');

            $s = $pdo->prepare("SELECT name FROM students WHERE id = :id");
            $s->execute([':id' => $id]);
            $student = $s->fetch();
            if (!$student) jsonResponse(false, 'Student not found.');

            $del = $pdo->prepare("DELETE FROM students WHERE id = :id");
            $del->execute([':id' => $id]);

            logActivity('DELETE', "Deleted student: {$student['name']} (ID: $id)", $id);
            jsonResponse(true, "Student '{$student['name']}' deleted successfully!");

        // ──────────────────────────────────────────────────
        // EXPORT CSV
        // ──────────────────────────────────────────────────
        case 'export':
            $search  = sanitize($_GET['search']  ?? '');
            $course  = sanitize($_GET['course']  ?? '');
            $status  = sanitize($_GET['status']  ?? '');
            $days    = (int)($_GET['days'] ?? 0);

            $where  = [];
            $params = [];
            if ($search) { $where[] = "(name LIKE :search OR email LIKE :search)"; $params[':search'] = "%$search%"; }
            if ($course) { $where[] = "course = :course"; $params[':course'] = $course; }
            if ($status && in_array($status, ['Active','Inactive'])) { $where[] = "status = :status"; $params[':status'] = $status; }
            if ($days > 0) { $where[] = "created_at >= NOW() - INTERVAL :days DAY"; $params[':days'] = $days; }
            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $pdo->prepare("SELECT id,name,email,course,status,created_at FROM students $whereSQL ORDER BY id");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="students_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');

            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, ['ID', 'Name', 'Email', 'Course', 'Status', 'Registered At']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['id'], $row['name'], $row['email'],
                    $row['course'], $row['status'],
                    date('Y-m-d H:i', strtotime($row['created_at']))
                ]);
            }
            fclose($out);
            logActivity('EXPORT', 'Exported student data to CSV');
            exit;

        // ──────────────────────────────────────────────────
        // GET STUDENT (for edit modal pre-fill)
        // ──────────────────────────────────────────────────
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(false, 'Invalid ID.');
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $student = $stmt->fetch();
            if (!$student) jsonResponse(false, 'Student not found.');
            jsonResponse(true, '', ['student' => $student]);

        default:
            jsonResponse(false, 'Unknown action.');
    }

} catch (Exception $e) {
    error_log("Process error: " . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.');
}

// ── Validation helper ──────────────────────────────────
function validateStudent(string $name, string $email, string $course, string $status): array {
    $errors = [];
    if (strlen($name) < 2)  $errors[] = 'Name must be at least 2 characters.';
    if (strlen($name) > 100) $errors[] = 'Name must not exceed 100 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($course) < 2) $errors[] = 'Course must be at least 2 characters.';
    if (!in_array($status, ['Active', 'Inactive'])) $errors[] = 'Invalid status value.';
    return $errors;
}
