@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
$brandColor = config('mail.brand_color', '#354f87');
$buttonColors = [
    'primary' => $brandColor,
    'blue' => $brandColor,
    'success' => '#16a34a',
    'green' => '#16a34a',
    'error' => '#dc2626',
    'red' => '#dc2626',
];
$bgColor = $buttonColors[$color] ?? $brandColor;
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button" target="_blank" rel="noopener" style="background-color: {{ $bgColor }}; border-radius: 4px; color: #fff; display: inline-block; text-decoration: none; padding: 8px 18px;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
