<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CategoriasSugeridas;
use App\Support\ContaDeDemonstracao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Hash de uma senha qualquer, usado quando o e-mail informado não existe. */
    private const HASH_FICTICIO = '$2y$10$kKbypR.c37K6t/2uHnTiIuOCZIZitPb4rdEYlIV9RwVjevVq7ao8e';

    public function registrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($dados);
        CategoriasSugeridas::adicionarPara($user);

        return $this->respostaComToken($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $dados['email'])->first();

        // A senha é conferida mesmo quando o e-mail não existe, para a resposta
        // levar o mesmo tempo nos dois casos e não revelar quem tem cadastro.
        $senhaConfere = Hash::check($dados['password'], $user?->password ?? self::HASH_FICTICIO);

        if (! $user || ! $senhaConfere) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        // Senhas guardadas com outro custo (de antes de uma mudança na
        // configuração) são refeitas com o custo atual no primeiro login.
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $dados['password']])->save();
        }

        return $this->respostaComToken($user);
    }

    /**
     * Cria uma conta de visitante já com dados de exemplo e entra nela,
     * para quem quer conhecer o sistema sem se cadastrar.
     */
    public function demonstracao(): JsonResponse
    {
        ContaDeDemonstracao::apagarAntigas();

        return $this->respostaComToken(ContaDeDemonstracao::criar(), 201);
    }

    public function eu(Request $request): JsonResponse
    {
        return response()->json($request->user()->dadosParaApi());
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function respostaComToken(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'usuario' => $user->dadosParaApi(),
            'token' => $user->createToken('api')->plainTextToken,
        ], $status);
    }
}
