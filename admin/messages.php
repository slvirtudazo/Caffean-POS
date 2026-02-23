<?php

/**
 * Purge Coffee Shop — Admin Messages  (messages.php)
 * Operations: View (eye), Mark as Read/Unread (envelope), Delete (trash).
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// ── POST Handlers (PRG) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = (int)$_POST['message_id'];

    if ($_POST['action'] === 'mark_read') {
        $stmt = mysqli_prepare($conn, "UPDATE contact_messages SET is_read = 1 WHERE message_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $_SESSION['flash'] = mysqli_stmt_execute($stmt)
            ? ['type' => 'success', 'msg' => "Message #$id marked as read."]
            : ['type' => 'error',   'msg' => 'Error updating message.'];
        mysqli_stmt_close($stmt);
    } elseif ($_POST['action'] === 'mark_unread') {
        $stmt = mysqli_prepare($conn, "UPDATE contact_messages SET is_read = 0 WHERE message_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $_SESSION['flash'] = mysqli_stmt_execute($stmt)
            ? ['type' => 'success', 'msg' => "Message #$id marked as unread."]
            : ['type' => 'error',   'msg' => 'Error updating message.'];
        mysqli_stmt_close($stmt);
    } elseif ($_POST['action'] === 'delete') {
        $stmt = mysqli_prepare($conn, "DELETE FROM contact_messages WHERE message_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $_SESSION['flash'] = mysqli_stmt_execute($stmt)
            ? ['type' => 'success', 'msg' => "Message #$id deleted."]
            : ['type' => 'error',   'msg' => 'Error deleting message.'];
        mysqli_stmt_close($stmt);
    }

    $qs = isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : '';
    header('Location: messages.php' . $qs);
    exit();
}

// ── Flash ─────────────────────────────────────────────────────
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$message = ($flash && $flash['type'] === 'success') ? $flash['msg'] : '';
$error   = ($flash && $flash['type'] === 'error')   ? $flash['msg'] : '';

// ── Filter tabs ───────────────────────────────────────────────
$filter      = $_GET['filter'] ?? 'all';
$where_map   = ['all' => '1=1', 'unread' => 'is_read = 0', 'read' => 'is_read = 1'];
$where_clause = $where_map[in_array($filter, array_keys($where_map)) ? $filter : 'all'];

// ── Tab counts ────────────────────────────────────────────────
$counts = ['all' => 0, 'unread' => 0, 'read' => 0];
$c_res  = mysqli_query($conn, "SELECT is_read, COUNT(*) AS cnt FROM contact_messages GROUP BY is_read");
while ($r = mysqli_fetch_assoc($c_res)) {
    $key = $r['is_read'] ? 'read' : 'unread';
    $counts[$key] = (int)$r['cnt'];
    $counts['all'] += (int)$r['cnt'];
}

// ── Fetch messages ────────────────────────────────────────────
$msgs_result = mysqli_query(
    $conn,
    "SELECT * FROM contact_messages WHERE $where_clause ORDER BY created_at DESC"
);
$total   = mysqli_num_rows($msgs_result);
$msgs_arr = [];
while ($m = mysqli_fetch_assoc($msgs_result)) $msgs_arr[] = $m;

define('BASE_URL', '..');
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/messages.css?v=<?php echo time(); ?>">

<div class="page-header">
    <div class="page-header-text">
        <h1>Messages</h1>
        <p>View and manage contact form submissions from customers</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="flash-success"><i class="fas fa-check-circle"></i><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
<?php endif; ?>

<!-- ── Filter Tabs ────────────────────────────────────────────── -->
<div class="admin-tabs">
    <a href="?filter=all" class="tab <?= $filter === 'all'    ? 'active' : '' ?>">All Messages <span class="tab-count"><?= $counts['all']    ?></span></a>
    <a href="?filter=read" class="tab <?= $filter === 'read'   ? 'active' : '' ?>">Read <span class="tab-count"><?= $counts['read']   ?></span></a>
    <a href="?filter=unread" class="tab <?= $filter === 'unread' ? 'active' : '' ?>">Unread <span class="tab-count"><?= $counts['unread'] ?></span></a>
</div>

<!-- ── Messages Table ─────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <h2><?= ucfirst($filter) ?> Messages</h2>
        <span class="card-count"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <table class="admin-table" id="messagesTable">
        <thead>
            <tr>
                <th data-sort="number">ID</th>
                <th data-sort="text">Name</th>
                <th data-sort="text">Email</th>
                <th data-sort="text">Subject</th>
                <th data-sort="text">Preview</th>
                <th data-sort="status">Status</th>
                <th data-sort="date">Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total === 0): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No messages found</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($msgs_arr as $m): ?>
                    <tr class="<?= !$m['is_read'] ? 'row-unread' : '' ?>">
                        <td class="td-id">#<?= $m['message_id'] ?></td>
                        <td>
                            <?php if (!$m['is_read']): ?>
                                <span class="unread-dot"></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($m['name']) ?>
                        </td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td class="msg-subject"><?= htmlspecialchars($m['subject']) ?></td>
                        <td>
                            <div class="msg-preview"><?= htmlspecialchars($m['message']) ?></div>
                        </td>
                        <td>
                            <?php if ($m['is_read']): ?>
                                <span class="badge badge-completed">Read</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Unread</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                        <td class="td-actions">
                            <!-- View -->
                            <button class="btn-icon btn-icon-view" title="View Message"
                                onclick="viewMessage(<?= $m['message_id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <!-- Mark Read / Unread -->
                            <?php if (!$m['is_read']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="message_id" value="<?= $m['message_id'] ?>">
                                    <button type="submit" class="btn-icon btn-icon-update" title="Mark as Read">
                                        <i class="fas fa-envelope-open"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="mark_unread">
                                    <input type="hidden" name="message_id" value="<?= $m['message_id'] ?>">
                                    <button type="submit" class="btn-icon btn-icon-update" title="Mark as Unread">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <!-- Delete -->
                            <form method="POST" style="display:inline;"
                                onsubmit="return confirm('Delete this message? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="message_id" value="<?= $m['message_id'] ?>">
                                <button type="submit" class="btn-icon btn-icon-delete" title="Delete Message">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ── View Message Modal ─────────────────────────────────────── -->
<div class="modal-overlay" id="viewMessageModal" style="display:none;">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-envelope modal-icon"></i>Message Details</h3>
            <button class="modal-close" onclick="closeModal('viewMessageModal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="view-detail-group">
                <span class="view-label">Message ID</span>
                <span class="view-value" id="vm_id"></span>
            </div>
            <div class="view-detail-group">
                <span class="view-label">From</span>
                <span class="view-value" id="vm_name"></span>
            </div>
            <div class="view-detail-group">
                <span class="view-label">Email</span>
                <span class="view-value" id="vm_email"></span>
            </div>
            <div class="view-detail-group">
                <span class="view-label">Subject</span>
                <span class="view-value" id="vm_subject"></span>
            </div>
            <div class="view-detail-group">
                <span class="view-label">Date</span>
                <span class="view-value" id="vm_date"></span>
            </div>
            <div class="view-detail-group">
                <span class="view-label">Status</span>
                <span class="view-value" id="vm_status"></span>
            </div>
            <div class="view-label view-modal-section-label">Message</div>
            <div class="msg-body-box" id="vm_message"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeModal('viewMessageModal')">Close</button>
        </div>
    </div>
</div>

<script>
    /* ── Pass PHP data to JS ───────────────────────────────────── */
    var msgsData = <?= json_encode($msgs_arr) ?>;
    var msgsMap = {};
    msgsData.forEach(function(m) {
        msgsMap[m.message_id] = m;
    });

    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function viewMessage(id) {
        var m = msgsMap[id];
        if (!m) return;
        document.getElementById('vm_id').textContent = '#' + m.message_id;
        document.getElementById('vm_name').textContent = m.name;
        document.getElementById('vm_email').textContent = m.email;
        document.getElementById('vm_subject').textContent = m.subject;
        document.getElementById('vm_date').textContent = m.created_at;
        document.getElementById('vm_status').textContent = m.is_read == 1 ? 'Read' : 'Unread';
        document.getElementById('vm_message').textContent = m.message;
        openModal('viewMessageModal');
    }

    initSortableTable('messagesTable');
</script>

<?php include 'includes/footer.php'; ?>