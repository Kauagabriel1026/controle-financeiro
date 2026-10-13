<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailDeRedefinicao
{
    /** Envia o link para a pessoa criar uma nova senha. */
    public static function enviar(User $user, string $token): void
    {
        $link = rtrim(config('app.front_url'), '/').'/redefinir-senha?'.http_build_query([
            'token' => $token,
            'email' => $user->email,
        ]);

        $chave = config('services.brevo.chave');

        if (! $chave) {
            // Em produção o link nunca vai para o log: com ele, qualquer um troca a senha.
            app()->isProduction()
                ? Log::warning('E-mail de redefinição não enviado: falta a variável BREVO_API_KEY.')
                : Log::info('Link de redefinição de senha (e-mail não enviado: falta BREVO_API_KEY).', ['link' => $link]);

            return;
        }

        $nome = e($user->name);
        $endereco = e($link);

        Http::withHeaders(['api-key' => $chave])
            ->acceptJson()
            ->timeout(10)
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => config('services.brevo.nome'),
                    'email' => config('services.brevo.remetente'),
                ],
                'to' => [['email' => $user->email, 'name' => $user->name]],
                'subject' => 'Crie uma nova senha no Kamel',
                'htmlContent' => <<<HTML
                    <p>Olá, {$nome}.</p>
                    <p>Recebemos um pedido para criar uma nova senha na sua conta do Kamel Controle Financeiro.</p>
                    <p><a href="{$endereco}">Criar nova senha</a></p>
                    <p>O link vale por 60 minutos e só pode ser usado uma vez. Se você não fez esse pedido, ignore este e-mail: sua senha continua a mesma.</p>
                    HTML,
                'textContent' => "Olá, {$user->name}.\n\nPara criar uma nova senha no Kamel, abra o link abaixo. Ele vale por 60 minutos e só pode ser usado uma vez.\n\n{$link}\n\nSe você não fez esse pedido, ignore este e-mail: sua senha continua a mesma.",
            ])
            ->throw();
    }
}
