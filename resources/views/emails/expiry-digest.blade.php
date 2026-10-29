<div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;font-size:14px">
    <p>موارد فعالی که تا <b>{{ $days }}</b> روز آینده منقضی می‌شوند:</p>

    @foreach ($sections as $section)
        @if (count($section['rows']))
            <h3>{{ $section['title'] }} ({{ count($section['rows']) }})</h3>
            <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">
                <thead style="background:#f0f0f0">
                    <tr>
                        <th>نوع</th>
                        <th>نام</th>
                        <th>تاریخ انقضا</th>
                        <th>روز باقی‌مانده</th>
                        <th>مشتری</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['rows'] as $r)
                        <tr>
                            <td>{{ $r['type'] === 'domain' ? 'دامین' : 'هاست' }}</td>
                            <td dir="ltr" style="text-align:left">{{ $r['name'] }}</td>
                            <td>{{ $r['expires_on'] }}<br>{{ $r['expires_jalali'] }}</td>
                            <td>{{ $r['days_left'] >= 0 ? $r['days_left'] : abs($r['days_left']) . ' روز گذشته' }}</td>
                            <td>
                                @if (array_key_exists('client_email', $r))
                                    {{ trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? '')) ?: ($r['companyname'] ?? ('#' . $r['userid'])) }}<br>
                                    {{ $r['client_email'] }}<br>
                                    {{ $r['client_phone'] }}
                                @else
                                    شناسه مشتری: {{ $r['userid'] }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</div>
