<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamPlayer;
use App\Models\TeamSchedule;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ScheduleCalendarController extends Controller
{
    public function show(Request $request, TeamSchedule $schedule): Response
    {
        $schedule->loadMissing('team.sport');

        abort_unless($this->canAccessSchedule($request->user(), $schedule), 403);

        $filename = $this->buildFilename($schedule);

        return response($this->buildCalendarContent($schedule), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    private function canAccessSchedule(?User $user, TeamSchedule $schedule): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'coach') {
            $coachId = $user->coach?->id;

            if (!$coachId) {
                return false;
            }

            return Team::query()
                ->forCoach($coachId)
                ->whereKey($schedule->team_id)
                ->exists();
        }

        if (in_array($user->role, ['student', 'student-athlete'], true)) {
            $studentId = $user->student?->id;

            if (!$studentId) {
                return false;
            }

            return TeamPlayer::query()
                ->where('team_id', $schedule->team_id)
                ->where('student_id', $studentId)
                ->exists();
        }

        return false;
    }

    private function buildFilename(TeamSchedule $schedule): string
    {
        $date = $schedule->start_time?->format('Y-m-d') ?? now()->format('Y-m-d');
        $slug = Str::slug($schedule->title ?: 'schedule');

        return "{$date}-{$slug}.ics";
    }

    private function buildCalendarContent(TeamSchedule $schedule): string
    {
        $teamName = $schedule->team?->team_name ?: 'AC-VMIS Team';
        $sportName = $schedule->team?->sport?->name;
        $scheduleType = Str::headline((string) $schedule->type);
        $summary = trim($teamName . ' - ' . ($schedule->title ?: 'Schedule'));
        $descriptionLines = array_values(array_filter([
            'Schedule Title: ' . ($schedule->title ?: 'Untitled Schedule'),
            'Team: ' . $teamName,
            $sportName ? 'Sport: ' . $sportName : null,
            $scheduleType !== '' ? 'Schedule Type: ' . $scheduleType : null,
            $schedule->notes ? 'Notes: ' . $schedule->notes : null,
        ]));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AC-VMIS//Schedule Calendar//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $schedule->id . '@ac-vmis',
            'DTSTAMP:' . $this->formatUtc(now()),
            'DTSTART:' . $this->formatUtc($schedule->start_time),
            'DTEND:' . $this->formatUtc($schedule->end_time),
            'SUMMARY:' . $this->escapeIcsText($summary),
            'LOCATION:' . $this->escapeIcsText((string) ($schedule->venue ?: 'TBA')),
            'DESCRIPTION:' . $this->escapeIcsText(implode("\n", $descriptionLines)),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    private function formatUtc(CarbonInterface $value): string
    {
        return $value->copy()->utc()->format('Ymd\THis\Z');
    }

    private function escapeIcsText(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            trim($value)
        );
    }
}
