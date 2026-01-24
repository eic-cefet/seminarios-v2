<x-mail::message>
# Bug Report: {{ $reportSubject }}

@if($reporterName || $reporterEmail)
**Reportado por:**
@if($reporterName)
- Nome: {{ $reporterName }}
@endif
@if($reporterEmail)
- E-mail: {{ $reporterEmail }}
@endif
@else
**Reportado por:** Anônimo
@endif

---

## Mensagem

{{ $message }}

@if(count($files) > 0)
---

**Arquivos anexados:** {{ count($files) }} arquivo(s)
@endif

<x-mail::subcopy>
Este relatório foi enviado através do formulário de bugs do {{ config('mail.name') }}.
</x-mail::subcopy>
</x-mail::message>
