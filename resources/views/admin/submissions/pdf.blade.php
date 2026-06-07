@php
use App\Support\ArabicReshaper;
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a2e20; margin: 0; padding: 20px; }
  .header { background: #1a3a2a; color: #c5a84a; padding: 16px 20px; margin: -20px -20px 24px; }
  .header h1 { margin: 0; font-size: 16px; }
  .header p { margin: 4px 0 0; font-size: 10px; color: rgba(255,255,255,0.7); }
  h2 { color: #1a3a2a; font-size: 12px; border-bottom: 1px solid #e0d8c8; padding-bottom: 3px; margin: 16px 0 6px; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 4px 8px; border: 1px solid #e0e0e0; font-size: 10px; vertical-align: top; }
  td:first-child { background: #f5f5f0; font-weight: bold; width: 35%; }
  .meta { font-size: 9px; color: #888; margin-bottom: 16px; }
  .ar { direction: rtl; unicode-bidi: bidi-override; text-align: right; }
</style>
</head>
<body>
<div class="header">
    <h1>Syrian Saudi Business Council — Membership Application</h1>
    <p>Submission #{{ $submission->id }} · {{ $submission->submitted_at->format('d M Y H:i') }} UTC</p>
</div>
<p class="meta">Status: {{ ucfirst(str_replace('_',' ',$submission->status)) }} · IP: {{ $submission->ip_address }}</p>

@foreach($sections as $section)
    @php
        $maxRepeat = $submission->answers->where('repeat_index', '>', 0)->pluck('repeat_index')->max() ?? 0;
        $count = $section->is_repeatable ? $maxRepeat + 1 : 1;
    @endphp
    @for($r = 0; $r < $count; $r++)
        <h2>{{ $section->title_en }}{{ $count > 1 ? ' ' . ($r + 1) : '' }}</h2>
        <table>
            @foreach($section->allFields as $field)
                @if($field->field_type === 'declaration') @continue @endif
                @php
                    if ($field->field_type === 'file') {
                        $isAr    = false;
                        $dispVal = null;
                    } else {
                        $rawVal  = $field->formatAnswer($submission->answerFor($field->id, $r));
                        $isAr    = ArabicReshaper::hasArabic($rawVal);
                        $dispVal = $isAr ? ArabicReshaper::reshape($rawVal) : $rawVal;
                    }
                @endphp
                <tr>
                    <td>{{ $field->label_en }}</td>
                    <td @if($isAr)class="ar"@endif>
                        @if($field->field_type === 'file')
                            @php $list = $submission->uploadsFor($field->id, $r); @endphp
                            @if($list->isEmpty())
                                —
                            @else
                                @foreach($list as $u)
                                    {{ $u->file_name }} ({{ round($u->file_size/1024) }} KB)<br>
                                @endforeach
                            @endif
                        @else
                            {{ $dispVal }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endfor
@endforeach

@if($submission->admin_notes)
    <h2>Admin Notes</h2>
    <p>{{ $submission->admin_notes }}</p>
@endif
</body>
</html>
