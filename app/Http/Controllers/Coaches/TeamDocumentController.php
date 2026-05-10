<?php

namespace App\Http\Controllers\Coaches;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\StudentDocument;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamDocumentController extends Controller
{
    public function index(Request $request)
    {
        $coach = $request->user()?->coach;

        if (!$coach) {
            return Inertia::render('Coaches/TeamDocuments', [
                'team' => null,
                'filters' => ['search' => '', 'type' => 'all', 'review_status' => 'all', 'upload_date' => ''],
                'documentTypes' => [],
                'documents' => [
                    'data' => [],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 12, 'total' => 0],
                ],
            ]);
        }

        $team = Team::query()
            ->with([
                'players:id,team_id,student_id',
                'sport:id,name',
            ])
            ->forCoach($coach->id)
            ->first();

        if (!$team) {
            return Inertia::render('Coaches/TeamDocuments', [
                'team' => null,
                'filters' => ['search' => '', 'type' => 'all', 'review_status' => 'all', 'upload_date' => ''],
                'documentTypes' => [],
                'documents' => [
                    'data' => [],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 12, 'total' => 0],
                ],
            ]);
        }

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'type' => trim((string) $request->query('type', 'all')),
            'review_status' => trim((string) $request->query('review_status', 'all')),
            'upload_date' => trim((string) $request->query('upload_date', '')),
        ];

        $studentIds = $team->players->pluck('student_id')->filter()->unique()->values()->all();

        $query = StudentDocument::query()
            ->with([
                'student.user:id,first_name,last_name,email',
                'academicPeriod:id,school_year,term',
                'documentTypeDefinition:id,context,code,label',
            ])
            ->whereIn('student_id', $studentIds)
            ->latest('uploaded_at');

        if ($filters['type'] !== '' && $filters['type'] !== 'all') {
            $query->whereHas('documentTypeDefinition', fn ($typeQuery) => $typeQuery->where('code', $filters['type']));
        }

        if ($filters['review_status'] !== '' && $filters['review_status'] !== 'all') {
            $query->where('review_status', $filters['review_status']);
        }

        if ($filters['upload_date'] !== '') {
            $query->whereDate('uploaded_at', $filters['upload_date']);
        }

        if ($filters['search'] !== '') {
            $term = $filters['search'];
            $query->where(function ($searchQuery) use ($term) {
                $searchQuery->whereHas('student.user', function ($userQuery) use ($term) {
                    $userQuery->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                })->orWhereHas('student', function ($studentQuery) use ($term) {
                    $studentQuery->where('student_id_number', 'like', "%{$term}%")
                        ->orWhere('course_or_strand', 'like', "%{$term}%");
                })->orWhereHas('documentTypeDefinition', function ($typeQuery) use ($term) {
                    $typeQuery->where('label', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            });
        }

        $documents = $query->paginate(12)->withQueryString();

        return Inertia::render('Coaches/TeamDocuments', [
            'team' => [
                'id' => $team->id,
                'name' => $team->team_name,
                'sport' => $team->sport?->name,
            ],
            'filters' => $filters,
            'documentTypes' => DocumentType::query()
                ->orderBy('label')
                ->get(['code', 'label'])
                ->unique('code')
                ->values()
                ->map(fn (DocumentType $type) => [
                    'code' => $type->code,
                    'label' => $type->label,
                ]),
            'documents' => [
                'data' => $documents->through(function (StudentDocument $document) use ($team) {
                    $student = $document->student;

                    return [
                        'id' => $document->id,
                        'student_name' => $student?->full_name ?? 'Unknown student',
                        'student_id_number' => $student?->student_id_number,
                        'team_name' => $team->team_name,
                        'document_label' => $document->documentTypeDefinition?->label ?? 'Document',
                        'review_status' => $document->review_status,
                        'uploaded_at' => optional($document->uploaded_at)->toDateTimeString(),
                        'file_url' => route('files.documents.show', $document->id),
                        'download_url' => route('files.documents.show', ['document' => $document->id, 'download' => 1]),
                        'notes' => $document->notes,
                        'file_name' => basename((string) $document->file_path),
                    ];
                })->items(),
                'meta' => [
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                    'per_page' => $documents->perPage(),
                    'total' => $documents->total(),
                ],
            ],
        ]);
    }
}
