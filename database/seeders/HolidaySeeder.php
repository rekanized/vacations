<?php

namespace Database\Seeders;

use App\Support\HolidayCalendar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = Carbon::now()->year;
        foreach (HolidayCalendar::supportedCountries() as $countryCode => $countryName) {
            $holidays = collect(range($currentYear - 5, $currentYear + 5))
                ->flatMap(fn (int $year) => HolidayCalendar::forYear($countryCode, $year))
                ->values();

            foreach ($holidays as $holiday) {
                \App\Models\Holiday::updateOrCreate(
                    [
                        'country_code' => $countryCode,
                        'date' => $holiday['date'],
                    ],
                    [
                        'name' => $holiday['name'],
                    ],
                );
            }
        }
    }
}
