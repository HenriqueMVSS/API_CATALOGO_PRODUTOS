# API Catálogo de Produtos

API REST desenvolvida em Laravel 11 para gerenciamento de catálogo de produtos com busca ElasticSearch, cache Redis e integração AWS S3.

## 📋 Requisitos

- Docker e Docker Compose
- PHP 8.2+
- Composer
- MySQL 8.0+
- Redis 7+
- Elasticsearch 8.11+

## 🚀 Como Rodar com Docker

### 1. Clone o repositório

```bash
git clone <repository-url>
cd Catalogo-Produtos
```

### 2. Configure o ambiente

```bash
cp .env.example .env
```

Edite o arquivo `.env` se necessário (as configurações padrão já estão prontas para Docker).

### 3. Suba os containers

```bash
docker compose up -d
```

Isso irá subir:
- **app**: Aplicação Laravel (PHP-FPM)
- **mysql**: Banco de dados MySQL
- **redis**: Cache Redis
- **elasticsearch**: Elasticsearch para busca

### 4. Instale as dependências

```bash
docker compose exec app composer install
```

### 5. Gere a chave da aplicação

```bash
docker compose exec app php artisan key:generate
```

### 6. Execute as migrations

```bash
docker compose exec app php artisan migrate
```

### 7. Execute os seeders (opcional)

```bash
docker compose exec app php artisan db:seed
```

Isso criará 10 produtos de exemplo no banco de dados.

### 8. Gere a documentação Swagger

```bash
docker compose exec app php artisan l5-swagger:generate
```

### 9. Acesse a aplicação

A API estará disponível em: `http://localhost:8000`

**Documentação Swagger**: `http://localhost:8000/api/documentation`

## 🧪 Como Rodar Testes

### Com Docker

```bash
docker compose exec app php artisan test
```

### Localmente (requer ambiente configurado)

```bash
php artisan test
```

Os testes utilizam SQLite em memória para maior velocidade e isolamento.

## 📚 Endpoints da API

### Produtos

- `POST /api/products` - Criar produto
- `GET /api/products` - Listar produtos (com paginação e filtros)
- `GET /api/products/{id}` - Buscar produto por ID
- `PUT /api/products/{id}` - Atualizar produto
- `DELETE /api/products/{id}` - Excluir produto (soft delete)
- `POST /api/products/{id}/image` - Upload de imagem do produto

### Busca

- `GET /api/search/products` - Buscar produtos com ElasticSearch

#### Parâmetros de busca:

- `q` - Busca textual em name e description
- `category` - Filtrar por categoria
- `min_price` - Preço mínimo
- `max_price` - Preço máximo
- `status` - Filtrar por status (active/inactive)
- `sort` - Ordenar por (price, created_at)
- `order` - Ordem (asc, desc)
- `page` - Página
- `per_page` - Itens por página

## 🏗️ Arquitetura

O projeto segue uma arquitetura limpa com separação de responsabilidades:

```
app/
├── DTOs/              # Data Transfer Objects
├── Http/
│   ├── Controllers/   # Controllers da API
│   └── Requests/      # Form Requests (validação)
├── Models/            # Eloquent Models
├── Observers/         # Model Observers
├── Repositories/      # Repositories (camada de dados)
├── Services/          # Services (lógica de negócio)
└── Providers/         # Service Providers
```

### Fluxo de Dados

```
Controller → Service → Repository → Model → Database
                ↓
         ElasticsearchService
                ↓
         Cache (Redis)
```

## 🔧 Decisões Técnicas

### 1. **Arquitetura em Camadas**
- **Controllers**: Apenas recebem requisições e retornam respostas
- **Services**: Contêm a lógica de negócio
- **Repositories**: Abstraem o acesso aos dados
- **DTOs**: Transferem dados entre camadas

### 2. **ElasticSearch**
- Sincronização automática via Observer
- Indexação assíncrona (não bloqueia a resposta)
- Tratamento de erros com logs

### 3. **Cache Redis**
- TTL de 90 segundos
- Invalidação automática em updates/deletes
- Cache por combinação de parâmetros na busca
- Não cacheia páginas muito altas (page > 50)

### 4. **Soft Delete**
- Produtos não são removidos fisicamente
- Permite auditoria e recuperação

### 5. **AWS S3**
- Upload de imagens com fallback para storage local
- Suporta configuração real ou simulação

### 6. **Testes**
- SQLite em memória para testes (mais rápido)
- MySQL em produção
- Cobertura de casos principais (CRUD, validações, busca)

## 📝 Validações

### Regras de Negócio

- **SKU**: Único, obrigatório
- **Nome**: Mínimo 3 caracteres, obrigatório
- **Preço**: Maior que zero, obrigatório
- **Status**: Padrão "active", valores: active/inactive

## 🔍 Observabilidade

- Logs estruturados em todas as operações importantes
- Tratamento de erros padronizado
- Mensagens de erro claras e consistentes

## 🐳 Docker

### Estrutura dos Containers

- **app**: PHP 8.2-FPM com extensões necessárias
- **mysql**: MySQL 8.0 com healthcheck
- **redis**: Redis 7 Alpine
- **elasticsearch**: Elasticsearch 8.11.0

### Comandos Úteis

```bash
# Ver logs
docker compose logs -f app

# Acessar container
docker compose exec app bash

# Reiniciar serviços
docker compose restart

# Parar tudo
docker compose down

# Parar e remover volumes
docker compose down -v
```

## 📦 Limitações Conhecidas

1. **ElasticSearch**: Requer alguns segundos para indexar após criação/atualização
2. **Cache**: Invalidação de cache de busca não é granular (invalida todos)
3. **S3**: Em ambiente local, usa storage local como fallback
4. **Testes**: Alguns testes de busca podem falhar se ElasticSearch não estiver pronto

## 🚧 Próximos Passos

- [ ] Implementar filas para indexação assíncrona do ElasticSearch
- [ ] Adicionar autenticação (JWT ou Sanctum)
- [ ] Implementar rate limiting
- [ ] Adicionar mais testes de integração
- [ ] Melhorar invalidação granular de cache
- [ ] Adicionar métricas e monitoramento
- [ ] Implementar versionamento de API
- [ ] Adicionar documentação de erros na API

## 📄 Licença

Este projeto é um desafio técnico e não possui licença específica.

## 👤 Autor

Desenvolvido como parte de um desafio técnico.

---

**Nota**: Para produção, configure adequadamente as variáveis de ambiente, especialmente as credenciais AWS e configurações de segurança.
