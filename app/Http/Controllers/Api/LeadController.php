<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Support\LeadScope;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $q = $this->filteredQuery($request)->with(['project', 'source', 'status', 'assignee:id,name,role']);

        return $q->orderByDesc('id')->paginate((int) $request->input('per_page', 15));
    }

    public function export(Request $request)
    {
        $format = strtolower($request->input('format', 'csv'));
        $rows = $this->filteredQuery($request)
            ->with(['project', 'source', 'status', 'assignee:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Lead $lead) => $this->exportRow($lead))
            ->all();

        $filename = 'leads-' . now()->format('Ymd-His');
        if ($format === 'xlsx' || $format === 'xls') {
            return response($this->excelHtml($rows), 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}.xls\"",
            ]);
        }
        if ($format === 'pdf') {
            return response($this->simplePdf($rows), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        }

        return response($this->csv($rows), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ]);
    }

    public function template(Request $request)
    {
        $columns = $this->templateColumns();
        $emptyRow = [array_fill_keys($columns, '')];
        $format = strtolower($request->input('format', 'csv'));

        if ($format === 'xlsx' || $format === 'xls') {
            return response($this->excelHtml($emptyRow), 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="lead-import-template.xls"',
            ]);
        }

        return response($this->csv($emptyRow), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="lead-import-template.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $headers = array_map(fn ($h) => Str::snake(trim((string) $h)), fgetcsv($handle) ?: []);
        $created = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $payload = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') $payload[$header] = $row[$index] ?? null;
            }

            $requestForRow = new Request($payload);
            $requestForRow->setUserResolver(fn () => $request->user());
            try {
                $data = $this->validateLead($requestForRow);
                $data['created_by'] = $request->user()->id;
                $data['status_id'] = $this->resolveStatusId($payload['status'] ?? null, $data['status_id'] ?? null);
                $data['source_id'] = $this->resolveSourceId($payload['source'] ?? null, $data['source_id'] ?? null);
                $lead = Lead::create($data);
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $request->user()->id,
                    'type' => LeadActivity::TYPE_NOTE,
                    'title' => 'Lead imported',
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $created + count($errors) + 2, 'message' => $e->getMessage()];
            }
        }
        fclose($handle);

        return response()->json(['created' => $created, 'errors' => $errors]);
    }

    public function store(Request $request)
    {
        $data = $this->validateLead($request);
        $data['created_by'] = $request->user()->id;
        if (empty($data['status_id'])) {
            $data['status_id'] = LeadStatus::where('slug', 'untouched')->value('id');
        }
        $lead = Lead::create($data);
        LeadActivity::create([
            'lead_id' => $lead->id, 'user_id' => $request->user()->id,
            'type' => LeadActivity::TYPE_NOTE, 'title' => 'Lead created',
        ]);
        return response()->json($lead->load(['project', 'source', 'status', 'assignee']), 201);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->authorizeView($request, $lead);
        return $lead->load(['project', 'source', 'status', 'assignee', 'creator', 'activities.user:id,name']);
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeView($request, $lead);
        $data = $this->validateLead($request, false);
        $lead->update($data);
        return $lead->fresh(['project', 'source', 'status', 'assignee']);
    }

    public function destroy(Request $request, Lead $lead)
    {
        if (! $request->user()->isMaster()) abort(403);
        $lead->delete();
        return response()->noContent();
    }

    public function assign(Request $request, Lead $lead)
    {
        $data = $request->validate(['assigned_to' => 'required|exists:users,id']);
        $lead->update($data);
        LeadActivity::create([
            'lead_id' => $lead->id, 'user_id' => $request->user()->id,
            'type' => LeadActivity::TYPE_ASSIGN, 'title' => 'Lead assigned',
            'meta' => ['assigned_to' => $data['assigned_to']],
        ]);
        return $lead->fresh(['assignee']);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeView($request, $lead);
        $data = $request->validate([
            'status_id' => 'required|exists:lead_statuses,id',
            'remarks'   => 'nullable|string',
        ]);
        $old = $lead->status_id;
        $lead->update(['status_id' => $data['status_id'], 'remarks' => $data['remarks'] ?? $lead->remarks]);
        LeadActivity::create([
            'lead_id' => $lead->id, 'user_id' => $request->user()->id,
            'type' => LeadActivity::TYPE_STATUS, 'title' => 'Status changed',
            'body' => $data['remarks'] ?? null,
            'meta' => ['from' => $old, 'to' => $data['status_id']],
        ]);
        return $lead->fresh(['status']);
    }

    private function authorizeView(Request $request, Lead $lead): void
    {
        $user = $request->user();
        if ($user->isMaster()) return;
        if ($user->isSubMaster()) {
            $ids = $user->teamUserIds();
            if (in_array($lead->assigned_to, $ids, true) || in_array($lead->created_by, $ids, true)) return;
        }
        if ($user->isAgent() && $lead->assigned_to === $user->id) return;
        abort(403);
    }

    private function validateLead(Request $request, bool $strict = true): array
    {
        $rules = [
            'customer_name' => ($strict ? 'required' : 'sometimes') . '|string|max:191',
            'mobile'        => 'nullable|string|max:32',
            'email'         => 'nullable|email|max:191',
            'city'          => 'nullable|string|max:120',
            'project_id'    => 'nullable|exists:projects,id',
            'source_id'     => 'nullable|exists:lead_sources,id',
            'sub_source'    => 'nullable|string|max:191',
            'status_id'     => 'nullable|exists:lead_statuses,id',
            'budget'        => 'nullable|numeric|min:0',
            'remarks'       => 'nullable|string',
            'follow_up_at'  => 'nullable|date',
            'assigned_to'   => 'nullable|exists:users,id',
            'campaign_name' => 'nullable|string|max:191',
            'ad_set_name'   => 'nullable|string|max:191',
            'ad_name'       => 'nullable|string|max:191',
            'form_name'     => 'nullable|string|max:191',
            'custom_fields' => 'nullable|array',
        ];
        return $request->validate($rules);
    }

    private function filteredQuery(Request $request)
    {
        $q = Lead::query();
        LeadScope::apply($q, $request->user());

        if ($s = $request->string('search')->toString()) {
            $q->where(function ($w) use ($s) {
                $w->where('customer_name', 'like', "%$s%")
                  ->orWhere('mobile', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('city', 'like', "%$s%");
            });
        }
        foreach (['status_id', 'source_id', 'project_id', 'assigned_to', 'sub_source'] as $f) {
            if ($v = $request->input($f)) $q->where($f, $v);
        }
        if ($slug = $request->input('status_slug')) {
            $slugs = array_filter(explode(',', $slug));
            $q->whereHas('status', fn ($s) => $s->whereIn('slug', $slugs));
        }
        if ($slug = $request->input('source_slug')) {
            $q->whereHas('source', fn ($s) => $s->where('slug', $slug)->orWhere('channel', $slug));
        }
        if ($source = $request->input('source')) {
            $q->whereHas('source', fn ($s) => $s->where('name', $source));
        }
        if ($request->input('assigned') === 'unassigned') {
            $q->whereNull('assigned_to');
        }
        if ($request->input('due') === 'callbacks') {
            $q->whereNotNull('follow_up_at');
        }

        // Follow-up insight drill-downs (mirror AnalyticsService::followUpInsights)
        if ($followUp = $request->input('follow_up')) {
            $now = now();
            $open = config('analytics.lost_slugs', []);
            $openOnly = fn ($w) => $w->whereDoesntHave('status', fn ($s) => $s->whereIn('slug', $open));
            $q->whereNotNull('follow_up_at');
            match ($followUp) {
                'today'    => $q->whereBetween('follow_up_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]),
                'overdue'  => $openOnly($q->where('follow_up_at', '<', $now->copy()->startOfDay())),
                'upcoming' => $q->where('follow_up_at', '>', $now->copy()->endOfDay()),
                'missed'   => $openOnly($q->whereBetween('follow_up_at', [$now->copy()->subDays(30), $now->copy()->startOfDay()])),
                default    => null,
            };
        }

        // Lead aging drill-downs (days since created_at; mirror AnalyticsService::leadAging)
        if ($request->filled('age_min')) {
            $now = now();
            $closed = array_merge(config('analytics.lost_slugs', []), config('analytics.won_slugs', []));
            $q->whereDoesntHave('status', fn ($s) => $s->whereIn('slug', $closed))
              ->where('created_at', '<=', $now->copy()->subDays((int) $request->input('age_min'))->endOfDay());
            if ($request->filled('age_max')) {
                $q->where('created_at', '>=', $now->copy()->subDays((int) $request->input('age_max'))->startOfDay());
            }
        }

        return $q;
    }

    private function resolveStatusId(?string $status, ?int $fallback): ?int
    {
        if ($fallback) return $fallback;
        if ($status) {
            return LeadStatus::where('slug', Str::slug($status))->orWhere('name', $status)->value('id');
        }
        return LeadStatus::where('slug', 'untouched')->value('id');
    }

    private function resolveSourceId(?string $source, ?int $fallback): ?int
    {
        if ($fallback) return $fallback;
        if (! $source) return null;
        return LeadSource::where('slug', Str::slug($source, '_'))->orWhere('name', $source)->value('id');
    }

    private function templateColumns(): array
    {
        $base = ['customer_name', 'mobile', 'email', 'city', 'source', 'sub_source', 'status', 'budget', 'follow_up_at', 'remarks'];
        $requiredCustom = CustomField::where('required', true)->orderBy('sort_order')->pluck('key')->all();
        return array_values(array_unique(array_merge($base, $requiredCustom)));
    }

    private function exportRow(Lead $lead): array
    {
        return [
            'ID' => $lead->id,
            'Customer' => $lead->customer_name,
            'Mobile' => $lead->mobile,
            'Email' => $lead->email,
            'City' => $lead->city,
            'Project' => $lead->project?->name,
            'Source' => $lead->source?->name,
            'Sub Source' => $lead->sub_source,
            'Status' => $lead->status?->name,
            'Assignee' => $lead->assignee?->name ?? 'Unassigned',
            'Budget' => $lead->budget,
            'Follow Up' => optional($lead->follow_up_at)->toDateTimeString(),
            'Campaign' => $lead->campaign_name,
            'Ad Set' => $lead->ad_set_name,
            'Ad' => $lead->ad_name,
            'Form' => $lead->form_name,
            'Created At' => optional($lead->created_at)->toDateTimeString(),
        ];
    }

    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        $headers = array_keys($rows[0] ?? array_fill_keys($this->templateColumns(), ''));
        fputcsv($handle, $headers);
        foreach ($rows as $row) fputcsv($handle, array_values($row));
        rewind($handle);
        return stream_get_contents($handle);
    }

    private function excelHtml(array $rows): string
    {
        $headers = array_keys($rows[0] ?? []);
        $html = '<table><thead><tr>';
        foreach ($headers as $h) $html .= '<th>' . e($h) . '</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) $html .= '<td>' . e((string) $cell) . '</td>';
            $html .= '</tr>';
        }
        return $html . '</tbody></table>';
    }

    private function simplePdf(array $rows): string
    {
        $lines = ['Lead Export', 'Generated ' . now()->toDateTimeString(), ''];
        foreach ($rows as $row) {
            $lines[] = implode(' | ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($row), $row));
        }
        $text = substr(implode("\n", $lines), 0, 6000);
        $stream = "BT /F1 10 Tf 40 780 Td (" . str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ') Tj T* ('], $text) . ") Tj ET";
        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj",
            "5 0 obj << /Length " . strlen($stream) . " >> stream\n{$stream}\nendstream endobj",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        return $pdf . "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
