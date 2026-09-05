<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

try {
    $payload = request_json();
    $templateId = (int) ($payload['id'] ?? 0);
    $name = trim((string) ($payload['name'] ?? ''));

    if ($name === '' || mb_strlen($name) > 190) {
        throw new InvalidArgumentException('اسم القالب مطلوب، وبحد أقصى 190 حرفًا.');
    }

    (new App\Services\AuthorizationService(db()))->requireAccess('template', $templateId, user());
    $statement = db()->prepare('UPDATE table_templates SET name=? WHERE id=?');
    $statement->execute([$name, $templateId]);

    json_response(['ok' => true, 'name' => $name]);
} catch (InvalidArgumentException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}