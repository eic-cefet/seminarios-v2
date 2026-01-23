@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ url(config('mail.logo_path')) }}" class="logo" alt="{{ config('mail.name') }} Logo" style="height: 75px; max-height: 75px; width: auto; margin-top: 15px; margin-bottom: 10px;">
</a>
</td>
</tr>
