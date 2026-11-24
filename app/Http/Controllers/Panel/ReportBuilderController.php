<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\Acl;
use App\Support\Panel\ReportRegistry;
use App\Support\Panel\ResourceRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Report builder: pick a data source, drag its columns (including related
 * model columns) into a report, preview it and export it to CSV / Excel. It
 * reuses the ResourceRegistry definitions so every module — including the
 * generated ones — becomes reportable without extra wiring.
 */
class ReportBuilderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeReports($request);

        return view('panel.report-builder', [
            'sources' => ReportRegistry::sources(),
        ]);
    }

    /**
     * Run the composed report and return the rows as JSON for the preview.
     */
    public function run(Request $request): JsonResponse
    {
        $this->authorizeReports($request);

        [$definition, $fields] = $this->resolve($request);

        $limit = (int) $request->input('limit', 100);
        $limit = max(1, min($limit, 2000));

        $rows = $this->query($definition)->limit($limit)->get();

        $records = $rows->map(fn (Model $row): array => array_map(
            fn (array $field): string => $this->render($field, $row),
            $fields,
        ))->all();

        return response()->json([
            'columns' => array_map(fn (array $field): array => [
                'key' => $field['key'],
                'title' => $field['title'],
            ], $fields),
            'rows' => $records,
            'total' => count($records),
        ]);
    }

    /**
     * Stream the report as CSV or XLSX, without a row limit.
     */
    public function export(Request $request, string $format)
    {
        $this->authorizeReports($request);

        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        [$definition, $fields] = $this->resolve($request);

        $records = [array_map(fn (array $field): string => $field['title'], $fields)];

        foreach ($this->query($definition)->cursor() as $row) {
            $records[] = array_map(fn (array $field): string => $this->plain($field, $row), $fields);
        }

        $filename = Str::slug($definition['title']).'-report-'.now()->format('Ymd-His').'.'.$format;

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($records): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                foreach ($records as $record) {
                    fputcsv($out, $record);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->streamDownload(function () use ($records): void {
            $writer = new XlsxWriter();
            $writer->openToFile('php://output');
            foreach ($records as $record) {
                $writer->addRow(Row::fromValues($record));
            }
            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /* ---------------------------------------------------------------------
     | Helpers
     | ------------------------------------------------------------------- */

    /**
     * Validate the request, resolve the source definition and the ordered
     * list of selected fields (key + title + value callback).
     *
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    protected function resolve(Request $request): array
    {
        $data = $request->validate([
            'source' => ['required', 'string'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string'],
        ]);

        abort_unless(ResourceRegistry::has($data['source']), 422);

        $definition = ResourceRegistry::find($data['source']);

        Gate::authorize('viewAny', $definition['model']);

        $titles = collect(ReportRegistry::catalog($definition))->keyBy('key');
        $fields = [];

        foreach (array_unique($data['columns']) as $key) {
            $column = ReportRegistry::column($definition, $key);

            if ($column === null) {
                continue;
            }

            $fields[] = [
                'key' => $key,
                'title' => $titles->get($key)['title'] ?? $column['title'] ?? $key,
                'value' => $column['value'],
            ];
        }

        abort_if($fields === [], 422);

        return [$definition, $fields];
    }

    /**
     * The base query of a definition with its default ordering applied.
     */
    protected function query(array $definition): Builder
    {
        $query = ($definition['query'])();

        if (! empty($definition['default_sort'])) {
            [$column, $direction] = $definition['default_sort'];
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Rendered cell value (HTML allowed, mirrors the DataTables listing).
     */
    protected function render(array $field, Model $row): string
    {
        return (string) ($field['value'])($row);
    }

    /**
     * Plain-text cell value for the exported files.
     */
    protected function plain(array $field, Model $row): string
    {
        $value = html_entity_decode(strip_tags($this->render($field, $row)), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Only super admins or users holding `manage_report` may build reports.
     */
    protected function authorizeReports(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && ($user->hasRole(Acl::SUPER_ADMIN_ROLE) || $user->checkPermissionTo('manage_report')),
            403,
        );
    }
}
