# Sistema de Reservas — CRUD de Recursos + API JSON

CRUD completo em PHP para a tabela `recurso` do banco `sistema_reservas`,
com upload de imagem, filtros, chaves estrangeiras para `categoria` e
`setor`, e uma API JSON para consumo externo (ex.: aplicativo Android).

Este projeto foi construído em cima do dump `sistema_reservas.sql`
fornecido (incluído na raiz do projeto), respeitando exatamente os nomes
de tabelas e colunas já existentes.

---

## Índice

1. [Requisitos](#requisitos)
2. [Instalação](#instalação)
3. [Estrutura do projeto](#estrutura-do-projeto)
4. [Banco de dados](#banco-de-dados)
5. [CRUD Web](#crud-web)
6. [API JSON](#api-json)
7. [Consumindo no Android](#consumindo-no-android)
8. [Segurança e boas práticas aplicadas](#segurança-e-boas-práticas-aplicadas)
9. [Possíveis próximos passos](#possíveis-próximos-passos)

---

## Requisitos

- PHP 8.0 ou superior, com extensões `pdo_mysql` e `fileinfo` habilitadas
- MySQL 5.7+ ou MariaDB (o dump original foi gerado em MariaDB 10.4)
- Servidor web (Apache, Nginx ou o servidor embutido do PHP)

---

## Instalação

1. **Banco de dados**
   Importe o dump original (que já contém as tabelas `categoria`, `setor`,
   `recurso`, `historico_uso` e `usuario`):
   ```bash
   mysql -u root -p < sistema_reservas.sql
   ```

   Opcionalmente, popule com alguns dados de exemplo:
   ```bash
   mysql -u root -p < dados_exemplo.sql
   ```

   > ⚠️ O CRUD exige que existam **categorias** e **setores** cadastrados
   > antes de criar um recurso, já que essas colunas são `NOT NULL` na
   > tabela `recurso`. Se optar por não usar o `dados_exemplo.sql`, insira
   > manualmente ao menos um registro em `categoria` e outro em `setor`.

2. **Configuração da conexão**
   Edite `config/database.php`:
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'sistema_reservas';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

3. **Permissão da pasta de uploads**
   ```bash
   chmod 755 uploads
   ```

4. **Subindo o servidor**
   ```bash
   php -S localhost:8000
   ```
   Acesse `http://localhost:8000/index.php`.

---

## Estrutura do projeto

```
crud-recursos/
├── api/
│   ├── recursos.php          → API JSON (GET, POST, PUT, DELETE)
│   └── README.md             → documentação técnica da API + guia Android
├── assets/
│   └── css/style.css         → estilo visual do sistema
├── config/
│   └── database.php          → conexão PDO com o MySQL
├── includes/
│   ├── header.php            → cabeçalho/menu compartilhado
│   ├── footer.php            → rodapé compartilhado
│   └── helpers.php           → upload de imagem, sanitização, status
├── uploads/                  → fotos enviadas pelos formulários
├── sistema_reservas.sql      → dump original do banco (fornecido)
├── dados_exemplo.sql         → dados de exemplo opcionais
├── index.php                  → listagem de recursos, com filtros
├── create.php                 → cadastro de recurso
├── edit.php                   → edição de recurso
└── delete.php                  → exclusão de recurso
```

---

## Banco de dados

O projeto usa as tabelas exatamente como estão no dump enviado. Nenhuma
tabela foi renomeada ou teve colunas alteradas.

**Tabela `recurso`** (tabela principal deste CRUD)

| Campo          | Tipo                                                        | Observação                              |
|----------------|---------------------------------------------------------------|-------------------------------------------|
| id_recurso     | INT (PK)                                                       | auto incremento                            |
| nome           | VARCHAR(100)                                                    | obrigatório                                |
| descricao      | TEXT                                                             | opcional (nullable)                        |
| id_categoria   | INT (FK → `categoria.id_categoria`)                              | **obrigatório** (NOT NULL no banco)        |
| id_setor       | INT (FK → `setor.id_setor`)                                       | **obrigatório** (NOT NULL no banco)        |
| status         | ENUM('Disponível','Em uso','Manutenção','Inativo')                 | padrão: `Disponível`                       |
| localizacao    | VARCHAR(100)                                                         | opcional (nullable)                        |
| data_cadastro  | DATE                                                                   | preenchido automaticamente (`curdate()`)   |
| foto           | VARCHAR(255)                                                            | nome do arquivo salvo em `uploads/`        |

**Tabela `categoria`**: `id_categoria`, `nome`, `descricao`
**Tabela `setor`**: `id_setor`, `nome`, `responsavel`, `telefone`, `email`
**Tabela `historico_uso`**: referencia `recurso.id_recurso` via FK **sem**
`ON DELETE CASCADE` — ou seja, se um recurso tiver histórico de uso
registrado, a exclusão dele será bloqueada pelo banco. O CRUD e a API
tratam esse erro e avisam o usuário de forma amigável, em vez de quebrar.
**Tabela `usuario`**: não é usada por este CRUD (fica disponível no banco
para um sistema de login, se você quiser implementar depois).

---

## CRUD Web

| Página        | Função                                                          |
|---------------|--------------------------------------------------------------------|
| `index.php`   | Lista recursos, com foto, categoria, setor, status e localização. Possui filtros por categoria, setor e status. |
| `create.php`  | Formulário de cadastro (categoria, setor e status são obrigatórios; upload de foto opcional) |
| `edit.php`    | Formulário de edição (trocar/remover foto)                          |
| `delete.php`  | Exclui o recurso e a imagem associada. Se houver histórico de uso vinculado, a exclusão é bloqueada com aviso na tela. |

O upload de imagem:
- Aceita JPG, PNG, WEBP e GIF
- Valida o MIME real do arquivo (não confia apenas na extensão)
- Limite de 5MB
- Gera nome de arquivo único (`uniqid`)
- Apaga o arquivo antigo automaticamente ao editar/excluir/remover foto

---

## API JSON

Endpoint principal: `api/recursos.php`. Documentação completa em
[`api/README.md`](api/README.md).

| Ação                | Método | URL                                                     |
|---------------------|--------|------------------------------------------------------------|
| Listar (com filtros)  | GET    | `/api/recursos.php` — aceita `?categoria=1&setor=2&status=Disponível` |
| Buscar um              | GET    | `/api/recursos.php?id=1`                                 |
| Criar                   | POST   | `/api/recursos.php` (multipart p/ foto, ou JSON)          |
| Atualizar sem foto        | PUT    | `/api/recursos.php?id=1` (JSON)                            |
| Atualizar com foto          | POST   | `/api/recursos.php?id=1` + campo `_method=PUT`             |
| Excluir                       | DELETE | `/api/recursos.php?id=1`                                    |

A API:
- Retorna sempre JSON, com cabeçalhos **CORS** liberados — pronta para ser
  consumida por um app Android sem bloqueio
- Devolve `foto_url` já como URL completa da imagem
- Valida obrigatoriedade de `nome`, `id_categoria`, `id_setor` e `status`
- Retorna `409 Conflict` ao tentar excluir um recurso com histórico de uso vinculado

Exemplo de resposta de `GET /api/recursos.php`:
```json
{
  "total": 1,
  "dados": [
    {
      "id": 1,
      "nome": "Projetor Epson PowerLite",
      "descricao": "Projetor multimídia full HD",
      "categoria": { "id": 1, "nome": "Audiovisual" },
      "setor": { "id": 1, "nome": "TI" },
      "status": "Disponível",
      "localizacao": "Sala de TI",
      "foto": null,
      "foto_url": null,
      "data_cadastro": "2026-07-01"
    }
  ]
}
```

---

## Consumindo no Android

Guia completo (modelos Kotlin, interface Retrofit, exemplo de chamada em
coroutine e carregamento de imagem com Coil) em
[`api/README.md`](api/README.md#consumindo-no-android-kotlin--retrofit).

Pontos rápidos:
- **Emulador Android** → `http://10.0.2.2/...` (aponta para o `localhost` da máquina)
- **Celular físico na mesma rede** → IP local do computador (ex.: `http://192.168.0.10/...`)
- Testes em HTTP puro (sem SSL) exigem `android:usesCleartextTraffic="true"` no `AndroidManifest.xml`

---

## Segurança e boas práticas aplicadas

- Todas as consultas usam **prepared statements** via PDO
- Saída de dados no HTML sempre passa por `htmlspecialchars` (função `e()`)
- Upload de imagem valida o **MIME real** do arquivo
- `.htaccess` bloqueia listagem de diretórios
- Exclusão de recurso trata a restrição de FK de `historico_uso` de forma
  amigável (sem tela de erro genérica do banco)

---

## Possíveis próximos passos

- Autenticação usando a tabela `usuario` já existente no banco (login +
  controle de acesso por `nivel`)
- CRUD de reservas propriamente dito, usando `historico_uso` (data início/fim,
  usuário, turma, finalidade), com verificação de conflito de datas
- CRUD próprio para gerenciar `categoria` e `setor` pela interface web
- Paginação e busca por nome na listagem (web e API)
