<table class="panel" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-left: {{ config('mail.brand_color', '#354f87') }} solid 4px; margin: 21px 0;">
<tr>
<td class="panel-content">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="panel-item">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
</table>
