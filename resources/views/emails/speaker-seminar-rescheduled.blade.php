<x-mail::message>
# Sua apresentação foi remarcada

Olá, **{{ $speakerName }}**!

A apresentação abaixo foi remarcada. Por favor, confirme sua disponibilidade no novo horário.

<x-mail::panel>
**{{ $seminar->name }}**

**Data anterior:** {{ $previousStartsAt->format('d/m/Y H:i') }}<br>
**Nova data:** {{ $seminar->scheduled_at->format('d/m/Y H:i') }} (horário de Brasília)
@if($seminar->seminarLocation)
<br>**Local:** {{ $seminar->seminarLocation->name }}
@endif
</x-mail::panel>

<x-mail::button :url="url('/seminario/' . $seminar->slug)">
Ver detalhes
</x-mail::button>

Em caso de conflito, entre em contato com a coordenação.<br>
{{ config('mail.name') }}
</x-mail::message>
