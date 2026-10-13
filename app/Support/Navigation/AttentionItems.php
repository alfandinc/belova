<?php

namespace App\Support\Navigation;

use App\Models\User;

/**
 * Everything waiting on the user (the topbar bell), taken from the sidebar badges: the same counts, the
 * same role rules, and the same links. Adding a badge to the sidebar adds it here too.
 */
class AttentionItems
{
    private const TONES = ['danger' => 'critical', 'warning' => 'warning', 'info' => 'info'];

    private const SEVERITY = ['critical' => 3, 'warning' => 2, 'info' => 1];

    /** @return array{items: array<int, array>, total: int} most urgent first */
    public static function forUser(?User $user): array
    {
        $items = [];

        foreach (MainNavigation::forUser($user) as $section) {
            foreach ($section['modules'] as $module) {
                foreach ($module['items'] ?? [] as $item) {
                    if (isset($item['heading']) || empty($item['badges'])) {
                        continue;
                    }

                    $reasons = collect($item['badges'])->map(fn ($badge) => [
                        'count' => (int) $badge['count'],
                        'title' => $badge['title'] ?: 'Perlu tindak lanjut',
                        'tone' => self::TONES[$badge['class']] ?? 'info',
                    ]);
                    $tone = $reasons->sortByDesc(fn ($r) => self::SEVERITY[$r['tone']] ?? 0)->first()['tone'];

                    $items[] = [
                        'label' => $item['label'],
                        'module' => $module['label'],
                        'icon' => $item['icon'],
                        'url' => $item['url'],
                        'class' => $item['attrs']['class'] ?? '',
                        'count' => $reasons->sum('count'),
                        'reasons' => $reasons->all(),
                        'tone' => $tone,
                    ];
                }
            }
        }

        $items = collect($items)
            ->sortBy([
                fn ($a, $b) => (self::SEVERITY[$b['tone']] ?? 0) <=> (self::SEVERITY[$a['tone']] ?? 0),
                fn ($a, $b) => $b['count'] <=> $a['count'],
            ])
            ->values();

        return [
            'items' => $items->all(),
            'total' => $items->sum('count'),
        ];
    }
}
