<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarPerfilRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function update(SalvarPerfilRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill($request->validated())->save();

        return response()->json($user->dadosParaApi());
    }

    /** Troca a senha e encerra as outras sessões; a atual continua aberta. */
    public function trocarSenha(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'senha_atual' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($dados['senha_atual'], $user->password)) {
            throw ValidationException::withMessages([
                'senha_atual' => ['A senha atual não confere.'],
            ]);
        }

        $user->forceFill(['password' => $dados['password']])->save();
        $user->tokens()->whereKeyNot($user->currentAccessToken()->getKey())->delete();

        return response()->json(['message' => 'Senha alterada. As outras sessões foram encerradas.']);
    }
}
