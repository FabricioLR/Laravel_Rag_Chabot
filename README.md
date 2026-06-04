# Laravel RAG Chatbot Engine

A modern, enterprise-grade Retrieval-Augmented Generation (RAG) chatbot engine built on top of Laravel 11+, PostgreSQL (`pgvector`), and external automation layers like n8n. The engine implements scheduled and override sync-pipelines to pull live publication material from decoupled WordPress nodes, chunks it into normalized markdown strings, embeds it using heavy AI weights, and scores them seamlessly for multi-tenant frontend embed widgets.

---

## 🏗️ Core Architectural Mechanics

The system operates via two isolated, highly cohesive pipelines ensuring immediate real-time synchronization and contextual response accuracy:

### 1. Ingestion Pipeline (WordPress to Vector Space)

* **Scheduling Vector Blocks**: A native command (`app:fetch-word-press-posts`) runs continuously controlled by the Laravel scheduler. It sweeps a target database using execution pointers and watermark indexes tracked on local application caches (`wp_last_execution`, `wp_last_indexed_posts`).
* **Asynchronous Isolation**: Discovered payloads dispatch isolated `IngestPost` jobs onto database-backed worker queues (`database` queue driver).
* **Content Normalization & Chunking**: Incoming HTML data structures are dynamically converted into normalized Markdown text elements using `league/html-to-markdown`. Files are parsed across configured semantic text splitters to fit within context envelopes.
* **Vector Serialization**: Segments are uploaded through an internal embedding pipeline (supporting external interfaces like HuggingFace or Groq API structures), resulting in high-density vectors serialized directly into your Postgres layer.

### 2. Search & Retrieving Pipeline (Chat & Context Building)

* **Full-Text vs. Semantic Hybrid Engine**: Data retrieval doesn't rely solely on basic vector similarities. Database layers utilize two performance-tuned indexes:
* A **GIN index** applying `to_tsvector` localized to Portuguese languages for semantic Keyword Matching.
* An **HNSW index** targeting `embedding vector_cosine_ops` optimizing rapid, ultra-low latency cosine operations.


* **RRF Rank Scoring**: Inside the core vector migration, an immutable custom mathematical function called `rrf_score` executes a Reciprocal Rank Fusion algorithmic calculation. It cross-references textual and vector queries, sorting context structures dynamically, and returning perfectly contextual payloads back to LLM completion layers.

---

## 🖥️ Admin Dashboard Panel Administration

The application features a secure, centralized administrative dashboard layer built to handle tenant configurations, system metrics, and ecosystem tokens:

* **Multi-Tenant Whitelisting**: Through the **Allowed Domains** manager resource, administrators can authorize or revoke external origins. This enforces strict CORS guardrails and ensures only verified websites can render the embedding chat widget components.
* **API Token & Key Management**: Provision, rotate, and keep track of access tokens tied to third-party integration points (such as n8n workflows and continuous data ingestion hooks).
* **Pipeline Monitoring**: Inspect live operational parameters directly from the UI, tracking indexed posts count, last successful ingestion watermarks, and resource health metrics across workers.

---

## 📦 System Architecture & Directory Tree

* **app/**
    * **Console/Commands/** — Execution watermarks & manual state utilities (`FetchWordPressPosts.php`, `ViewWordPressSyncState.php`)
    * **Http/Controllers/Api/** — Token-validated and categorized chat endpoints (`ChatController.php`, `IngestionController.php`)
    * **Jobs/IngestPost.php** — Markdown transformation, split engines, and ingestion workers
    * **Models/AllowedDomain.php** — Multi-tenant domain white-listing and API token structures
    * **Services/** — Core domain layer (Embeddings, RRF Query Search, LLM connectors)
* **database/migrations/** — Strict pgvector schema schemas, indices & RRF functions
* **docker/** — Optimized Alpine-PHP multi-stage build environments and reverse proxy layers
* **resources/js/** — IIFE self-contained front-end chat widget compilation (`widget-entry.jsx`)
* **routes/** — Separated API and isolated Admin view routing manifests
* **RAG-Test.json** — Native n8n node configuration structure for isolated integration tests

---

## 🚀 Environment Blueprint & Production Deployment

### Local Development Setup

The project provides a unified, local Docker environment containing health check bindings to orchestrate immediate testing:

1. **Clone the project and copy configuration manifests**:
cp .env.model .env
2. **Boot up dependencies using docker-compose**:
docker-compose up -d --build
3. **Initialize Application Environment**:
Run the native setup sequence inside the development container to handle installs, key generation, migrations, and frontend asset compilation automatically:
docker exec -it laravel_app_dev composer run setup

*Note: The dev container runs standard database seeders, setups your default administrative credentials, and exposes Vite HMR ports (`5173`) and artisan layers (`8000`) instantly.*

### Production Stage Architecture

For production environments, the engine leverages `docker-compose.prod.yml` to isolate the web routing layer, the persistent application instances, and high-performance queue processing daemons:

docker-compose -f docker-compose.prod.yml up -d --build

#### Production Containers:

* **`chatbot_app_prod`**: Core application processing container, mounted securely onto isolated production storage layers.
* **`chatbot_app_queue`**: Dedicated asynchronous supervisor executing continuous worker processes (`queue:work`) tracking up to 3 failed attempts before execution timeouts occur.
* **`chatbot_nginx_prod`**: Hardened reverse proxy exposing public interface layers over port `8081`.

---

## 🛠️ CLI Operations & Pipeline Administration

The platform ships with customized automation utilities to track internal systems directly from your host terminal:

* **Inspect Core Ingestion Status**: Check current execution timestamps, human-readable execution delays, and the precise IDs tracked inside the exclusion list window:
./get_sync_stat.sh
* **Clear Core Indexes**: Flush specific system cache blocks manually to enforce a comprehensive re-index of all external articles:
./get_sync_stat.sh --clear
* **Simulate Background Schedulers**: Initiate continuous worker mock-loops locally:
./start_scheduler.sh

---

## 🧪 Testing Suite Execution

The application incorporates comprehensive Unit and Feature paradigms powered by Pest. Database operations are isolated on dedicated in-memory mock parameters inside `docker-compose.test.yml`:

docker-compose -f docker-compose.test.yml up --exit-code-from app

*(Automatically ensures database instances are completely sound via `pg_isready` checks before executing migrations and verifying the RAG pipeline).*

---

## 🔄 External Integration Testing (n8n)

The repository provides a `RAG-Test.json` blueprint containing a native n8n testing workflow configuration.

* **Purpose**: This pipeline extracts random post IDs from the database, sweeps out formatting strings, cleans up HTML to markdown elements, and triggers test queries into the Groq API (`llama-3.1-8b-instant`) to simulate QA validation tasks.
* **Deployment**: Simply import `RAG-Test.json` directly into your self-hosted n8n instance and assign your custom credentials to activate it.