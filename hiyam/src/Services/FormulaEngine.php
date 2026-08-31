<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class FormulaEngine
{
    public function validate(array $columns): void
    {
        $known = array_column($columns, null, 'column_key');
        $graph = [];
        foreach ($columns as $column) {
            $key = $column['column_key'];
            $sources = $column['formula']['sources'] ?? [];
            foreach ($sources as $source) {
                if (!isset($known[$source])) {
                    throw new InvalidArgumentException("العمود المصدر {$source} غير موجود.");
                }
                if ($source === $key) {
                    throw new InvalidArgumentException('لا يمكن أن يعتمد العمود على نفسه.');
                }
            }
            $graph[$key] = $sources;
        }
        $visiting = $visited = [];
        $walk = function (string $key) use (&$walk, &$visiting, &$visited, $graph): void {
            if (isset($visiting[$key])) {
                throw new InvalidArgumentException('توجد دورة في اعتمادات المعادلات.');
            }
            if (isset($visited[$key])) return;
            $visiting[$key] = true;
            foreach ($graph[$key] ?? [] as $dependency) $walk($dependency);
            unset($visiting[$key]);
            $visited[$key] = true;
        };
        foreach (array_keys($graph) as $key) $walk($key);
    }

    public function calculate(array $columns, array $values): array
    {
        $this->validate($columns);
        $columnMap = array_column($columns, null, 'column_key');
        $resolving = [];
        $resolve = function (string $key) use (&$resolve, &$values, &$resolving, $columnMap): mixed {
            if (array_key_exists($key, $values) && $values[$key] !== '') return $values[$key];
            $column = $columnMap[$key] ?? null;
            if (!$column || empty($column['formula'])) return null;
            if (isset($resolving[$key])) throw new InvalidArgumentException('Circular formula dependency.');
            $resolving[$key] = true;
            $formula = $column['formula'];
            $sourceValues = array_map(fn(string $source): mixed => $resolve($source), $formula['sources'] ?? []);
            $result = $this->apply($formula['type'] ?? 'SUM', $sourceValues, $formula);
            unset($resolving[$key]);
            return $values[$key] = $result;
        };
        foreach ($columnMap as $key => $column) {
            if (!empty($column['formula'])) $resolve($key);
        }
        return $values;
    }

    private function apply(string $type, array $values, array $formula): ?float
    {
        $missingAsZero = ($formula['missing'] ?? 'blank') === 'zero';
        if (!$missingAsZero && in_array(null, $values, true)) return null;
        $numbers = array_map(fn(mixed $value): float => (float) ($value ?? 0), $values);
        $result = match ($type) {
            'SUM' => array_sum($numbers),
            'AVERAGE' => $numbers ? array_sum($numbers) / count($numbers) : 0,
            'PERCENTAGE' => ((float) ($formula['base'] ?? 0)) > 0 ? array_sum($numbers) / (float) $formula['base'] * 100 : 0,
            default => throw new InvalidArgumentException('نوع المعادلة غير مدعوم.'),
        };
        return round($result / $this->divisor($formula), (int) ($formula['decimals'] ?? 2));
    }

    /** قاسم اختياري لرؤوس مثل «المجموع 8/2»؛ غيابه أو صفريّته تعني بلا قسمة. */
    public function divisor(array $formula): float
    {
        $divisor = (float) ($formula['divisor'] ?? 1);
        return $divisor > 0 ? $divisor : 1.0;
    }
}