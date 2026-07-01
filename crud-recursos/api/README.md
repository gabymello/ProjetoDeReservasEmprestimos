# API de Recursos — `sistema_reservas`

Base URL: `/api/recursos.php`

Todas as respostas são em JSON (`Content-Type: application/json`).

---

## Listar recursos

```
GET /api/recursos.php
```

**Filtros opcionais (querystring):**
| Parâmetro   | Exemplo              | Descrição                         |
|-------------|-----------------------|---------------------------------------|
| categoria   | `?categoria=1`         | filtra por `id_categoria`             |
| setor       | `?setor=2`             | filtra por `id_setor`                 |
| status      | `?status=Disponível`   | filtra por status exato               |

Os filtros podem ser combinados: `?categoria=1&status=Disponível`

**Resposta 200**
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
      "foto": "recurso_65123abc.jpg",
      "foto_url": "http://seusite.com/uploads/recurso_65123abc.jpg",
      "data_cadastro": "2026-07-01"
    }
  ]
}
```

---

## Buscar um recurso

```
GET /api/recursos.php?id=1
```

**Resposta 200** → `{ "dado": { ... } }`
**Resposta 404** → `{ "erro": "Recurso não encontrado." }`

---

## Criar recurso

```
POST /api/recursos.php
Content-Type: multipart/form-data   (use isso se for enviar foto)
```

**Campos:**
| Campo         | Tipo    | Obrigatório | Observação                                      |
|---------------|---------|-------------|----------------------------------------------------|
| nome          | string  | sim         |                                                      |
| descricao     | string  | não         |                                                      |
| id_categoria  | int     | sim         | precisa existir em `categoria`                       |
| id_setor      | int     | sim         | precisa existir em `setor`                            |
| status        | string  | sim         | um de: `Disponível`, `Em uso`, `Manutenção`, `Inativo` |
| localizacao   | string  | não         |                                                      |
| foto          | arquivo | não         |                                                      |

Também aceita `Content-Type: application/json` quando não há upload de foto:
```json
{
  "nome": "Impressora HP",
  "id_categoria": 2,
  "id_setor": 1,
  "status": "Disponível",
  "localizacao": "Sala 05"
}
```

**Resposta 201** → `{ "mensagem": "Recurso criado.", "dado": { ... } }`
**Resposta 422** → `{ "erro": "O campo \"id_categoria\" é obrigatório." }`

---

## Atualizar recurso

**Sem foto (JSON):**
```
PUT /api/recursos.php?id=1
Content-Type: application/json

{ "status": "Em uso", "localizacao": "Sala 08" }
```

**Com foto (multipart, use override de método):**
```
POST /api/recursos.php?id=1
Content-Type: multipart/form-data

_method=PUT
nome=Novo nome
foto=(arquivo)
```
> PHP não processa `multipart/form-data` em requisições `PUT` nativas — por
> isso, para trocar a foto, envie via `POST` com o campo `_method=PUT`.

**Resposta 200** → `{ "mensagem": "Recurso atualizado.", "dado": { ... } }`

---

## Excluir recurso

```
DELETE /api/recursos.php?id=1
```

**Resposta 200** → `{ "mensagem": "Recurso excluído com sucesso." }`
**Resposta 404** → `{ "erro": "Recurso não encontrado." }`
**Resposta 409** → `{ "erro": "Não é possível excluir: existem registros de histórico de uso vinculados a este recurso." }`
> Isso acontece porque `historico_uso.id_recurso` é uma FK sem
> `ON DELETE CASCADE` — o banco protege o histórico contra exclusões.

---

## Exemplos com cURL

```bash
# Listar todos
curl http://localhost:8000/api/recursos.php

# Listar apenas disponíveis do setor 1
curl "http://localhost:8000/api/recursos.php?setor=1&status=Disponível"

# Criar (com foto)
curl -X POST http://localhost:8000/api/recursos.php \
  -F "nome=Cadeira Gamer" \
  -F "id_categoria=3" \
  -F "id_setor=2" \
  -F "status=Disponível" \
  -F "foto=@/caminho/para/imagem.jpg"

# Atualizar status (sem foto)
curl -X PUT http://localhost:8000/api/recursos.php?id=1 \
  -H "Content-Type: application/json" \
  -d '{"status":"Manutenção"}'

# Excluir
curl -X DELETE http://localhost:8000/api/recursos.php?id=1
```

---

## Consumindo no Android (Kotlin + Retrofit)

**1. Dependências (`build.gradle` do módulo app):**
```kotlin
implementation("com.squareup.retrofit2:retrofit:2.11.0")
implementation("com.squareup.retrofit2:converter-gson:2.11.0")
implementation("com.squareup.okhttp3:okhttp:4.12.0")
implementation("io.coil-kt:coil:2.6.0") // para carregar a foto
```

> No `AndroidManifest.xml`, se a API estiver em HTTP (sem SSL) durante
> testes locais, adicione `android:usesCleartextTraffic="true"` na tag
> `<application>`.

**2. Modelos de dados:**
```kotlin
data class RecursoResponse(
    val total: Int,
    val dados: List<Recurso>
)

data class Recurso(
    val id: Int,
    val nome: String,
    val descricao: String?,
    val categoria: Categoria,
    val setor: Setor,
    val status: String,
    val localizacao: String?,
    val foto: String?,
    val foto_url: String?,
    val data_cadastro: String?
)

data class Categoria(val id: Int, val nome: String?)
data class Setor(val id: Int, val nome: String?)
```

**3. Interface Retrofit:**
```kotlin
interface RecursoApi {
    @GET("api/recursos.php")
    suspend fun listar(
        @Query("categoria") categoriaId: Int? = null,
        @Query("setor") setorId: Int? = null,
        @Query("status") status: String? = null
    ): RecursoResponse

    @GET("api/recursos.php")
    suspend fun buscar(@Query("id") id: Int): Map<String, Recurso>

    @DELETE("api/recursos.php")
    suspend fun excluir(@Query("id") id: Int): Map<String, String>
}
```

**4. Criando o client:**
```kotlin
val retrofit = Retrofit.Builder()
    .baseUrl("http://SEU_DOMINIO_OU_IP/crud-recursos/")
    .addConverterFactory(GsonConverterFactory.create())
    .build()

val api = retrofit.create(RecursoApi::class.java)
```

**5. Buscando a lista de recursos (dentro de uma coroutine):**
```kotlin
lifecycleScope.launch {
    try {
        val resposta = api.listar(status = "Disponível")
        adapter.submitList(resposta.dados)
    } catch (e: Exception) {
        Log.e("API", "Erro ao buscar recursos", e)
    }
}
```

**6. Exibindo a foto num `ImageView`:**
```kotlin
imageView.load(recurso.foto_url) {
    placeholder(R.drawable.placeholder_recurso)
    error(R.drawable.placeholder_recurso)
}
```

**Observações importantes para o Android:**
- **Emulador Android** → use `http://10.0.2.2/...` como base URL
- **Celular físico na mesma rede Wi-Fi** → use o IP local do computador (ex.: `http://192.168.0.10/...`)
- O campo `status` deve ser enviado exatamente como está no banco
  (`Disponível`, `Em uso`, `Manutenção`, `Inativo`), incluindo acentuação
