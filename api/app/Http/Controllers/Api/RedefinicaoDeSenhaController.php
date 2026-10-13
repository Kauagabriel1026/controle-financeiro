<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailDeRedefinicao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class RedefinicaoDeSenhaController extends Controller
{
    /**
     * Envia o link de redefinição. A resposta é a mesma com ou sem cadastro,
     * para não revelar quais e-mails existem no sistema.
     */
    public function enviarLink(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($dados, function (User $user, string $token) {
            // Contas de visitante não têm e-mail de verdade.
            if ($user->demonstracao) {
                return;
            }

            try {
                EmailDeRedefinicao::enviar($user, $token);
            } catch (Throwable $falha) {
                // A falha fica registrada para análise; a resposta não muda.
                report($falha);
            }
        });

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você vai receber um link para criar uma nova senha. Confira também a caixa de spam.',
        ]);
    }

    public function redefinir(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($dados, function (User $user, string $senha) {
            $user->forceFill(['password' => $senha])->save();

            // Quem estava logado com a senha antiga, em qualquer aparelho, sai.
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => ['Este link é inválido ou já expirou. Peça um novo.'],
            ]);
        }

        return response()->json(['message' => 'Senha alterada. Entre com a nova senha.']);
    }
}
