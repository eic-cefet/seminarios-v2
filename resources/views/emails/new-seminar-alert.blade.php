<x-mail::message>
# Novo Seminário Disponível

Olá, **{{ $userName }}**!

Um novo seminário combina com suas preferências de alerta.

<x-mail::panel>
**{{ $seminar->name }}**
@if ($seminar->scheduled_at)

**Quando:** {{ $seminar->scheduled_at->format('d/m/Y') }} às {{ $seminar->scheduled_at->format('H:i') }} (horário de Brasília)
@endif
@if ($seminar->seminarLocation)

**Local:** {{ $seminar->seminarLocation->name }}
@endif
</x-mail::panel>

@if ($seminar->description)
{{ \Illuminate\Support\Str::limit(\App\Support\MarkdownStripper::strip(strip_tags($seminar->description)), 400) }}
@endif

<x-mail::button :url="url('/seminario/' . $seminar->slug)">
Ver Detalhes do Seminário
</x-mail::button>

Você está recebendo este e-mail porque ativou os alertas de novos seminários. Para alterar ou desativar, acesse suas preferências.

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
