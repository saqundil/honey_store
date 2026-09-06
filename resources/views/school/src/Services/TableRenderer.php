<?php

declare(strict_types=1);

namespace App\Services;

final class TableRenderer
{
    public function __construct(private readonly HeaderLayoutCalculator $layout = new HeaderLayoutCalculator()) {}

    private function formatMark(int|float|string $mark): string
    {
        return rtrim(rtrim(number_format((float) $mark, 10, '.', ''), '0'), '.');
    }

    public function render(array $template, array $students = [], array $studentValues = [], bool $editable = false, int $minimumRows = 0): string
    {
        $layout = $this->layout->calculate($template['groups'] ?? [], $template['columns'] ?? []);
        $totalWidth = array_sum(array_map(static fn(array $column): float => max(0.1, (float) ($column['width_mm'] ?? 15)), $layout['columns']));
        ob_start();
        echo '<table class="dynamic-report-table"><colgroup>';
        foreach ($layout['columns'] as $column) {
            $width = max(0.1, (float) ($column['width_mm'] ?? 15));
            echo '<col style="width:' . school_e($this->formatMark(($width / $totalWidth) * 100)) . '%">';
        }
        echo '</colgroup><thead>';
        foreach ($layout['rows'] as $row) {
            if (!$row) continue;
            echo '<tr>';
            foreach ($row as $cell) {
                $column = $cell['column'] ?? null;
                $vertical = $cell['display_direction'] === 'vertical' ? ' vertical-header' : '';
                $total = ($column['type'] ?? null) === 'calculated_total' ? ' total-column' : '';
                $studentName = ($column['type'] ?? null) === 'student_name' ? ' student-name-column' : '';
                $studentNumber = ($column['type'] ?? null) === 'student_number' ? ' student-number-column' : '';
                echo '<th class="' . school_e(trim($cell['kind'] . $vertical . $total . $studentName . $studentNumber)) . '" colspan="' . (int) $cell['colspan'] . '" rowspan="' . (int) $cell['rowspan'] . '">';
                echo '<span><span class="header-label">' . school_e($cell['label']) . '</span>';
                if ($column && $column['max_mark'] !== null && $column['max_mark'] !== '') echo '<small class="header-mark" dir="ltr">(' . school_e($this->formatMark($column['max_mark'])) . ')</small>';
                echo '</span></th>';
            }
            echo '</tr>';
        }
        echo '</thead><tbody>';
        foreach ($students as $index => $student) {
            $values = $studentValues[(int) $student['id']] ?? [];
            echo '<tr data-student-id="' . (int) $student['id'] . '">';
            foreach ($layout['columns'] as $column) {
                $key = $column['column_key'];
                $value = match ($column['type']) {
                    'student_number' => $index + 1,
                    'student_name' => $student['name'],
                    default => $values[$key] ?? '',
                };
                $class = match ($column['type']) {
                    'student_number' => 'student-number-column',
                    'student_name' => 'student-name-column',
                    'calculated_total' => 'total-column',
                    default => '',
                };
                echo '<td' . ($class !== '' ? ' class="' . $class . '"' : '') . ' data-column-key="' . school_e($key) . '">';
                if ($editable && $column['type'] === 'manual_mark') {
                    echo '<input class="mark-input" type="number" min="0" max="' . school_e($column['max_mark']) . '" step="' . school_e($column['step_value'] ?? '0.25') . '" value="' . school_e($value) . '">';
                } elseif ($editable && in_array($column['type'], ['text', 'date'], true)) {
                    echo '<input class="value-input" type="' . ($column['type'] === 'date' ? 'date' : 'text') . '" value="' . school_e($value) . '">';
                } else {
                    echo '<span class="cell-value">' . school_e($value) . '</span>';
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        for ($index = count($students); $index < $minimumRows; $index++) {
            echo '<tr class="empty-report-row" aria-hidden="true">';
            foreach ($layout['columns'] as $column) {
                $value = $column['type'] === 'student_number' ? (string) ($index + 1) : '';
                $class = match ($column['type']) {
                    'student_number' => 'student-number-column',
                    'student_name' => 'student-name-column',
                    'calculated_total' => 'total-column',
                    default => '',
                };
                echo '<td' . ($class !== '' ? ' class="' . $class . '"' : '') . ' data-column-key="' . school_e($column['column_key']) . '"><span class="cell-value">' . school_e($value) . '</span></td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        return (string) ob_get_clean();
    }
}