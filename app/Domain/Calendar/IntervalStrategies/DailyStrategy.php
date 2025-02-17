<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\CarbonInterface;

class DailyStrategy extends AbstractIntervalStrategy
{
    public function __construct(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        int $repeatEvery = 1,
    ) {
        parent::__construct($startDate, $eventEnd, $repeatEvery);
    }

    /**
     * @return ScheduleInterval
     */
    public function getInterval(): ScheduleInterval
    {
        return ScheduleInterval::DAILY;
    }

    /**
     * @return null
     */
    public function getOccurrence(): null
    {
        return null;
    }

    /**
     * @param CarbonInterface $date
     *
     * @return bool
     */
    public function isScheduledOnDate(CarbonInterface $date): bool
    {
        $diffInDays = $this->startDate->diffInDays($date);
        $isOnDay = $diffInDays % $this->repeatEvery === 0;

        return $this->isActiveOnDate($date) && $isOnDay;
    }

    /**
     * Returns date of the next closest occurrence
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface|null
     */
    public function getNextOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        $nextDate = $date->clone()->addDay();

        if ($this->repeatEvery > 1) {
            $daysDiff = $this->startDate->diffInDays($nextDate) % $this->repeatEvery;

            if ($daysDiff > 0) {
                $nextDate = $nextDate->addDays($this->repeatEvery - $daysDiff);
            }
        }

        return $this->isScheduledOnDate($nextDate) ? $nextDate : $this->getFirstOccurrenceDate($date);
    }

    /**
     * Returns date of the previous closest occurrence
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface|null
     */
    public function getPrevOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        $prevDate = $date->clone()->subDay();

        if ($this->repeatEvery > 1) {
            $daysDiff = $this->startDate->diffInDays($prevDate) % $this->repeatEvery;

            if ($daysDiff > 0) {
                $prevDate = $prevDate->subDays($daysDiff);
            }
        }

        return $this->isScheduledOnDate($prevDate) ? $prevDate : $this->getLastOccurrenceDate($date);
    }

    protected function getLastOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        $thresholdDate = $this->getThresholdDate();

        return is_null($thresholdDate) || $date->lessThan($thresholdDate)
            ? null
            : $thresholdDate;
    }

    protected function getEndAfterOccurrencesEndDate(): CarbonInterface|null
    {
        $occurrences = $this->getMaxOccurrences();

        if ($occurrences === null) {
            return null;
        }

        return $this->startDate->clone()->addDays($occurrences * $this->repeatEvery - 1);
    }
}
