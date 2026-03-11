<x-mail::message>
# Alteração de Data de Seminário

Olá, **{{ $userName }}**!

Informamos que a data do seminário **{{ $seminar->name }}** foi alterada:

<x-mail::panel>
**Data anterior:** ~~{{ $oldScheduledAt->format('d/m/Y') }} às {{ $oldScheduledAt->format('H:i') }}~~ (horário de Brasília)

**Nova data:** {{ $seminar->scheduled_at->format('d/m/Y') }} às {{ $seminar->scheduled_at->format('H:i') }} (horário de Brasília)
@if($seminar->seminarLocation)

**Local:** {{ $seminar->seminarLocation->name }}
@endif
@if($seminar->room_link)

**Link:** {{ $seminar->room_link }}
@endif
</x-mail::panel>

<x-mail::button :url="url('/seminario/' . ($seminar->slug ?? $seminar->id))">
Ver Detalhes do Seminário
</x-mail::button>

**Anexamos o arquivo .ics atualizado para você atualizar o evento no seu calendário.**

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
