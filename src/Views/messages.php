<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$action = $_GET['action'] ?? 'inbox';
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
    if ($_POST['action'] === 'send') {
        db()->insert('messages', [
            'sender_id' => $userId,
            'recipient_id' => !empty($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null,
            'subject' => trim($_POST['subject'] ?? ''),
            'body' => trim($_POST['body'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
        ]);
        $_SESSION['success'] = 'Message envoyé';
        header('Location: ?page=messages&action=sent');
        exit;
    }
    if ($_POST['action'] === 'mark_read') {
        db()->update('messages', ['read_at' => date('Y-m-d H:i:s')], 'id = :id AND recipient_id = :uid', ['id' => (int)$_POST['id'], 'uid' => $userId]);
        header('Location: ?page=messages');
        exit;
    }
}

Security::showSuccess();

$users = db()->fetchAll("SELECT id, nom, prenom, role FROM users WHERE actif = 1 ORDER BY nom");

if ($action === 'inbox') {
    $messages = db()->fetchAll(
        "SELECT m.*, u.nom as sender_nom, u.prenom as sender_prenom
         FROM messages m
         JOIN users u ON m.sender_id = u.id
         WHERE m.recipient_id = :uid OR (m.recipient_id IS NULL AND m.sender_id != :uid2)
         ORDER BY m.created_at DESC LIMIT 50",
        ['uid' => $userId, 'uid2' => $userId]
    );
} elseif ($action === 'sent') {
    $messages = db()->fetchAll(
        "SELECT m.*, u.nom as recipient_nom, u.prenom as recipient_prenom
         FROM messages m
         LEFT JOIN users u ON m.recipient_id = u.id
         WHERE m.sender_id = ?
         ORDER BY m.created_at DESC LIMIT 50", [$userId]
    );
}
?>
<div class="messages-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2">
            <a href="?page=messages&action=inbox" class="btn <?= $action === 'inbox' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="bi bi-inbox"></i> Réception</a>
            <a href="?page=messages&action=sent" class="btn <?= $action === 'sent' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="bi bi-send"></i> Envoyés</a>
        </div>
        <button class="btn btn-success" onclick="showCompose()"><i class="bi bi-pencil"></i> Nouveau message</button>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Sujet</th><th><?= $action === 'inbox' ? 'Expéditeur' : 'Destinataire' ?></th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($messages as $m): ?>
                        <tr class="<?= $action === 'inbox' && !$m['read_at'] ? 'fw-bold' : '' ?>">
                            <td>
                                <a href="#" onclick="viewMessage(<?= $m['id'] ?>, `<?= Security::h($m['subject'] ?? '') ?>`, `<?= Security::h($m['body'] ?? '') ?>`, '<?= Security::h(($m['sender_prenom'] ?? $m['recipient_prenom'] ?? '') . ' ' . ($m['sender_nom'] ?? $m['recipient_nom'] ?? '')) ?>')"><?= Security::h($m['subject'] ?? '(Sans sujet)') ?></a>
                            </td>
                            <td><?= Security::h(($m['sender_prenom'] ?? $m['recipient_prenom'] ?? '') . ' ' . ($m['sender_nom'] ?? $m['recipient_nom'] ?? '')) ?></td>
                            <td><small><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></small></td>
                            <td><?php if ($action === 'inbox'): ?><span class="badge bg-<?= $m['read_at'] ? 'secondary' : 'primary' ?>"><?= $m['read_at'] ? 'Lu' : 'Non lu' ?></span><?php endif; ?></td>
                            <td>
                                <?php if ($action === 'inbox' && !$m['read_at']): ?>
                                <form method="POST" style="display:inline;">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="repondre(<?= $m['id'] ?>, `<?= Security::h($m['subject'] ?? '') ?>`)"><i class="bi bi-reply"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun message</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="composeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Nouveau message</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="send">
                    <input type="hidden" name="parent_id" id="composeParentId">
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <select class="form-select" name="recipient_id" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= Security::h($u['prenom'] . ' ' . $u['nom']) ?> (<?= ucfirst($u['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="subject" id="composeSubject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="body" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewSubject"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p><strong>De:</strong> <span id="viewSender"></span></p>
                <hr>
                <p id="viewBody"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script <?= nonce_attr() ?>>
function showCompose() { new bootstrap.Modal(document.getElementById('composeModal')).show(); }
function viewMessage(id, subject, body, sender) {
    document.getElementById('viewSubject').textContent = subject;
    document.getElementById('viewSender').textContent = sender;
    document.getElementById('viewBody').textContent = body;
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}
function repondre(id, subject) {
    document.getElementById('composeParentId').value = id;
    document.getElementById('composeSubject').value = 'Re: ' + subject;
    new bootstrap.Modal(document.getElementById('composeModal')).show();
}
</script>
