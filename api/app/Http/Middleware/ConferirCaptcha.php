<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Confere com a Cloudflare o token do captcha (Turnstile) enviado pelo front no
 * campo "captcha". Sem a chave secreta configurada, deixa o pedido passar.
 */
class ConferirCaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        $chave = config('services.turnstile.chave_secreta');

        if (! $chave) {
            return $next($request);
        }

        $token = $request->input('captcha');

        if (! is_string($token) || $token === '') {
            $this->recusar('Confirme que você não é um robô.');
        }

        try {
            $resposta = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $chave,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (Throwable $falha) {
            report($falha);
            $this->recusar('Não foi possível confirmar a verificação de segurança. Tente de novo.');
        }

        if ($resposta->json('success') !== true) {
            $this->recusar('A verificação de segurança expirou ou falhou. Tente de novo.');
        }

        return $next($request);
    }

    private function recusar(string $mensagem): never
    {
        throw ValidationException::withMessages(['captcha' => [$mensagem]]);
    }
}
