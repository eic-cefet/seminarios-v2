<x-mail::message>
# Bem-vindo(a) ao {{ config('mail.name') }}!

Olá, **{{ $userName }}**!

Sua conta foi criada com sucesso. Agora você pode:

- Consultar a programação de apresentações e workshops
- Se inscrever em eventos de seu interesse
- Receber certificados de participação
- Avaliar as apresentações que participou

<x-mail::button :url="$loginUrl">
Acessar o Sistema
</x-mail::button>

Esperamos você nos próximos eventos!

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
