<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Support\Panel\Html;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Yajra\DataTables\Facades\DataTables;

/**
 * Read-only Audit Trail listing. Every entry is produced automatically by the
 * `Auditable` model trait; this controller only exposes them in the panel and
 * allows exporting the log to CSV/XLSX.
 */
class AuditController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAccess($request);

        if ($request->ajax() || $request->has('draw')) {
            return $this->dataTable($request);
        }

        return view('panel.audit', [
            'columns' => $this->columns(),
            'filters' => $this->filters(),
        ]);
    }

    public function export(Request $request, string $format)
    {
        $this->authorizeAccess($request);

        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $rows = $this->query($request)->latest('id')->get();
        $headers = ['Waktu', 'User', 'Aksi', 'Modul', 'ID', 'Perubahan', 'IP', 'URL'];
        $records = [$headers];

        foreach ($rows as $row) {
            $records[] = [
                $row->created_at?->format('Y-m-d H:i:s'),
                $row->user_name,
                $row->event,
                class_basename((string) $row->auditable_type),
                (string) $row->auditable_id,
                $this->changesText($row),
                $row->ip_address,
                $row->url,
            ];
        }

        $filename = 'audit-trail-'.now()->format('Ymd-His').'.'.$format;

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($records) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                foreach ($records as $record) {
                    fputcsv($out, $record);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->streamDownload(function () use ($records) {
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

    protected function dataTable(Request $request): JsonResponse
    {
        $table = DataTables::eloquent($this->query($request));

        $table->editColumn('created_at', fn (Audit $row) => '<span class="text-gray-500 dark:text-gray-400">'.$row->created_at?->format('d/m/Y H:i:s').'</span>');
        $table->editColumn('user_name', fn (Audit $row) => Html::text($row->user_name));
        $table->addColumn('event', fn (Audit $row) => $this->eventBadge($row->event));
        $table->addColumn('module', fn (Audit $row) => Html::badge($this->moduleLabel($row), 'muted'));
        $table->editColumn('auditable_id', fn (Audit $row) => '<span class="text-gray-500 dark:text-gray-400">#'.(int) $row->auditable_id.'</span>');
        $table->addColumn('changes', fn (Audit $row) => $this->changesHtml($row));
        $table->editColumn('ip_address', fn (Audit $row) => '<span class="font-mono text-xs text-gray-500 dark:text-gray-400">'.e((string) $row->ip_address).'</span>');

        $raw = ['created_at', 'user_name', 'event', 'module', 'auditable_id', 'changes', 'ip_address'];

        if (filled($search = $this->searchTerm($request))) {
            $table->filter(function ($builder) use ($search) {
                $builder->where(function ($inner) use ($search) {
                    foreach (['user_name', 'event', 'auditable_type', 'ip_address', 'url'] as $column) {
                        $inner->orWhere($column, 'like', "%{$search}%");
                    }
                });
            });
        }

        return $table->rawColumns($raw)->toJson();
    }

    protected function query(Request $request): Builder
    {
        $query = Audit::query();
        $filters = (array) $request->input('filters', []);

        if (filled($filters['event'] ?? null)) {
            $query->where('event', $filters['event']);
        }

        if (filled($filters['auditable_type'] ?? null)) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        return $query;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function columns(): array
    {
        return [
            ['data' => 'created_at', 'title' => 'Waktu', 'orderable' => true, 'className' => 'whitespace-nowrap'],
            ['data' => 'user_name', 'title' => 'User', 'orderable' => true],
            ['data' => 'event', 'title' => 'Aksi', 'orderable' => false],
            ['data' => 'module', 'title' => 'Modul', 'orderable' => false],
            ['data' => 'auditable_id', 'title' => 'ID', 'orderable' => false],
            ['data' => 'changes', 'title' => 'Perubahan', 'orderable' => false],
            ['data' => 'ip_address', 'title' => 'IP', 'orderable' => false],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function filters(): array
    {
        return [
            [
                'name' => 'event',
                'label' => 'Aksi',
                'options' => ['created' => 'Dibuat', 'updated' => 'Diperbarui', 'deleted' => 'Dihapus'],
            ],
            [
                'name' => 'auditable_type',
                'label' => 'Modul',
                'options' => $this->moduleOptions(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function moduleOptions(): array
    {
        return Audit::query()
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type', 'auditable_type')
            ->mapWithKeys(fn ($type) => [$type => class_basename((string) $type)])
            ->all();
    }

    protected function moduleLabel(Audit $row): string
    {
        return class_basename((string) $row->auditable_type) ?: '—';
    }

    protected function eventBadge(?string $event): string
    {
        $variant = match ($event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            default => 'muted',
        };

        return Html::badge($event, $variant);
    }

    /**
     * Human readable summary of the changed attributes for the export file.
     */
    protected function changesText(Audit $row): string
    {
        $old = (array) $row->old_values;
        $new = (array) $row->new_values;

        if ($row->event === 'created') {
            return $this->stringify($new);
        }

        if ($row->event === 'deleted') {
            return $this->stringify($old);
        }

        $parts = [];

        foreach ($new as $key => $value) {
            $parts[] = "{$key}: ".$this->stringifyValue($old[$key] ?? null).' → '.$this->stringifyValue($value);
        }

        return implode(', ', $parts);
    }

    /**
     * Compact inline diff rendered inside the DataTable cell.
     */
    protected function changesHtml(Audit $row): string
    {
        $old = (array) $row->old_values;
        $new = (array) $row->new_values;

        if ($row->event === 'created') {
            return $this->keyList(array_keys($new), 'text-emerald-600 dark:text-emerald-400');
        }

        if ($row->event === 'deleted') {
            return $this->keyList(array_keys($old), 'text-rose-600 dark:text-rose-400');
        }

        $chips = [];

        foreach ($new as $key => $value) {
            $chips[] = '<span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs dark:bg-white/10">'
                .'<span class="font-medium text-gray-600 dark:text-gray-300">'.e((string) $key).'</span>'
                .'<span class="text-gray-400">'.e(Str::limit($this->stringifyValue($old[$key] ?? null), 24)).'</span>'
                .'<span class="text-gray-400">→</span>'
                .'<span class="text-brand-600 dark:text-brand-400">'.e(Str::limit($this->stringifyValue($value), 24)).'</span>'
                .'</span>';
        }

        return $chips === [] ? '<span class="text-gray-400">—</span>' : '<span class="flex flex-wrap gap-1">'.implode('', $chips).'</span>';
    }

    /**
     * @param  array<int, int|string>  $keys
     */
    protected function keyList(array $keys, string $color): string
    {
        if ($keys === []) {
            return '<span class="text-gray-400">—</span>';
        }

        $html = '';

        foreach ($keys as $key) {
            $html .= '<span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium '.$color.' dark:bg-white/10">'.e((string) $key).'</span>';
        }

        return '<span class="flex flex-wrap gap-1">'.$html.'</span>';
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function stringify(array $values): string
    {
        $parts = [];

        foreach ($values as $key => $value) {
            $parts[] = "{$key}: ".$this->stringifyValue($value);
        }

        return implode(', ', $parts);
    }

    protected function stringifyValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '∅';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '';
        }

        return (string) $value;
    }

    protected function searchTerm(Request $request): ?string
    {
        $search = $request->input('search');

        if (is_array($search)) {
            $search = $search['value'] ?? null;
        }

        return filled($search) ? (string) $search : null;
    }

    protected function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless($user !== null && $user->can('viewAny', Audit::class), 403);
    }
}
