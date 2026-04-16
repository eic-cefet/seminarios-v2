<x-mail::message>
# Seu relatório está pronto

Olá!

O relatório **"{{ $reportName }}"** foi gerado com sucesso e está disponível para download.

<x-mail::button :url="$downloadUrl">
Baixar Relatório
</x-mail::button>

<x-mail::panel>
**Atenção:** Este link é válido por apenas **2 horas** a partir do momento da geração. Após esse período, o arquivo será removido automaticamente e o link expirará.
</x-mail::panel>

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
