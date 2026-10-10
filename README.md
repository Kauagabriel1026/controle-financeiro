# Controle Financeiro

Sistema de contas a pagar e a receber com fluxo de caixa por período. API em Laravel com PostgreSQL e front-end em React.

> Projeto em desenvolvimento. O andamento está na seção [Etapas](#etapas).

## Tecnologias

- PHP 8.3 e Laravel 13
- PostgreSQL 16
- Docker e Docker Compose
- PHPUnit e GitHub Actions

## Como rodar

Pré-requisitos: [Docker Desktop](https://www.docker.com/products/docker-desktop/) e Git.

```bash
git clone https://github.com/Kauagabriel1026/controle-financeiro.git
cd controle-financeiro
docker compose up --build
```

Na primeira execução o contêiner instala as dependências, gera a chave da aplicação e cria as tabelas no banco. Quando aparecer `Server running on [http://0.0.0.0:8000]`, a API está no ar.

Para conferir, acesse `http://localhost:8000/api/health`. A resposta esperada é:

```json
{ "status": "ok", "banco": "pgsql" }
```

## Comandos úteis

```bash
# Rodar os testes
docker compose exec api php artisan test

# Criar as tabelas do zero
docker compose exec api php artisan migrate:fresh

# Parar tudo
docker compose down
```

## Estrutura

```
├── api/                  # API em Laravel
│   ├── app/              # Models, controllers e regras de negócio
│   ├── database/         # Migrations, factories e seeders
│   ├── routes/api.php    # Rotas da API
│   ├── tests/            # Testes automatizados
│   └── Dockerfile
├── docker-compose.yml    # Sobe a API e o PostgreSQL
└── .github/workflows/    # Testes automáticos a cada push
```

## Etapas

- [x] Base do projeto: Laravel, PostgreSQL, Docker e testes automáticos
- [x] Banco de dados: categorias e contas
- [ ] API de contas a pagar e a receber, com baixa de pagamento
- [ ] Login, com cada usuário vendo só as próprias contas
- [ ] Fluxo de caixa por período
- [ ] Front-end em React
