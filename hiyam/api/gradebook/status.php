<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

try {
    $payload = request_json();
    $actorId = current_user_id();
    $repository = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
    $status = (new App\Services\GradebookService(db(), $repository, is_super_admin()))->changeStatus(
        (int) ($payload['class_assessment_id'] ?? 0),
        (string) ($payload['action'] ?? ''),
        $actorId
    );
    json_response(['ok' => true, 'status' => $status]);
} catch (InvalidArgumentException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    json_response(['ok' => false, 'message' => 'تعذر تغيير حالة الاختبار.'], 500);
}
