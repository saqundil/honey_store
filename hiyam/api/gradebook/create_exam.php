<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

/**
 * ينشئ اختبارًا بأقسامه في معاملة واحدة.
 *
 * القسم في الواجهة = صف في assessment_templates يشير إلى إصدار جدول.
 * المعلم يرسل إمّا قسمًا جاهزًا (template_version_id) أو قسمًا جديدًا
 * (اسم + أعمدة)، ولا يعرف شيئًا عن القوالب ولا إصداراتها ولا المخططات.
 *
 * أعمدة الهوية (رقم الطالب واسمه) تُضاف هنا لأن TemplateService يشترطها،
 * فلا تظهر للمعلم ولا يُطالَب بها.
 */

/** @return array{column_key:string,name:string,type:string,max_mark:string,...} */
function identityColumns(): array
{
    return [
        [
            'column_key' => 'student_number', 'name' => 'الرقم', 'header_label' => '',
            'type' => 'student_number', 'max_mark' => '', 'step_value' => 0.25, 'width_mm' => 10,
            'sort_order' => 1, 'is_visible' => true, 'header_group_key' => null,
            'text_direction' => 'rtl', 'display_direction' => 'horizontal', 'formula' => null,
        ],
        [
            'column_key' => 'student_name', 'name' => 'اسم الطالب', 'header_label' => '',
            'type' => 'student_name', 'max_mark' => '', 'step_value' => 0.25, 'width_mm' => 48,
            'sort_order' => 2, 'is_visible' => true, 'header_group_key' => null,
            'text_direction' => 'rtl', 'display_direction' => 'horizontal', 'formula' => null,
        ],
    ];
}

/**
 * يحوّل أعمدة المعلم إلى حمولة TemplateService كاملة.
 * المفاتيح تُولَّد آليًا بالصيغة التي يفرضها TemplateService.
 */
function buildTemplatePayload(string $sectionName, array $columns): array
{
    $payload = identityColumns();
    $order = count($payload);
    $markKeys = [];

    foreach (array_values($columns) as $index => $column) {
        $name = trim((string) ($column['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('كل عمود يحتاج اسمًا.');
        }
        $type = (string) ($column['type'] ?? 'manual_mark');
        if (!in_array($type, ['manual_mark', 'text', 'date', 'calculated_total'], true)) {
            $type = 'manual_mark';
        }
        $key = 'col_' . ($index + 1);
        $maxMark = (string) ($column['max_mark'] ?? '');

        if ($type === 'calculated_total') {
            if (!$markKeys) {
                throw new InvalidArgumentException("عمود «{$name}» مجموع، ويحتاج أعمدة علامات قبله.");
            }
            $payload[] = [
                'column_key' => $key, 'name' => $name, 'header_label' => '', 'type' => 'calculated_total',
                'max_mark' => $maxMark !== '' ? $maxMark : (string) array_sum(array_column($columns, 'max_mark')),
                'step_value' => 0.25, 'width_mm' => 15, 'sort_order' => ++$order, 'is_visible' => true,
                'header_group_key' => null, 'text_direction' => 'rtl', 'display_direction' => 'horizontal',
                'formula' => ['type' => 'SUM', 'sources' => $markKeys, 'missing' => 'blank', 'base' => null, 'divisor' => 1, 'decimals' => 2],
            ];
            continue;
        }

        if ($type === 'manual_mark') {
            if ($maxMark === '' || (float) $maxMark <= 0) {
                throw new InvalidArgumentException("عمود «{$name}» يحتاج علامة قصوى أكبر من صفر.");
            }
            $markKeys[] = $key;
        }

        $payload[] = [
            'column_key' => $key, 'name' => $name, 'header_label' => '', 'type' => $type,
            'max_mark' => $type === 'manual_mark' ? $maxMark : '',
            'step_value' => 0.25, 'width_mm' => 15, 'sort_order' => ++$order, 'is_visible' => true,
            'header_group_key' => null, 'text_direction' => 'rtl', 'display_direction' => 'horizontal',
            'formula' => null,
        ];
    }

    if (count($payload) < 3) {
        throw new InvalidArgumentException("القسم «{$sectionName}» يحتاج عمودًا واحدًا على الأقل.");
    }

    return ['template_id' => 0, 'name' => $sectionName, 'description' => '', 'settings' => [], 'groups' => [], 'columns' => $payload];
}

try {
    $payload = request_json();
    $actorId = current_user_id();
    $classId = (int) ($payload['class_id'] ?? 0);
    $repository = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
    $service = new App\Services\GradebookService(db(), $repository, is_super_admin());

    // المعلم لا يُطالَب بإعداد «مخطط تقييم» قبل أول اختبار: يُحلّ هنا داخليًا.
    // حين يتعذّر الاختيار بأمان تُعاد الخيارات بأسمائها ليقرّر المستخدم.
    $scheme = $service->ensureSchemeForClass(
        $classId,
        $actorId,
        isset($payload['scheme_version_id']) ? (int) $payload['scheme_version_id'] : null
    );
    if ($scheme['status'] === 'choose') {
        json_response(['ok' => false, 'needs_scheme' => true, 'options' => $scheme['options']], 409);
    }

    $requested = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];

    if (!$requested) {
        // المسار القديم: جدول واحد مُمرَّر مباشرة
        $classAssessmentId = $service->createExam(
            $classId,
            (string) ($payload['name'] ?? ''),
            isset($payload['exam_date']) ? (string) $payload['exam_date'] : null,
            (int) ($payload['template_version_id'] ?? 0),
            $actorId
        );
    } else {
        $templates = new App\Services\TemplateService(
            db(),
            new App\Repositories\TemplateRepository(db(), $actorId, is_super_admin())
        );

        db()->beginTransaction();
        try {
            $sections = [];
            foreach ($requested as $section) {
                $label = trim((string) ($section['name'] ?? ''));
                if ($label === '') {
                    throw new InvalidArgumentException('كل قسم يحتاج اسمًا.');
                }

                if (!empty($section['template_version_id'])) {
                    // قسم جاهز يُعاد استخدامه كما هو
                    $sections[] = ['template_version_id' => (int) $section['template_version_id'], 'label' => $label];
                    continue;
                }

                // قسم جديد: يُنشأ قالبه وإصداره الأول عبر TemplateService نفسه
                $templateId = $templates->save(buildTemplatePayload($label, $section['columns'] ?? []), $actorId);
                $statement = db()->prepare('SELECT current_version_id FROM table_templates WHERE id=?');
                $statement->execute([$templateId]);
                $sections[] = ['template_version_id' => (int) $statement->fetchColumn(), 'label' => $label];
            }

            $classAssessmentId = $service->createExam(
                $classId,
                (string) ($payload['name'] ?? ''),
                isset($payload['exam_date']) ? (string) $payload['exam_date'] : null,
                0,
                $actorId,
                $sections
            );
            db()->commit();
        } catch (Throwable $error) {
            if (db()->inTransaction()) db()->rollBack();
            throw $error;
        }
    }

    json_response([
        'ok' => true,
        'class_assessment_id' => $classAssessmentId,
        'entry_url' => url('admin/gradebook/entry.php?id=' . $classAssessmentId),
    ]);
} catch (InvalidArgumentException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    json_response(['ok' => false, 'message' => 'تعذر إنشاء الاختبار.'], 500);
}
