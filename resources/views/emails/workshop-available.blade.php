<x-mail::message>
# Novo workshop disponível!

Olá, **{{ $userName }}**!

Um novo workshop foi adicionado ao calendário:

<x-mail::panel>
**{{ $workshop->name }}**
@if($workshop->description)
<br>{{ $workshop->description }}
@endif
</x-mail::panel>

<x-mail::button :url="url('/workshop/' . ($workshop->slug ?? $workshop->id))">
Conhecer o workshop
</x-mail::button>

Você está recebendo este e-mail porque optou por receber comunicados sobre novos workshops. Você pode alterar suas preferências a qualquer momento na sua conta.

Até logo,<br>
{{ config('mail.name') }}
</x-mail::message>
