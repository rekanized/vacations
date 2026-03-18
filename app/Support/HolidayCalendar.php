<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class HolidayCalendar
{
    public const DEFAULT_COUNTRY = 'SE';

    public static function supportedCountries(): array
    {
        return [
            'SE' => 'Sweden',
            'DK' => 'Denmark',
            'NO' => 'Norway',
            'FI' => 'Finland',
            'DE' => 'Germany',
            'GB' => 'United Kingdom',
            'US' => 'United States',
        ];
    }

    public static function normalizeCountry(?string $countryCode): string
    {
        $normalized = strtoupper(trim((string) $countryCode));

        return array_key_exists($normalized, self::supportedCountries())
            ? $normalized
            : self::DEFAULT_COUNTRY;
    }

    public static function countryName(?string $countryCode): string
    {
        $normalized = self::normalizeCountry($countryCode);

        return self::supportedCountries()[$normalized];
    }

    public static function forYear(?string $countryCode, int $year): Collection
    {
        $resolvedCountry = self::normalizeCountry($countryCode);

        return match ($resolvedCountry) {
            'DK' => self::danishHolidays($year),
            'NO' => self::norwegianHolidays($year),
            'FI' => self::finnishHolidays($year),
            'DE' => self::germanHolidays($year),
            'GB' => self::ukHolidays($year),
            'US' => self::usHolidays($year),
            default => SwedishHolidayCalendar::forYear($year),
        };
    }

    private static function danishHolidays(int $year): Collection
    {
        $easterSunday = self::easterSunday($year);

        return collect([
            self::holiday($year, 1, 1, 'New Year\'s Day'),
            ['date' => $easterSunday->copy()->subDays(3)->format('Y-m-d'), 'name' => 'Maundy Thursday'],
            ['date' => $easterSunday->copy()->subDays(2)->format('Y-m-d'), 'name' => 'Good Friday'],
            ['date' => $easterSunday->format('Y-m-d'), 'name' => 'Easter Sunday'],
            ['date' => $easterSunday->copy()->addDay()->format('Y-m-d'), 'name' => 'Easter Monday'],
            self::holiday($year, 5, 1, 'Labour Day'),
            ['date' => $easterSunday->copy()->addDays(26)->format('Y-m-d'), 'name' => 'General Prayer Day'],
            ['date' => $easterSunday->copy()->addDays(39)->format('Y-m-d'), 'name' => 'Ascension Day'],
            ['date' => $easterSunday->copy()->addDays(49)->format('Y-m-d'), 'name' => 'Whit Sunday'],
            ['date' => $easterSunday->copy()->addDays(50)->format('Y-m-d'), 'name' => 'Whit Monday'],
            self::holiday($year, 6, 5, 'Constitution Day'),
            self::holiday($year, 12, 24, 'Christmas Eve'),
            self::holiday($year, 12, 25, 'Christmas Day'),
            self::holiday($year, 12, 26, 'Boxing Day'),
            self::holiday($year, 12, 31, 'New Year\'s Eve'),
        ])->keyBy('date');
    }

    private static function norwegianHolidays(int $year): Collection
    {
        $easterSunday = self::easterSunday($year);

        return collect([
            self::holiday($year, 1, 1, 'New Year\'s Day'),
            ['date' => $easterSunday->copy()->subDays(4)->format('Y-m-d'), 'name' => 'Maundy Thursday'],
            ['date' => $easterSunday->copy()->subDays(2)->format('Y-m-d'), 'name' => 'Good Friday'],
            ['date' => $easterSunday->format('Y-m-d'), 'name' => 'Easter Sunday'],
            ['date' => $easterSunday->copy()->addDay()->format('Y-m-d'), 'name' => 'Easter Monday'],
            self::holiday($year, 5, 1, 'Labour Day'),
            self::holiday($year, 5, 17, 'Constitution Day'),
            ['date' => $easterSunday->copy()->addDays(39)->format('Y-m-d'), 'name' => 'Ascension Day'],
            ['date' => $easterSunday->copy()->addDays(49)->format('Y-m-d'), 'name' => 'Whit Sunday'],
            ['date' => $easterSunday->copy()->addDays(50)->format('Y-m-d'), 'name' => 'Whit Monday'],
            self::holiday($year, 12, 25, 'Christmas Day'),
            self::holiday($year, 12, 26, 'Boxing Day'),
        ])->keyBy('date');
    }

    private static function finnishHolidays(int $year): Collection
    {
        $easterSunday = self::easterSunday($year);

        return collect([
            self::holiday($year, 1, 1, 'New Year\'s Day'),
            self::holiday($year, 1, 6, 'Epiphany'),
            ['date' => $easterSunday->copy()->subDays(2)->format('Y-m-d'), 'name' => 'Good Friday'],
            ['date' => $easterSunday->format('Y-m-d'), 'name' => 'Easter Sunday'],
            ['date' => $easterSunday->copy()->addDay()->format('Y-m-d'), 'name' => 'Easter Monday'],
            self::holiday($year, 5, 1, 'May Day'),
            ['date' => $easterSunday->copy()->addDays(39)->format('Y-m-d'), 'name' => 'Ascension Day'],
            ['date' => self::firstWeekdayOnOrAfter(Carbon::createMidnightDate($year, 6, 19), Carbon::FRIDAY)->format('Y-m-d'), 'name' => 'Midsummer Eve'],
            ['date' => self::firstWeekdayOnOrAfter(Carbon::createMidnightDate($year, 6, 20), Carbon::SATURDAY)->format('Y-m-d'), 'name' => 'Midsummer Day'],
            ['date' => self::firstWeekdayOnOrAfter(Carbon::createMidnightDate($year, 10, 31), Carbon::SATURDAY)->format('Y-m-d'), 'name' => 'All Saints\' Day'],
            self::holiday($year, 12, 6, 'Independence Day'),
            self::holiday($year, 12, 24, 'Christmas Eve'),
            self::holiday($year, 12, 25, 'Christmas Day'),
            self::holiday($year, 12, 26, 'Boxing Day'),
        ])->keyBy('date');
    }

    private static function germanHolidays(int $year): Collection
    {
        $easterSunday = self::easterSunday($year);

        return collect([
            self::holiday($year, 1, 1, 'New Year\'s Day'),
            ['date' => $easterSunday->copy()->subDays(2)->format('Y-m-d'), 'name' => 'Good Friday'],
            ['date' => $easterSunday->copy()->addDay()->format('Y-m-d'), 'name' => 'Easter Monday'],
            self::holiday($year, 5, 1, 'Labour Day'),
            ['date' => $easterSunday->copy()->addDays(39)->format('Y-m-d'), 'name' => 'Ascension Day'],
            ['date' => $easterSunday->copy()->addDays(50)->format('Y-m-d'), 'name' => 'Whit Monday'],
            self::holiday($year, 10, 3, 'German Unity Day'),
            self::holiday($year, 12, 25, 'Christmas Day'),
            self::holiday($year, 12, 26, 'Second Day of Christmas'),
        ])->keyBy('date');
    }

    private static function ukHolidays(int $year): Collection
    {
        $easterSunday = self::easterSunday($year);

        return collect([
            self::observedIfWeekend(Carbon::createMidnightDate($year, 1, 1), 'New Year\'s Day'),
            ['date' => $easterSunday->copy()->subDays(2)->format('Y-m-d'), 'name' => 'Good Friday'],
            ['date' => $easterSunday->copy()->addDay()->format('Y-m-d'), 'name' => 'Easter Monday'],
            ['date' => self::firstWeekdayOnOrAfter(Carbon::createMidnightDate($year, 5, 1), Carbon::MONDAY)->format('Y-m-d'), 'name' => 'Early May Bank Holiday'],
            ['date' => self::lastWeekdayInMonth($year, 5, Carbon::MONDAY)->format('Y-m-d'), 'name' => 'Spring Bank Holiday'],
            ['date' => self::lastWeekdayInMonth($year, 8, Carbon::MONDAY)->format('Y-m-d'), 'name' => 'Summer Bank Holiday'],
            self::observedIfWeekend(Carbon::createMidnightDate($year, 12, 25), 'Christmas Day'),
            self::observedIfWeekend(Carbon::createMidnightDate($year, 12, 26), 'Boxing Day'),
        ])->keyBy('date');
    }

    private static function usHolidays(int $year): Collection
    {
        return collect([
            self::observedIfWeekend(Carbon::createMidnightDate($year, 1, 1), 'New Year\'s Day'),
            ['date' => self::nthWeekdayInMonth($year, 1, Carbon::MONDAY, 3)->format('Y-m-d'), 'name' => 'Martin Luther King Jr. Day'],
            ['date' => self::nthWeekdayInMonth($year, 2, Carbon::MONDAY, 3)->format('Y-m-d'), 'name' => 'Presidents\' Day'],
            ['date' => self::lastWeekdayInMonth($year, 5, Carbon::MONDAY)->format('Y-m-d'), 'name' => 'Memorial Day'],
            self::observedIfWeekend(Carbon::createMidnightDate($year, 7, 4), 'Independence Day'),
            ['date' => self::nthWeekdayInMonth($year, 9, Carbon::MONDAY, 1)->format('Y-m-d'), 'name' => 'Labor Day'],
            ['date' => self::nthWeekdayInMonth($year, 11, Carbon::THURSDAY, 4)->format('Y-m-d'), 'name' => 'Thanksgiving Day'],
            self::observedIfWeekend(Carbon::createMidnightDate($year, 12, 25), 'Christmas Day'),
        ])->keyBy('date');
    }

    private static function holiday(int $year, int $month, int $day, string $name): array
    {
        return [
            'date' => Carbon::createMidnightDate($year, $month, $day)->format('Y-m-d'),
            'name' => $name,
        ];
    }

    private static function easterSunday(int $year): Carbon
    {
        return Carbon::createMidnightDate($year, 3, 21)->addDays(easter_days($year));
    }

    private static function firstWeekdayOnOrAfter(Carbon $date, int $weekday): Carbon
    {
        while ($date->dayOfWeek !== $weekday) {
            $date->addDay();
        }

        return $date;
    }

    private static function lastWeekdayInMonth(int $year, int $month, int $weekday): Carbon
    {
        $date = Carbon::createMidnightDate($year, $month, 1)->endOfMonth();

        while ($date->dayOfWeek !== $weekday) {
            $date->subDay();
        }

        return $date;
    }

    private static function nthWeekdayInMonth(int $year, int $month, int $weekday, int $occurrence): Carbon
    {
        $date = self::firstWeekdayOnOrAfter(Carbon::createMidnightDate($year, $month, 1), $weekday);

        return $date->addWeeks($occurrence - 1);
    }

    private static function observedIfWeekend(Carbon $date, string $name): array
    {
        if ($date->isSaturday()) {
            $date->subDay();
        } elseif ($date->isSunday()) {
            $date->addDay();
        }

        return [
            'date' => $date->format('Y-m-d'),
            'name' => $name,
        ];
    }
}