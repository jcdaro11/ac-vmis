<?php

namespace App\Http\Controllers\Coaches;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Models\TeamSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CoachDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $coach = $request->user()?->coach;

            if (!$coach) {
                return Inertia::render('Coaches/CoachDashboard', $this->emptyPayload());
            }

            $team = Team::with('sport')
                ->forCoach($coach->id)
                ->first();

            if (!$team) {
                return Inertia::render('Coaches/CoachDashboard', $this->emptyPayload());
            }

            $teamId = $team->id;
            $now = Carbon::now(config('app.timezone'));
            $trendStart = $now->copy()->subDays(13)->startOfDay();
            $trendEnd = $now->copy()->endOfDay();

            $rosterTotal = TeamPlayer::where('team_id', $teamId)->count();
            $upcomingSessions = TeamSchedule::query()
                ->where('team_id', $teamId)
                ->where('start_time', '>=', $now)
                ->count();

            $pastSchedules = TeamSchedule::query()
                ->where('team_id', $teamId)
                ->where('end_time', '<', $now)
                ->withCount(['attendances'])
                ->orderByDesc('end_time')
                ->get();

            $attendanceNeedsReview = $pastSchedules->where('attendances_count', 0)->count();
            $nextAttendanceAction = $pastSchedules
                ->first(function ($schedule) {
                    return (int) ($schedule->attendances_count ?? 0) === 0;
                });
            return Inertia::render('Coaches/CoachDashboard', [
                'team' => [
                    'id' => $team->id,
                    'team_name' => $team->team_name,
                    'sport' => $team->sport?->name ?? $team->sport_id ?? 'unknown',
                ],
                'metrics' => [
                    'upcoming_sessions' => $upcomingSessions,
                    'attendance_needs_review' => $attendanceNeedsReview,
                    'roster_total' => $rosterTotal,
                ],
                'actions' => [
                    'attendance_pending_schedule' => $nextAttendanceAction ? [
                        'id' => $nextAttendanceAction->id,
                        'title' => $nextAttendanceAction->title,
                        'type' => $nextAttendanceAction->type,
                        'venue' => $nextAttendanceAction->venue,
                        'end_time' => optional($nextAttendanceAction->end_time)->toIso8601String(),
                    ] : null,
                ],
                'trends' => [
                    'attendance' => $this->attendanceTrend($teamId, $trendStart, $trendEnd),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Coach dashboard load failed.', [
                'user_id' => $request->user()?->id,
                'coach_id' => $request->user()?->coach?->id,
                'message' => $e->getMessage(),
            ]);

            return Inertia::render('Coaches/CoachDashboard', $this->emptyPayload());
        }
    }

    private function emptyPayload(): array
    {
        return [
            'team' => null,
            'metrics' => [
                'upcoming_sessions' => 0,
                'attendance_needs_review' => 0,
                'roster_total' => 0,
            ],
            'actions' => [
                'attendance_pending_schedule' => null,
            ],
            'trends' => [
                'attendance' => [
                    'labels' => [],
                    'series' => [
                        'present' => [],
                        'late' => [],
                        'absent' => [],
                        'excused' => [],
                    ],
                ],
            ],
        ];
    }

    private function attendanceTrend(int $teamId, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('team_schedules as ts')
            ->leftJoin('schedule_attendances as sa', 'sa.schedule_id', '=', 'ts.id')
            ->where('ts.team_id', $teamId)
            ->whereBetween('ts.start_time', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->selectRaw('DATE(ts.start_time) as schedule_date')
            ->selectRaw("SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_count")
            ->selectRaw("SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_count")
            ->selectRaw("SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
            ->selectRaw("SUM(CASE WHEN sa.status = 'excused' THEN 1 ELSE 0 END) as excused_count")
            ->groupByRaw('DATE(ts.start_time)')
            ->orderByRaw('DATE(ts.start_time)')
            ->get()
            ->keyBy('schedule_date');

        $labels = [];
        $series = [
            'present' => [],
            'late' => [],
            'absent' => [],
            'excused' => [],
        ];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('M j');
            $series['present'][] = (int) ($rows[$key]->present_count ?? 0);
            $series['late'][] = (int) ($rows[$key]->late_count ?? 0);
            $series['absent'][] = (int) ($rows[$key]->absent_count ?? 0);
            $series['excused'][] = (int) ($rows[$key]->excused_count ?? 0);
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }
}
