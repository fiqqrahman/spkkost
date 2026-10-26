<?php

namespace App\Libraries;

final class SawCalculator
{
    /**
     * @param list<array{id: int|string, code: string, type: string}> $criterias
     * @param list<array<string, mixed>> $alternatives
     * @param array<string, float|int> $weights
     * @return list<array<string, mixed>>
     */
    public function rank(array $criterias, array $alternatives, array $weights): array
    {
        if ($alternatives === []) {
            return [];
        }

        $minMax = [];
        foreach ($criterias as $criteria) {
            $criteriaId = (int) $criteria['id'];
            $values     = [];

            foreach ($alternatives as $alternative) {
                $values[] = $this->rawScore($alternative, $criteria);
            }

            $minMax[$criteriaId] = $values === []
                ? 0.0
                : (($criteria['type'] === 'cost') ? min($values) : max($values));
        }

        $rankings = [];
        foreach ($alternatives as $alternative) {
            $totalScore = 0.0;

            foreach ($criterias as $criteria) {
                $criteriaId = (int) $criteria['id'];
                $denominator = $minMax[$criteriaId] ?? 0.0;
                $score       = $this->rawScore($alternative, $criteria);

                if ($denominator == 0.0 || $score == 0.0) {
                    continue;
                }

                $normalized = ($criteria['type'] === 'cost')
                    ? $denominator / $score
                    : $score / $denominator;
                $totalScore += $normalized * (float) ($weights[$criteria['code']] ?? 0.0);
            }

            $rankings[] = [
                'id'          => $alternative['id'],
                'name'        => $alternative['name'],
                'price'       => $alternative['price'],
                'distance'    => round((float) $alternative['distance'], 2),
                'final_score' => round($totalScore, 4),
                'latitude'    => $alternative['latitude'],
                'longitude'   => $alternative['longitude'],
                'features'    => $alternative['feature_names'],
                'is_full'     => (int) ($alternative['is_full'] ?? 0),
                'images'      => json_decode($alternative['image'] ?? '[]', true),
            ];
        }

        usort($rankings, static fn (array $a, array $b): int => $b['final_score'] <=> $a['final_score']);

        return $rankings;
    }

    /** @param array{id: int|string, code: string, type: string} $criteria */
    private function rawScore(array $alternative, array $criteria): float
    {
        return match ($criteria['code']) {
            'C1' => (float) $alternative['price'],
            'C2' => (float) $alternative['distance'],
            default => (float) ($alternative['feature_scores'][(int) $criteria['id']] ?? 0.0),
        };
    }
}
