<x-mail::message>
# Inscrição confirmada!

Olá, **{{ $userName }}**!

Sua inscrição no seminário abaixo foi confirmada:

<x-mail::panel>
**{{ $seminar->name }}**

@if($seminar->scheduled_at)
**Data:** {{ $seminar->scheduled_at->format('d/m/Y') }}<br>
**Horário:** {{ $seminar->scheduled_at->format('H:i') }} (horário de Brasília)
@endif
@if($seminar->seminarLocation)
<br>**Local:** {{ $seminar->seminarLocation->name }}
@endif
@if($seminar->room_link)
<br>**Link:** {{ $seminar->room_link }}
@endif
</x-mail::panel>

<x-mail::button :url="url('/seminario/' . $seminar->slug)">
Ver detalhes do seminário
</x-mail::button>

Você receberá lembretes antes do evento. Caso não possa comparecer, cancele sua inscrição na página do seminário.

Até logo,<br>
{{ config('mail.name') }}
</x-mail::message>
