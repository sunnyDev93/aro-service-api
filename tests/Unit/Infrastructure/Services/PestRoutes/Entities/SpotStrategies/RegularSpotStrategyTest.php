<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Entities\SpotStrategies;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\RegularSpotStrategy;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\SpotFactory;

class RegularSpotStrategyTest extends TestCase
{
    private RegularSpotStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new RegularSpotStrategy();
    }

    /**
     * @test
     */
    public function it_returns_proper_spot_type(): void
    {
        $this->assertEquals(SpotType::REGULAR, $this->strategy->getSpotType());
    }

    /**
     * @test
     *
     * @dataProvider windowDataProvider
     */
    public function it_returns_proper_window(Spot $spot, string $expectedWindow): void
    {
        $this->assertEquals($expectedWindow, $this->strategy->getWindow($spot));
    }

    public static function windowDataProvider(): iterable
    {
        yield [
            'spot' => SpotFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(8),
                    Carbon::tomorrow()->hour(8)->minute(29)
                ),
            ]),
            'expectedWindow' => 'AM',
        ];

        yield [
            'spot' => SpotFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(12),
                    Carbon::tomorrow()->hour(12)->minute(29)
                ),
            ]),
            'expectedWindow' => 'AM',
        ];

        yield [
            'spot' => SpotFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(13),
                    Carbon::tomorrow()->hour(13)->minute(29)
                ),
            ]),
            'expectedWindow' => 'AM',
        ];

        yield [
            'spot' => SpotFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(15),
                    Carbon::tomorrow()->hour(15)->minute(29)
                ),
            ]),
            'expectedWindow' => 'PM',
        ];

        yield [
            'spot' => SpotFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(14),
                    Carbon::tomorrow()->hour(14)->minute(29)
                ),
            ]),
            'expectedWindow' => 'PM',
        ];
    }

    /**
     * @test
     */
    public function it_returns_proper_coordinates(): void
    {
        $prevLat = $this->faker->randomFloat(4, 1, 90);
        $prevLng = $this->faker->randomFloat(4, 1, 180);
        $nextLat = $this->faker->randomFloat(4, 1, 90);
        $nextLng = $this->faker->randomFloat(4, 1, 180);

        $spot = SpotFactory::make([
            'previousCoordinates' => new Coordinate($prevLat, $prevLng),
            'nextCoordinates' => new Coordinate($nextLat, $nextLng),
        ]);

        $this->assertEquals($prevLat, $this->strategy->getPreviousCoordinate($spot)->getLatitude());
        $this->assertEquals($prevLng, $this->strategy->getPreviousCoordinate($spot)->getLongitude());
        $this->assertEquals($nextLat, $this->strategy->getNextCoordinate($spot)->getLatitude());
        $this->assertEquals($nextLng, $this->strategy->getNextCoordinate($spot)->getLongitude());
    }

    /**
     * @test
     */
    public function it_returns_is_aro(): void
    {
        $this->assertFalse($this->strategy->isAroSpot());
    }
}
