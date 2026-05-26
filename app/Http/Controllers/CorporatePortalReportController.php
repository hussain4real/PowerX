<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildCorporatePortal;
use App\Http\Requests\ExportCorporatePortalReportRequest;
use App\Models\Team;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CorporatePortalReportController extends Controller
{
    public function csv(
        ExportCorporatePortalReportRequest $request,
        Team $currentTeam,
        BuildCorporatePortal $buildCorporatePortal,
    ): StreamedResponse {
        $report = $buildCorporatePortal->report($request->user(), $currentTeam);

        abort_unless($report['available'], 403);

        return response()->streamDownload(
            fn () => $this->writeCsv($report),
            $this->filename($currentTeam),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * @param  array{available: bool, title: string, team: string, scope: string, generatedAt: string, gate: string, sections: array<int, array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}>}  $report
     */
    private function writeCsv(array $report): void
    {
        $handle = fopen('php://output', 'w');

        fputcsv($handle, $this->sanitizeCsvRow([$report['title']]));
        fputcsv($handle, $this->sanitizeCsvRow(['Team', $report['team']]));
        fputcsv($handle, $this->sanitizeCsvRow(['Company scope', $report['scope']]));
        fputcsv($handle, $this->sanitizeCsvRow(['Generated at', $report['generatedAt']]));
        fputcsv($handle, $this->sanitizeCsvRow(['Release gate', $report['gate']]));

        foreach ($report['sections'] as $section) {
            fputcsv($handle, []);
            fputcsv($handle, $this->sanitizeCsvRow([$section['title']]));
            fputcsv($handle, $this->sanitizeCsvRow([$section['description']]));
            fputcsv($handle, $this->sanitizeCsvRow($section['columns']));

            foreach ($section['rows'] as $row) {
                fputcsv($handle, $this->sanitizeCsvRow(array_values($row)));
            }
        }

        fclose($handle);
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    private function sanitizeCsvRow(array $row): array
    {
        return array_map(fn (mixed $value): mixed => $this->sanitizeCsvCell($value), $row);
    }

    private function sanitizeCsvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^\s*[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }

    private function filename(Team $team): string
    {
        return Str::slug($team->name).'-corporate-portal-report.csv';
    }
}
