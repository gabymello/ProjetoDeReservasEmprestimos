# Gestão de Recursos — CRUD + API JSON

Sistema de cadastro de recursos (nome, descrição, categoria, setor e foto),
com um CRUD web completo em PHP e uma API JSON para consumo externo
(ex.: aplicativo Android).

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
- MySQL 5.7+ ou MariaDB equivalente
- Servidor web (Apache, Nginx ou o servidor embutido do PHP)

---

## Instalação

1. **Banco de dados**
   Importe o arquivo `database.sql` no seu MySQL:
   ```bash
   mysql -u root -p < database.sql
   ```
   Isso cria o banco `crud_recursos`, as tabelas `categorias`, `setores` e
   `recursos`, além de alguns registros de exemplo.

2. **Configuração da conexão**
   Edite `config/database.php` com as credenciais do seu ambiente:
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'crud_recursos';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

3. **Permissão da pasta de uploads**
   Garanta que a pasta `uploads/` tenha permissão de escrita:
   ```bash
   chmod 755 uploads
   ```

4. **Subindo o servidor**
   Para testes rápidos, use o servidor embutido do PHP a partir da raiz do projeto:
   ```bash
   php -S localhost:8000
   ```
   Acesse `http://localhost:8000/index.php`.

   Em produção, aponte o Apache/Nginx para a raiz do projeto normalmente.

---

## Estrutura do projeto

```
crud-recursos/
├── api/
│   ├── recursos.php        → API JSON (GET, POST, PUT, DELETE)
│   └── README.md           → documentação técnica da API + guia Android
├── assets/
│   └── css/style.css       → estilo visual do sistema
├── config/
│   └── database.php        → conexão PDO com o MySQL
├── includes/
│   ├── header.php          → cabeçalho/menu compartilhado
│   ├── footer.php          → rodapé compartilhado
│   └── helpers.php         → upload de imagem, sanitização, funções úteis
├── uploads/                → fotos enviadas pelos formulários
├── database.sql            → script de criação do banco
├── index.php                → listagem de recursos
├── create.php               → cadastro de recurso
├── edit.php                 → edição de recurso
└── delete.php                → exclusão de recurso
```

---

## Banco de dados

**Tabela `recursos`**

| Campo          | Tipo      | Observação                                   |
|----------------|-----------|-----------------------------------------------|
| id             | INT (PK)  | auto incremento                                |
| nome           | VARCHAR   | obrigatório                                    |
| descricao      | TEXT      | opcional (nullable)                            |
| categoria_id   | INT (FK)  | opcional (nullable) → `categorias.id`          |
| setor_id       | INT (FK)  | opcional (nullable) → `setores.id`             |
| foto           | VARCHAR   | nome do arquivo salvo em `uploads/` (nullable) |
| criado_em      | DATETIME  | preenchido automaticamente                     |
| atualizado_em  | DATETIME  | atualizado automaticamente                     |

**Tabelas `categorias` e `setores`**

Foram criadas como catálogos simples (`id`, `nome`) porque a estrutura real
não havia sido definida. Se você já possui essas tabelas em outro lugar do
seu sistema, edite `database.sql`, apague os blocos `CREATE TABLE categorias`
/ `CREATE TABLE setores` e ajuste as `FOREIGN KEY` de `recursos` para
apontarem para as tabelas existentes.

As FKs usam `ON DELETE SET NULL`: se uma categoria ou setor for removido, o
recurso não é apagado — apenas fica sem categoria/setor definidos.

---

## CRUD Web

Interface web tradicional, acessada pelo navegador:

| Página        | Função                                              |
|---------------|-------------------------------------------------------|
| `index.php`   | Lista todos os recursos, com foto, categoria e setor  |
| `create.php`  | Formulário de cadastro (com upload de foto)           |
| `edit.php`    | Formulário de edição (trocar/remover foto)            |
| `delete.php`  | Exclui o recurso e apaga o arquivo de imagem associado |

