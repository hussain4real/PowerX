<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildOperationalReport;
use App\Actions\PowerX\RecordAuditEvent;
use App\Http\Requests\ExportOperationalReportRequest;
use App\Models\Team;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Spatie\LaravelPdf\Support\pdf;

class ReportExportController extends Controller
{
    public function csv(
        ExportOperationalReportRequest $request,
        Team $currentTeam,
        BuildOperationalReport $buildOperationalReport,
        RecordAuditEvent $recordAuditEvent,
    ): StreamedResponse {
        $report = $buildOperationalReport->handle($currentTeam);
        $filename = $this->filename($currentTeam, 'csv');

        $this->recordExport($request, $currentTeam, $recordAuditEvent, 'csv', $filename, $report);

        return response()->streamDownload(
            fn () => $this->writeCsv($report),
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function pdf(
        ExportOperationalReportRequest $request,
        Team $currentTeam,
        BuildOperationalReport $buildOperationalReport,
        RecordAuditEvent $recordAuditEvent,
    ): PdfBuilder {
        $report = $buildOperationalReport->handle($currentTeam);
        $filename = $this->filename($currentTeam, 'pdf');

        $this->recordExport($request, $currentTeam, $recordAuditEvent, 'pdf', $filename, $report);

        return pdf('pdf.powerx.operational-report', compact('report'))
            ->format(Format::A4)
            ->margins(top: 12, right: 10, bottom: 14, left: 10, unit: 'mm')
            ->inline($filename);
    }

    /**
     * @param  array{title: string, team: string, generatedAt: string, sections: array<int, array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}>}  $report
     */
    private function writeCsv(array $report): void
    {
        $handle = fopen('php://output', 'w');

        fputcsv($handle, [$report['title']]);
        fputcsv($handle, ['Team', $report['team']]);
        fputcsv($handle, ['Generated at', $report['generatedAt']]);

        foreach ($report['sections'] as $section) {
            fputcsv($handle, []);
            fputcsv($handle, [$section['title']]);
            fputcsv($handle, $section['columns']);

            foreach ($section['rows'] as $row) {
                fputcsv($handle, array_values($row));
            }
        }

        fclose($handle);
    }

    /**
     * @param  array{title: string, team: string, generatedAt: string, sections: array<int, array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}>}  $report
     */
    private function recordExport(
        ExportOperationalReportRequest $request,
        Team $team,
        RecordAuditEvent $recordAuditEvent,
        string $format,
        string $filename,
        array $report,
    ): void {
        $recordAuditEvent->handle(
            action: 'report.exported',
            subject: $team,
            actor: $request->user(),
            team: $team,
            after: [
                'format' => $format,
                'filename' => $filename,
                'sections' => collect($report['sections'])->pluck('key')->all(),
            ],
            metadata: ['route' => $request->route()?->getName()],
            summary: "Operational report exported as {$format}.",
            request: $request,
        );
    }

    private function filename(Team $team, string $extension): string
    {
        return Str::slug($team->name).'-operational-report.'.ltrim($extension, '.');
    }
}
