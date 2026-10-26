<?php

use App\Libraries\SawCalculator;
use CodeIgniter\Test\CIUnitTestCase;

final class SawCalculatorTest extends CIUnitTestCase
{
    public function testRanksCostAndBenefitCriteriaAndPreservesViewShape(): void
    {
        $criteria = [
            ['id' => 1, 'code' => 'C1', 'type' => 'cost'],
            ['id' => 2, 'code' => 'C3', 'type' => 'benefit'],
        ];
        $alternatives = [
            ['id' => 10, 'name' => 'A', 'price' => 100, 'distance' => 1.234, 'latitude' => 0, 'longitude' => 0, 'feature_scores' => [2 => 10], 'feature_names' => 'wifi', 'is_full' => 0, 'image' => '["a.jpg"]'],
            ['id' => 20, 'name' => 'B', 'price' => 200, 'distance' => 2, 'latitude' => 1, 'longitude' => 1, 'feature_scores' => [2 => 20], 'feature_names' => 'ac', 'is_full' => 1, 'image' => '[]'],
        ];

        $results = (new SawCalculator())->rank($criteria, $alternatives, ['C1' => 0.6, 'C3' => 0.4]);

        $this->assertSame([10, 20], array_column($results, 'id'));
        $this->assertSame(0.8, $results[0]['final_score']);
        $this->assertSame(0.7, $results[1]['final_score']);
        $this->assertSame(1.23, $results[0]['distance']);
        $this->assertSame(['a.jpg'], $results[0]['images']);
    }

    public function testZeroDenominatorAndEmptyAlternativesDoNotBreakRanking(): void
    {
        $criteria = [['id' => 1, 'code' => 'C3', 'type' => 'benefit']];
        $alternatives = [[
            'id' => 1, 'name' => 'No features', 'price' => 0, 'distance' => 0,
            'latitude' => 0, 'longitude' => 0, 'feature_scores' => [1 => 0],
            'feature_names' => '', 'image' => '[]',
        ]];

        $this->assertSame([], (new SawCalculator())->rank($criteria, [], ['C3' => 1]));
        $this->assertSame(0.0, (new SawCalculator())->rank($criteria, $alternatives, ['C3' => 1])[0]['final_score']);
    }
}