O upload de imagem:
- Aceita JPG, PNG, WEBP e GIF
- Valida o MIME real do arquivo (não confia apenas na extensão)
- Limite de 5MB
- Gera nome de arquivo único (`uniqid`), evitando conflitos
- Ao editar ou excluir, apaga o arquivo antigo do servidor automaticamente

---

## API JSON

Endpoint principal: `api/recursos.php`. Documentação completa e exemplos de
`curl` em [`api/README.md`](api/README.md).

Resumo dos endpoints:

| Ação                | Método | URL                                             |
|---------------------|--------|--------------------------------------------------|
| Listar todos         | GET    | `/api/recursos.php`                              |
| Buscar um             | GET    | `/api/recursos.php?id=1`                         |
| Criar                 | POST   | `/api/recursos.php` (multipart p/ foto, ou JSON) |
| Atualizar sem foto     | PUT    | `/api/recursos.php?id=1` (JSON)                  |
| Atualizar com foto      | POST   | `/api/recursos.php?id=1` + campo `_method=PUT`   |
| Excluir                | DELETE | `/api/recursos.php?id=1`                         |

A API:
- Retorna sempre JSON (`Content-Type: application/json`)
- Já inclui cabeçalhos **CORS** liberados (`Access-Control-Allow-Origin: *`),
  necessários para que apps externos — como um app Android — consigam
  consumir os dados sem bloqueio
- Retorna `foto_url` com a URL completa da imagem, pronta para ser carregada
  direto num `ImageView` do Android
- Usa os mesmos códigos HTTP semânticos: `200` (sucesso), `201` (criado),
  `404` (não encontrado), `422` (validação), `500` (erro interno)

Exemplo de resposta de `GET /api/recursos.php`:
```json
{
  "total": 1,
  "dados": [
    {
      "id": 1,
      "nome": "Notebook Dell Latitude",
      "descricao": "Uso do setor de TI",
      "categoria": { "id": 2, "nome": "Software" },
      "setor": { "id": 2, "nome": "TI" },
      "foto": "recurso_65123abc.jpg",
      "foto_url": "http://seusite.com/uploads/recurso_65123abc.jpg",
      "criado_em": "2026-06-30 10:00:00",
      "atualizado_em": "2026-06-30 10:00:00"
    }
  ]
}
```

---

## Consumindo no Android

Um guia completo (modelos Kotlin, interface Retrofit, exemplo de chamada em
coroutine e carregamento de imagem com Coil) está em
[`api/README.md`](api/README.md#consumindo-no-android-kotlin--retrofit).

Pontos rápidos:
- **Emulador Android** → use `http://10.0.2.2/...` como base URL (aponta
  para o `localhost` da sua máquina).
- **Celular físico na mesma rede** → use o IP local do seu computador
  (ex.: `http://192.168.0.10/...`).
- Se a API estiver em HTTP puro (sem SSL) durante testes, adicione
  `android:usesCleartextTraffic="true"` no `AndroidManifest.xml`.

---

## Segurança e boas práticas aplicadas

- Todas as consultas usam **prepared statements** via PDO (proteção contra
  SQL Injection)
- Saída de dados no HTML sempre passa por `htmlspecialchars` (proteção
  contra XSS), através da função `e()`
- Upload de imagem valida o **MIME real** do arquivo, não apenas a extensão
- `.htaccess` bloqueia listagem de diretórios
- Chaves estrangeiras com `ON DELETE SET NULL` evitam exclusões em cascata
  indesejadas

---

## Possíveis próximos passos

Caso o projeto cresça, algumas melhorias que fazem sentido adicionar depois:

- Autenticação (login) tanto no CRUD web quanto na API (ex.: token Bearer)
- Paginação na listagem (web e API)
- Filtros por categoria/setor na API (`?categoria_id=1`)
- Campo de busca por nome
- CRUD próprio para gerenciar categorias e setores pela interface web
