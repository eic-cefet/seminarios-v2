<x-mail::message>
# Resumo da sua apresentação

Olá, **{{ $speakerName }}**!

Sua apresentação aconteceu há dois dias. Aqui vai um resumo rápido:

<x-mail::panel>
**{{ $seminar->name }}**

@if($seminar->scheduled_at)
**Realizado em:** {{ $seminar->scheduled_at->format('d/m/Y H:i') }}<br>
@endif
**Presença confirmada:** {{ $attendeesPresent }} pessoa{{ $attendeesPresent === 1 ? '' : 's' }}
</x-mail::panel>

@if($attendeesPresent === 0)
> Nenhuma presença foi marcada para este seminário. Se houve confirmações que ainda não foram registradas, faça-o pela página do seminário para que os presentes recebam o certificado.
@endif

<x-mail::button :url="url('/seminario/' . $seminar->slug)">
Ver seminário
</x-mail::button>

Obrigado por contribuir,<br>
{{ config('mail.team_name') }}
</x-mail::message>
