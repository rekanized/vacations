<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\User;
use App\Support\HolidayCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $currentUser = $this->currentUserFromSession($request);

        abort_if($currentUser === null, 404);

        $requestHistory = $this->requestHistoryFor($currentUser->id);

        return view('profile.show', [
            'currentUser' => $currentUser,
            'layoutCurrentUser' => $currentUser,
            'countries' => HolidayCalendar::supportedCountries(),
            'currentCountryName' => HolidayCalendar::countryName($currentUser->holiday_country),
            'requestHistory' => $requestHistory,
            'requestSummary' => $this->requestSummaryFromHistory($requestHistory),
            'plannerSnapshot' => $this->plannerSnapshotFor($currentUser->id),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $currentUser = $this->currentUserFromSession($request);

        abort_if($currentUser === null, 404);

        $validated = $request->validate([
            'holiday_country' => [
                'required',
                'string',
                'size:2',
                Rule::in(array_keys(HolidayCalendar::supportedCountries())),
            ],
        ]);

        $currentUser->update([
            'holiday_country' => HolidayCalendar::normalizeCountry($validated['holiday_country']),
        ]);

        return redirect()
            ->route('profile.show')
            ->with('status', 'Holiday country updated.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $currentUser = $this->currentUserFromSession($request);

        abort_if($currentUser === null, 404);

        $validated = $request->validate([
            'theme_preference' => [
                'required',
                'string',
                Rule::in(User::supportedThemePreferences()),
            ],
        ]);

        $themePreference = (string) $validated['theme_preference'];

        $currentUser->update([
            'theme_preference' => $themePreference,
        ]);

        return redirect()->back();
    }

    private function currentUserFromSession(Request $request): ?User
    {
        $currentUserId = $request->session()->get('current_user_id');

        if ($currentUserId === null) {
            return null;
        }

        return User::query()
            ->active()
            ->select(['id', 'department_id', 'manager_id', 'name', 'email', 'location', 'holiday_country', 'theme_preference', 'is_admin', 'is_active'])
            ->with([
                'department:id,name',
                'manager:id,department_id,name,location',
                'manager.department:id,name',
            ])
            ->find($currentUserId);
    }

    private function requestHistoryFor(int $userId): Collection
    {
        return Absence::query()
            ->with('approver:id,name')
            ->where('user_id', $userId)
            ->whereNotNull('request_uuid')
            ->orderByDesc('date')
            ->get()
            ->groupBy('request_uuid')
            ->map(fn (Collection $absences) => $this->summarizeRequest($absences))
            ->sortByDesc('date_start')
            ->values();
    }

    private function summarizeRequest(Collection $absences): array
    {
        $orderedAbsences = $absences->sortBy('date')->values();
        $firstAbsence = $orderedAbsences->first();
        $lastAbsence = $orderedAbsences->last();
        $decisionTime = $orderedAbsences
            ->pluck('approved_at')
            ->filter()
            ->sortBy(fn (Carbon $value) => $value->timestamp)
            ->last();
        $approverName = $orderedAbsences
            ->pluck('approver.name')
            ->filter()
            ->last();

        return [
            'request_uuid' => $firstAbsence?->request_uuid,
            'type' => $firstAbsence?->type,
            'reason' => $firstAbsence?->reason,
            'decision_reason' => $firstAbsence?->decision_reason,
            'status' => $firstAbsence?->status,
            'date_start' => $firstAbsence?->date,
            'date_end' => $lastAbsence?->date,
            'date_label' => $this->formatDateLabel($firstAbsence?->date, $lastAbsence?->date),
            'date_count' => $orderedAbsences->count(),
            'submitted_at' => $firstAbsence?->created_at?->format('Y-m-d H:i'),
            'decision_at' => $decisionTime?->format('Y-m-d H:i'),
            'approver_name' => $approverName,
        ];
    }

    private function requestSummaryFromHistory(Collection $requestHistory): array
    {
        return [
            'total' => $requestHistory->count(),
            'pending' => $requestHistory->where('status', Absence::STATUS_PENDING)->count(),
            'approved' => $requestHistory->where('status', Absence::STATUS_APPROVED)->count(),
            'rejected' => $requestHistory->where('status', Absence::STATUS_REJECTED)->count(),
        ];
    }

    private function plannerSnapshotFor(int $userId): array
    {
        $start = Carbon::now()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $monthAbsences = Absence::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $upcomingRequests = Absence::query()
            ->with('approver:id,name')
            ->where('user_id', $userId)
            ->where('date', '>=', Carbon::today()->format('Y-m-d'))
            ->whereIn('status', [Absence::STATUS_PENDING, Absence::STATUS_APPROVED])
            ->whereNotNull('request_uuid')
            ->orderBy('date')
            ->get()
            ->groupBy('request_uuid')
            ->map(fn (Collection $absences) => $this->summarizeRequest($absences))
            ->sortBy('date_start')
            ->values();

        return [
            'period_label' => $start->translatedFormat('F Y'),
            'planned_days' => $monthAbsences->whereIn('status', [Absence::STATUS_PENDING, Absence::STATUS_APPROVED])->count(),
            'approved_days' => $monthAbsences->where('status', Absence::STATUS_APPROVED)->count(),
            'pending_days' => $monthAbsences->where('status', Absence::STATUS_PENDING)->count(),
            'rejected_days' => $monthAbsences->where('status', Absence::STATUS_REJECTED)->count(),
            'next_request' => $upcomingRequests->first(),
            'upcoming_requests' => $upcomingRequests->take(3),
        ];
    }

    private function formatDateLabel(?string $startDate, ?string $endDate): string
    {
        if ($startDate === null || $endDate === null) {
            return 'Unknown dates';
        }

        if ($startDate === $endDate) {
            return $startDate;
        }

        return sprintf('%s to %s', $startDate, $endDate);
    }
}