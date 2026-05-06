@extends('print.layout')

@section('title', 'AC-VMIS Training Requirements')

@section('meta')
    <div>Asian College Varsity Management Information System</div>
    <div>Team: {{ $team['team_name'] }}</div>
    <div>Sport: {{ $team['sport'] }}</div>
    <div>Schedule: {{ $schedule['title'] }}</div>
    <div>Schedule Time: {{ $schedule['start'] }} to {{ $schedule['end'] }}</div>
    <div>Coach: {{ $coachName }}</div>
    <div>Printed: {{ $printedAt }}</div>
@endsection

@section('content')
    <div class="section-title">Training Requirements</div>
    <table>
        <thead>
            <tr>
                <th>Student-Athlete</th>
                <th>Category</th>
                <th>Title</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requirements as $requirement)
                <tr>
                    <td>{{ $requirement['student_name'] }}</td>
                    <td>{{ $requirement['category'] }}</td>
                    <td>{{ $requirement['title'] }}</td>
                    <td>{{ $requirement['description'] ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">No training requirements have been assigned to this schedule.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
