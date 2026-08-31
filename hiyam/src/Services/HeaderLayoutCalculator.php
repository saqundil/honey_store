<?php

declare(strict_types=1);

namespace App\Services;

final class HeaderLayoutCalculator
{
    public function calculate(array $groups, array $columns): array
    {
        $visible = array_values(array_filter($columns, fn(array $column): bool => (bool) ($column['is_visible'] ?? true)));
        usort($visible, fn(array $a, array $b): int => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        $groupMap = [];
        foreach ($groups as $group) {
            $group['children'] = [];
            $groupMap[(int) $group['id']] = $group;
        }
        $roots = [];
        foreach ($groupMap as $id => &$group) {
            $parentId = $group['parent_id'] ? (int) $group['parent_id'] : null;
            if ($parentId && isset($groupMap[$parentId])) {
                $groupMap[$parentId]['children'][] = &$group;
            } else {
                $roots[] = &$group;
            }
        }
        unset($group);
        $depth = max(1, $this->treeDepth($roots) + 1);
        $rows = array_fill(0, $depth, []);
        $topLevel = [];
        foreach ($roots as $root) {
            $topLevel[] = ['type' => 'group', 'item' => $root, 'order' => $this->minimumOrder($root, $visible)];
        }
        foreach ($visible as $column) {
            if (empty($column['header_group_id'])) {
                $topLevel[] = ['type' => 'column', 'item' => $column, 'order' => (int) ($column['sort_order'] ?? 0)];
            }
        }
        usort($topLevel, fn(array $a, array $b): int => $a['order'] <=> $b['order']);
        foreach ($topLevel as $entry) {
            if ($entry['type'] === 'group') {
                $this->appendGroup($entry['item'], 0, $rows, $visible);
            } else {
                $rows[0][] = $this->columnCell($entry['item'], $depth);
            }
        }
        return ['rows' => $rows, 'columns' => $visible, 'depth' => $depth];
    }

    private function treeDepth(array $groups): int
    {
        $depth = 0;
        foreach ($groups as $group) {
            $depth = max($depth, 1 + $this->treeDepth($group['children'] ?? []));
        }
        return $depth;
    }

    private function appendGroup(array $group, int $level, array &$rows, array $columns): void
    {
        $descendantIds = $this->groupIds($group);
        $groupColumns = array_values(array_filter($columns, fn(array $column): bool => in_array((int) ($column['header_group_id'] ?? 0), $descendantIds, true)));
        if (!$groupColumns) {
            return;
        }
        $rows[$level][] = [
            'kind' => 'group', 'label' => $group['name'], 'colspan' => count($groupColumns), 'rowspan' => 1,
            'display_direction' => $group['display_direction'] ?? 'horizontal',
        ];
        $nextLevel = [];
        foreach ($group['children'] ?? [] as $child) {
            $nextLevel[] = ['type' => 'group', 'item' => $child, 'order' => $this->minimumOrder($child, $columns)];
        }
        foreach ($groupColumns as $column) {
            if ((int) $column['header_group_id'] === (int) $group['id']) {
                $nextLevel[] = ['type' => 'column', 'item' => $column, 'order' => (int) ($column['sort_order'] ?? 0)];
            }
        }
        usort($nextLevel, fn(array $a, array $b): int => $a['order'] <=> $b['order']);
        foreach ($nextLevel as $entry) {
            if ($entry['type'] === 'group') {
                $this->appendGroup($entry['item'], $level + 1, $rows, $columns);
            } else {
                $rows[$level + 1][] = $this->columnCell($entry['item'], count($rows) - $level - 1);
            }
        }
    }

    private function groupIds(array $group): array
    {
        $ids = [(int) $group['id']];
        foreach ($group['children'] ?? [] as $child) {
            $ids = [...$ids, ...$this->groupIds($child)];
        }
        return $ids;
    }

    private function minimumOrder(array $group, array $columns): int
    {
        $ids = $this->groupIds($group);
        $orders = array_map(
            fn(array $column): int => (int) ($column['sort_order'] ?? PHP_INT_MAX),
            array_filter($columns, fn(array $column): bool => in_array((int) ($column['header_group_id'] ?? 0), $ids, true))
        );
        return $orders ? min($orders) : PHP_INT_MAX;
    }

    private function columnCell(array $column, int $rowspan): array
    {
        return [
            'kind' => 'column', 'column' => $column, 'label' => $column['header_label'] ?: $column['name'],
            'colspan' => 1, 'rowspan' => max(1, $rowspan), 'display_direction' => $column['display_direction'] ?? 'horizontal',
        ];
    }
}