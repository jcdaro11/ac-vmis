<?php

namespace App\Http\Controllers;

use App\Models\AcademicDocument;
use App\Models\Coach;
use App\Models\DocumentType;
use App\Models\StudentDocument;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileAccessController extends Controller
{
    public function academic(AcademicDocument $document, Request $request)
    {
        return $this->streamDocument($document, $request);
    }

    public function document(StudentDocument $document, Request $request)
    {
        return $this->streamDocument($document, $request);
    }

    private function canAccessStudentFile(StudentDocument $document): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $studentId = (int) $document->student_id;

        if ($user->role === 'admin') {
            return true;
        }

        if (in_array($user->role, ['student', 'student-athlete'], true)) {
            return (int) ($user->student?->id ?? 0) === $studentId;
        }

        if ($user->role === 'coach') {
            $coachId = (int) (Coach::where('user_id', $user->id)->value('id') ?? 0);
            $coachSportId = (int) (Coach::where('user_id', $user->id)->value('sport_id') ?? 0);
            if ($coachId <= 0 || $coachSportId <= 0) {
                return false;
            }

            $hasRosterAccess = Team::query()
                ->forCoach($coachId)
                ->whereHas('players', fn ($q) => $q->where('student_id', $studentId))
                ->exists();

            if ($hasRosterAccess) {
                return true;
            }

            return $document->newQuery()
                ->whereKey($document->id)
                ->where('student_id', $studentId)
                ->whereHas('student', fn ($studentQuery) => $studentQuery
                    ->where('applied_sport_id', $coachSportId))
                ->whereHas('documentTypeDefinition', fn ($typeQuery) => $typeQuery
                    ->where('context', DocumentType::CONTEXT_REGISTRATION))
                ->exists();
        }

        return false;
    }

    private function streamDocument(StudentDocument $document, Request $request)
    {
        abort_unless($this->canAccessStudentFile($document), 403);

        $path = (string) $document->file_path;
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        $disposition = $request->boolean('download')
            ? 'attachment'
            : 'inline';

        return Storage::disk('public')->response($path, basename($path), [
            'Content-Disposition' => $disposition . '; filename="' . basename($path) . '"',
        ]);
    }
}
