<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #1a2e20; }
  h1 { color: #1a3a2a; font-size: 20px; }
  h2 { color: #1a3a2a; font-size: 15px; margin-top: 24px; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  td { padding: 6px 10px; border: 1px solid #e0e0e0; vertical-align: top; font-size: 13px; }
  td:first-child { background: #f5f5f0; font-weight: bold; width: 35%; }
</style></head>
<body>
<h1>New Membership Application</h1>
<p>Submitted: {{ $submission->submitted_at->format('d M Y H:i') }} UTC</p>
<p>IP: {{ $submission->ip_address }}</p>

@foreach($form as $section)
  @php
    $maxRepeat = $submission->answers->where('repeat_index', '>', 0)->pluck('repeat_index')->max() ?? 0;
    $count = $section->is_repeatable ? $maxRepeat + 1 : 1;
  @endphp
  @for($r = 0; $r < $count; $r++)
    <h2>{{ $section->title_en }}{{ $count > 1 ? ' ' . ($r + 1) : '' }}</h2>
    <table>
      @foreach($section->fields as $field)
        @php $answer = $submission->answerFor($field->id, $r); @endphp
        @if($field->field_type === 'file')
          @php $uploads = $submission->uploadsFor($field->id, $r); @endphp
          <tr>
            <td>{{ $field->label_en }}</td>
            <td>@foreach($uploads as $u)<a href="{{ $u->url() }}">{{ $u->file_name }}</a> @endforeach</td>
          </tr>
        @elseif($field->field_type !== 'declaration')
          <tr>
            <td>{{ $field->label_en }}</td>
            <td>{{ $answer ?? '—' }}</td>
          </tr>
        @endif
      @endforeach
    </table>
  @endfor
@endforeach

<p style="margin-top:24px;font-size:12px;color:#888;">
  View in admin: <a href="{{ url('/admin/submissions/' . $submission->id) }}">Submission #{{ $submission->id }}</a>
</p>
</body>
</html>
