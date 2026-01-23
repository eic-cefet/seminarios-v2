<x-mail::message>
# Certificado de Participação

Olá, **{{ $userName }}**!

Parabéns por sua participação no seminário **"{{ $seminarName }}"**, realizado em {{ $seminarDate }}.

Seu certificado de participação foi gerado com sucesso e está anexado a este e-mail em formato PDF.

**Código do certificado:**
`{{ $certificateCode }}`

Você também pode acessar seu certificado online a qualquer momento clicando no botão abaixo:

<x-mail::button :url="$certificateUrl">
Acessar Certificado
</x-mail::button>

<x-mail::panel>
**Nota:** O link de acesso ao certificado é válido por 5 minutos. Caso expire, você pode acessar novamente através do mesmo botão.
</x-mail::panel>

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
