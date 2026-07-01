# API de Recursos

Base URL: `/api/recursos.php`

Todas as respostas são em JSON (`Content-Type: application/json`).

---

## Listar todos

```
GET /api/recursos.php
```

**Resposta 200**
```json
{
  "total": 2,
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
| Campo         | Tipo    | Obrigatório |
|---------------|---------|-------------|
| nome          | string  | sim         |
| descricao     | string  | não         |
| categoria_id  | int     | não         |
| setor_id      | int     | não         |
| foto          | arquivo | não         |

Também aceita `Content-Type: application/json` quando não há upload de foto:
```json
{ "nome": "Impressora HP", "categoria_id": 1, "setor_id": 3 }
```

**Resposta 201** → `{ "mensagem": "Recurso criado.", "dado": { ... } }`
**Resposta 422** → `{ "erro": "O campo \"nome\" é obrigatório." }`

---

## Atualizar recurso

**Sem foto (JSON):**
```
PUT /api/recursos.php?id=1
Content-Type: application/json

{ "nome": "Novo nome", "setor_id": null }
```

**Com foto (multipart, use override de método):**
```
POST /api/recursos.php?id=1
Content-Type: multipart/form-data

_method=PUT
nome=Novo nome
foto=(arquivo)
```
> PHP não processa `multipart/form-data` em requisições `PUT` nativas — por isso, para trocar a foto, envie via `POST` com o campo `_method=PUT`.

**Resposta 200** → `{ "mensagem": "Recurso atualizado.", "dado": { ... } }`

---

## Excluir recurso

```
DELETE /api/recursos.php?id=1
```

**Resposta 200** → `{ "mensagem": "Recurso excluído com sucesso." }`
**Resposta 404** → `{ "erro": "Recurso não encontrado." }`

---

## Consumindo no Android (Kotlin + Retrofit)

**1. Dependências (`build.gradle` do módulo app):**
```kotlin
implementation("com.squareup.retrofit2:retrofit:2.11.0")
implementation("com.squareup.retrofit2:converter-gson:2.11.0")
implementation("com.squareup.okhttp3:okhttp:4.12.0")
```

> No `AndroidManifest.xml`, se a API estiver em HTTP (sem SSL) durante testes locais,
> adicione `android:usesCleartextTraffic="true"` na tag `<application>`.

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
    val categoria: Categoria?,
    val setor: Setor?,
    val foto: String?,
    val foto_url: String?
)

data class Categoria(val id: Int?, val nome: String?)
data class Setor(val id: Int?, val nome: String?)
```

**3. Interface Retrofit:**
```kotlin
interface RecursoApi {
    @GET("api/recursos.php")
    suspend fun listar(): RecursoResponse

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
        val resposta = api.listar()
        // resposta.dados -> lista pronta para popular um RecyclerView
        adapter.submitList(resposta.dados)
    } catch (e: Exception) {
        Log.e("API", "Erro ao buscar recursos", e)
    }
}
```

**Observações importantes para o Android:**
- Se estiver testando no **emulador**, use `http://10.0.2.2/...` como base URL (esse IP aponta para o `localhost` da sua máquina dentro do emulador).
- Se estiver testando em **celular físico** na mesma rede Wi-Fi, use o IP local do seu computador (ex.: `http://192.168.0.10/...`).
- Para exibir a foto (`foto_url`) num `ImageView`, use uma lib como Coil ou Glide:
  ```kotlin
  implementation("io.coil-kt:coil:2.6.0")
  imageView.load(recurso.foto_url)
  ```



```bash
# Listar
curl http://localhost:8000/api/recursos.php

# Criar (com foto)
curl -X POST http://localhost:8000/api/recursos.php \
  -F "nome=Cadeira Gamer" \
  -F "categoria_id=1" \
  -F "foto=@/caminho/para/imagem.jpg"

# Atualizar (sem foto)
curl -X PUT http://localhost:8000/api/recursos.php?id=1 \
  -H "Content-Type: application/json" \
  -d '{"nome":"Cadeira Ergonômica"}'

# Excluir
curl -X DELETE http://localhost:8000/api/recursos.php?id=1
```
