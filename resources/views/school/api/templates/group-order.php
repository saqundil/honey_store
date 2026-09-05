<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

try {
    $payload = request_json();
    $groupIds = array_values(array_unique(array_map('intval', $payload['group_ids'] ?? [])));
    if (!$groupIds || in_array(0, $groupIds, true)) {
        throw new InvalidArgumentException('ترتيب المجموعات غير صالح.');
    }

    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $sql = "SELECT id FROM template_groups WHERE id IN ({$placeholders})";
    $params = $groupIds;
    if (!is_super_admin()) {
        $sql .= ' AND created_by=?';
        $params[] = current_user_id();
    }
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $available = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    if (count($available) !== count($groupIds)) {
        http_response_code(403);
        exit('غير مصرح بتغيير ترتيب إحدى المجموعات.');
    }

    db()->beginTransaction();
    try {
        $update = db()->prepare('UPDATE template_groups SET sort_order=? WHERE id=?');
        foreach ($groupIds as $index => $groupId) {
            $update->execute([($index + 1) * 10, $groupId]);
        }
        db()->commit();
    } catch (Throwable $exception) {
        db()->rollBack();
        throw $exception;
    }

    json_response(['ok' => true]);
} catch (InvalidArgumentException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}