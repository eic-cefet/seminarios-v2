<x-mail::message>
# Seus dados estão prontos para download

Olá, **{{ $userName }}**!

Sua solicitação de exportação de dados pessoais (LGPD Art. 18, V) foi concluída com sucesso.
O arquivo `.zip` contém todos os dados que mantemos sobre você organizados em formato JSON.

<x-mail::button :url="$downloadUrl">
Baixar meus dados
</x-mail::button>

<x-mail::panel>
**Atenção:** O link de download expira em **{{ $expiresAt }}**. Por segurança, não compartilhe esta URL com outras pessoas.
</x-mail::panel>

Se você não solicitou esta exportação, entre em contato com nosso Encarregado pelo Tratamento de Dados em
[{{ config('lgpd.encarregado.email') }}](mailto:{{ config('lgpd.encarregado.email') }}).

Atenciosamente,<br>
{{ config('mail.team_name') }}

<x-mail::subcopy>
Este é um e-mail automático. Por favor, não responda.
</x-mail::subcopy>
</x-mail::message>
