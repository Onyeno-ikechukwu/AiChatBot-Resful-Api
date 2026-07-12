# AiChatBot RESTful API

A **Laravel-based RESTful API** that integrates with **Groq AI** to provide intelligent chat capabilities. The API supports both **authenticated (subscribed)** users and **unregistered (guest)** users, with image analysis features powered by Groq's LLaMA vision models.

---

## Features

- **AI-Powered Chat** – Send text prompts and/or images to Groq AI models and receive intelligent responses.
- **Image Analysis** – Upload images with or without prompts for AI-powered analysis.
- **Conversation History** – Context-aware chat that maintains conversation memory across messages.
- **Authenticated User Support** – Full CRUD operations for registered users with subscription management.
- **Guest (Unregistered) Access** – Limited free-tier access for unauthenticated users (up to 10 chats total via IP tracking).
- **Rate Limiting** – Authenticated endpoints are throttled (6 requests per minute).
- **Daily Chat Limits** – Free-tier authenticated users get 110 chats per day.
- **API Documentation** – Auto-generated OpenAPI documentation via **Scramble**.

---

## Requirements

- PHP ^8.3
- Composer
- MySQL or compatible database
- Web server (Apache/Nginx) or Laravel's built-in server (`php artisan serve`)
- Groq API key ([Get one here](https://console.groq.com/))

---

## Installation

### 1. Clone the Repository

```bash
git clone git@github.com:Onyeno-ikechukwu/AiChatBot-Resful-Api.git
cd AiChatBot-Resful-Api
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the `.env.example` file and update it with your credentials:

```bash
cp .env.example .env
```

Edit the `.env` file with your database and API credentials:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=apichatbot
DB_USERNAME=root
DB_PASSWORD=your_password

# Groq AI API Key (required for AI chat functionality)
GROQ_API_KEY=your_actual_groq_api_key_here

# Flutterwave Payment (optional, for subscriptions)
FLW_CLIENT_ID=your_client_id_here
FLW_CLIENT_SECRET=your_client_secret_here
FLW_ENCRYPTION_KEY=your_encryption_key_here
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Database Migrations

```bash
php artisan migrate
```

This creates the following tables:
- `chat` – Stores all chat conversations (both registered and unregistered users)
- Other default Laravel tables (users, personal_access_tokens, etc.)

### 6. Seed the Database (Optional)

The project includes a **UserSeeder** that creates a test user for development purposes.

#### Seeder Files

**`database/seeders/UserSeeder.php`** – Creates a single free-tier test user:

```php
\App\Models\User::create([
    'email' => 'free@example.com',
    'password' => "123456789",  // Auto-hashed via User model's cast
]);
```

| Field | Value | Description |
|-------|-------|-------------|
| Email | `free@example.com` | Use this to log in |
| Password | `123456789` | Plain text (hashed automatically by Laravel) |

#### Running Seeders

**Seed the test user individually:**

```bash
php artisan db:seed --class=UserSeeder
```

**Run all seeders (if additional seeders are added in the future):**

```bash
php artisan db:seed
```

**Reset migrations and seed in one command (fresh start):**

```bash
php artisan migrate:fresh --seed
```

> **Note:** The seeded user is a **non-subscribed** user and will be subject to the **110 chats per day** free-tier limit. To test as a subscribed user, you would need a Payment record with `status = 'successful'` and `expires_at > now()` via the Flutterwave payment flow.

### 7. Install & Configure Scramble (API Documentation)

[**Scramble**](https://github.com/dedoc/scramble) is a Laravel package that automatically generates OpenAPI (Swagger) documentation from your codebase by analyzing route definitions, controllers, FormRequest validation rules, and PHPDoc annotations.

#### What is Scramble?

Scramble is a **zero-configuration** OpenAPI (Swagger) documentation generator for Laravel. Unlike traditional Swagger tools that require verbose annotations or yaml files, Scramble infers your API schema directly from:

- Route registrations in `routes/api.php`
- Controller method parameters and return types
- FormRequest validation rules
- PHPDoc blocks and type hints
- Eloquent model casts and relationships

This means your documentation stays **always in sync** with your code — when you update validation rules, response structures, or add new endpoints, Scramble automatically reflects those changes.

#### How Scramble is Used in This Project

Scramble analyzes:

1. **`app/Http/Controllers/AiChatBotController.php`** – Its PHPDoc blocks, method signatures, and return types inform Scramble about endpoint behavior, parameters, and response structures.
2. **`app/Http/Controllers/UnRegisteredAiChatBotController.php`** – Same analysis for guest endpoints.
3. **`app/Http/Requests/AiChatBotRequest.php` & `UnRegisteredAiChatBotRequest.php`** – Laravel FormRequest `rules()` methods are parsed to generate request body schemas with parameter types, validation constraints, and descriptions.
4. **`app/Models/AiChatBot.php`** – Model casts (e.g., `image_path` cast as `array`) are analyzed to produce accurate response schemas.
5. **`routes/api.php`** – Route definitions, middleware groups, and URI patterns are all extracted.

#### Install Scramble (Already Included)

Scramble is already included as a dependency in `composer.json` (`dedoc/scramble: ^0.13.33`). If you need to install it fresh on a new Laravel project:

```bash
composer require dedoc/scramble
```

#### Publish Scramble Configuration (Optional)

```bash
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider" --tag="scramble-config"
```

This creates `config/scramble.php` where you can customize:
- UI theme and title
- API path filtering
- Security scheme definitions
- Document and operation transformers

#### Access the Documentation

Start your development server:

```bash
php artisan serve
```

Then visit:

- **Scramble UI** → `http://localhost:8000/docs/api`
- **Scalar UI** (alternative) → `http://localhost:8000/docs/api#`

Scramble provides an interactive OpenAPI explorer where you can:
- Browse all available endpoints grouped by category
- View request parameters, headers, and body schemas
- See response status codes and response body structures
- Test endpoints directly from the browser (with authentication tokens)

#### Regenerating Documentation

Scramble generates documentation **on-the-fly** — simply refresh the `/docs/api` page to get the latest schema reflecting any code changes. No build step required.

#### How Document Generation Works (Technical Overview)

1. **Route Collection** – Scramble scans all registered routes and filters those matching the configured API path prefixes.
2. **Controller Analysis** – It reads the controller class PHPDoc comments, method signatures, and return type hints.
3. **FormRequest Inspection** – For each endpoint using a FormRequest, Scramble:
   - Parses the `rules()` method to generate parameter schemas
   - Extracts field descriptions from PHPDoc or rule definitions
   - Determines required vs optional fields
4. **Response Inferring** – It analyzes return statements in controllers to infer response structures, including resource classes and collections.
5. **Model Reflection** – Eloquent models are inspected for casts, fillable attributes, and relationships to enrich response schemas.
6. **OpenAPI Generation** – All collected data is compiled into a valid OpenAPI 3.1 specification.

---

## API Usage

### Base URL

```
http://localhost:8000/api
```

### Authentication

Authenticated endpoints use **Laravel Sanctum** token-based authentication.

**Getting a Token:**

```bash
# Register a new user
POST /api/register

# Login
POST /api/login

# Response includes:
{
  "token": "your-sanctum-token-here",
  "user": { ... }
}
```

**Using the Token:**

Include the token in the `Authorization` header for authenticated requests:

```
Authorization: Bearer your-sanctum-token-here
Content-Type: application/json
Accept: application/json
```

---

### Endpoints Overview

| Method | Endpoint | Auth Required | Description |
|--------|----------|--------------|-------------|
| GET | `/api/chat` | Yes | List authenticated user's chats |
| POST | `/api/chat` | Yes | Create a new chat |
| GET | `/api/chat/{id}` | Yes | Get a specific chat |
| PUT | `/api/chat/{id}` | Yes | Update an existing chat |
| DELETE | `/api/chat/{id}` | Yes | Delete a chat |
| GET | `/api/unregistered-chat/chat` | No | List guest user's chats (by IP) |
| POST | `/api/unregistered-chat/chat` | No | Create a guest chat |
| GET | `/api/unregistered-chat/chat/{id}` | No | Get a specific guest chat |
| PUT | `/api/unregistered-chat/chat/{id}` | No | Update a guest chat |
| DELETE | `/api/unregistered-chat/chat/{id}` | No | Delete a guest chat |
| POST | `/api/payment` | Yes | Initiate subscription payment |
| GET | `/api/payment/callback` | Yes | Handle payment callback |

---

## Detailed Endpoint Documentation

### Authenticated Chat Endpoints

All authenticated endpoints are rate-limited to **6 requests per minute** and require a valid Sanctum token.

---

#### 1. List Chats (Authenticated)

```
GET /api/chat
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `search` | string | No | Search by title or description (partial match) |
| `sort` | string | No | Sort field. Prefix with `-` for descending. Allowed: `title`, `price`, `created_at`, `updated_at`, `location`, `status`, `views`, `is_featured` |
| `per_page` | integer | No | Number of items per page (pagination) |

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "ip_address": null,
      "image_path": ["ChatImages/abc123.jpg"],
      "user_prompt": "What is in this image?",
      "ai_response": "The image shows a sunset landscape with mountains...",
      "created_at": "2026-07-12T10:30:00.000000Z",
      "updated_at": "2026-07-12T10:30:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

#### 2. Create Chat (Authenticated)

```
POST /api/chat
```

**Headers:**
```
Authorization: Bearer your-token
Accept: application/json
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_prompt` | string | No* | The text prompt for the AI |
| `images[]` | file (array) | No* | One or more image files (jpg, jpeg, png, gif, svg). Max 1MB each |

*At least one of `user_prompt` or `images[]` is required.

**Behavior:**

- **With text only:** Sends prompt to Groq LLaMA 3.3 70B model for AI response.
- **With images only:** Analyzes images using Groq LLaMA 4 Scout 17B vision model.
- **With both text and images:** Sends the prompt along with images for contextual AI analysis.
- **Conversation history** is automatically included for context-aware responses.

**Free Tier Limits (non-subscribed authenticated users):**
- Maximum 110 chats per day.
- If exceeded, returns `422` with message: `"You have reached your daily free limit. Please subscribe."`

**Subscribed users** have unlimited chats (requires active `Payment` record with `status = 'successful'` and `expires_at > now()`).

**Response `201 Created`:**

```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "ip_address": null,
    "image_path": ["ChatImages/abc123.jpg"],
    "user_prompt": "Explain quantum computing",
    "ai_response": "Quantum computing is a type of computation that harnesses quantum mechanical phenomena...",
    "created_at": "2026-07-12T10:30:00.000000Z",
    "updated_at": "2026-07-12T10:30:00.000000Z"
  }
}
```

---

#### 3. Get Single Chat (Authenticated)

```
GET /api/chat/{id}
```

**Response `200 OK`:**

```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "ip_address": null,
    "image_path": ["ChatImages/abc123.jpg"],
    "user_prompt": "Explain quantum computing",
    "ai_response": "Quantum computing is a type of computation...",
    "created_at": "2026-07-12T10:30:00.000000Z",
    "updated_at": "2026-07-12T10:30:00.000000Z"
  }
}
```

**Response `404 Not Found`** – If the chat does not exist or does not belong to the authenticated user.

---

#### 4. Update Chat (Authenticated)

```
PUT /api/chat/{id}
```

**Headers:**
```
Authorization: Bearer your-token
Accept: application/json
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_prompt` | string | No* | Updated text prompt |
| `images[]` | file (array) | No* | New image files |

*At least one field must be provided, otherwise returns `422` with `"Nothing to update."`

**Note:** Updating a chat **re-generates the AI response** using the new prompt/images.

**Response `200 OK`:**

```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "ip_address": null,
    "image_path": ["ChatImages/new-image.jpg"],
    "user_prompt": "Updated prompt",
    "ai_response": "New AI response based on updated input...",
    "created_at": "2026-07-12T10:30:00.000000Z",
    "updated_at": "2026-07-12T11:00:00.000000Z"
  }
}
```

**Response `403 Forbidden`** – If the chat does not belong to the authenticated user.

---

#### 5. Delete Chat (Authenticated)

```
DELETE /api/chat/{id}
```

**Response `200 OK`:**

```json
{
  "message": "AiChatBot deleted successfully."
}
```

**Response `403 Forbidden`** – If the chat does not belong to the authenticated user.

---

### Unregistered (Guest) Chat Endpoints

These endpoints are **public** (no authentication required) and use the **client's IP address** to identify and scope chat conversations.

**Guest Limits:**
- Maximum **10 chats total** per IP address.
- If exceeded, returns `422` with message: `"You have reached the maximum number of requests allowed for you. Please create an account to continue using the service."`

---

#### 6. List Chats (Guest)

```
GET /api/unregistered-chat/chat
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `search` | string | No | Search by title/description |
| `sort` | string | No | Sort field (same as authenticated) |
| `per_page` | integer | No | Pagination size |

**Response `200 OK`:** (Same structure as authenticated list)

---

#### 7. Create Chat (Guest)

```
POST /api/unregistered-chat/chat
```

**Request Body (multipart/form-data):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_prompt` | string | No* | Text prompt |
| `images[]` | file (array) | No* | Image files (max 1MB each) |

*At least one required.

**Response `201 Created`:** (Same structure as authenticated create)

---

#### 8. Get Single Chat (Guest)

```
GET /api/unregistered-chat/chat/{id}
```

Returns the chat only if it belongs to the requesting IP address.

**Response `200 OK`** or **`404 Not Found`**

---

#### 9. Update Chat (Guest)

```
PUT /api/unregistered-chat/chat/{id}
```

**Request Body (multipart/form-data):** Same as authenticated update.

**Response `200 OK`** or **`422`** or **`403 Forbidden`**

---

#### 10. Delete Chat (Guest)

```
DELETE /api/unregistered-chat/chat/{id}
```

**Response `200 OK`:**

```json
{
  "message": "Chat IP address deleted."
}
```

**Response `403 Forbidden`** – If the IP address does not match.

---

### Example cURL Commands

#### Authenticated: Create a Chat with Text Only

```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Authorization: Bearer your-sanctum-token" \
  -H "Accept: application/json" \
  -F "user_prompt=Explain the theory of relativity in simple terms"
```

#### Authenticated: Create a Chat with Image + Prompt

```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Authorization: Bearer your-sanctum-token" \
  -H "Accept: application/json" \
  -F "user_prompt=What is shown in this image?" \
  -F "images[]=@/path/to/photo.jpg"
```

#### Authenticated: Create a Chat with Multiple Images

```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Authorization: Bearer your-sanctum-token" \
  -H "Accept: application/json" \
  -F "images[]=@/path/to/image1.jpg" \
  -F "images[]=@/path/to/image2.png"
```

#### Guest: Create a Chat (No Auth Required)

```bash
curl -X POST http://localhost:8000/api/unregistered-chat/chat \
  -H "Accept: application/json" \
  -F "user_prompt=Hello, who are you?"
```

#### List Authenticated Chats with Sorting

```bash
curl -X GET "http://localhost:8000/api/chat?sort=-created_at&per_page=20" \
  -H "Authorization: Bearer your-sanctum-token" \
  -H "Accept: application/json"
```

#### Search Chats

```bash
curl -X GET "http://localhost:8000/api/chat?search=quantum" \
  -H "Authorization: Bearer your-sanctum-token" \
  -H "Accept: application/json"
```

---

## Database Schema

### `chat` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint (PK) | Auto-incrementing ID |
| `user_id` | varchar (nullable) | FK to `users` table (for authenticated users) |
| `ip_address` | varchar (nullable) | Client IP (for unregistered users) |
| `image_path` | text (nullable) | JSON array of stored image paths |
| `user_prompt` | text (nullable) | User's text prompt |
| `ai_response` | longtext (nullable) | AI-generated response |
| `created_at` | timestamp | Record creation time |
| `updated_at` | timestamp | Record last update time |

---

## Data Flow

```
User Request
     │
     ▼
┌─────────────────────┐
│   Laravel Router    │
│  (routes/api.php)   │
└─────────┬───────────┘
          │
          ▼
┌──────────────────────┐
│   FormRequest        │
│   (Validation)       │
│ - Validates input    │
│ - Checks rate limits │
│ - Manages images     │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│   GroqAiService      │
│   (AI Integration)   │
│                      │
│  ┌─────────────────┐ │
│  │ Fetches Chat    │ │
│  │ History (for    │ │
│  │ context)        │ │
│  └────────┬────────┘ │
│           │          │
│  ┌────────▼────────┐ │
│  │ Builds Messages │ │
│  │ Array (with     │ │
│  │ images encoded  │ │
│  │ as base64)      │ │
│  └────────┬────────┘ │
│           │          │
│  ┌────────▼────────┐ │
│  │ HTTP POST to    │ │
│  │ Groq API        │ │
│  │ (api.groq.com)  │ │
│  └────────┬────────┘ │
│           │          │
│  ┌────────▼────────┐ │
│  │ Returns AI      │ │
│  │ Response Text   │ │
│  └─────────────────┘ │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│   Store in Database  │
│   (chat table)       │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│   Return JSON        │
│   Response           │
└──────────────────────┘
```

---

## Environment Variables Reference

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `APP_NAME` | No | `Laravel` | Application name |
| `APP_ENV` | No | `local` | Application environment |
| `APP_KEY` | **Yes** | – | Laravel application key (generate with `php artisan key:generate`) |
| `APP_DEBUG` | No | `true` | Debug mode (set `false` in production) |
| `APP_URL` | No | `http://localhost` | Application base URL |
| `DB_CONNECTION` | No | `mysql` | Database driver |
| `DB_HOST` | No | `127.0.0.1` | Database host |
| `DB_PORT` | No | `3306` | Database port |
| `DB_DATABASE` | **Yes** | `apichatbot` | Database name |
| `DB_USERNAME` | **Yes** | `root` | Database username |
| `DB_PASSWORD` | No | – | Database password |
| `GROQ_API_KEY` | **Yes** | – | Your Groq API key for AI completions |
| `FLW_CLIENT_ID` | No* | – | Flutterwave client ID (for payments) |
| `FLW_CLIENT_SECRET` | No* | – | Flutterwave secret key |
| `FLW_ENCRYPTION_KEY` | No* | – | Flutterwave encryption key |

\* Required only if using the subscription payment feature.

---

## Testing

Run the test suite with Pest PHP:

```bash
php artisan test
```

Or with vendor binary:

```bash
./vendor/bin/pest
```

---

## Artisan Commands

The project includes a custom Artisan command for subscription management:

```bash
# Check and expire subscriptions that have passed their expiry date
php artisan app:check-expired-subscriptions
```

---

## Security

- **Sanctum Token Authentication** – All authenticated endpoints are protected with Laravel Sanctum.
- **Rate Limiting** – Authenticated routes are throttled to 6 requests per minute.
- **IP-Based Access Control** – Guest chat ownership is verified via IP address matching.
- **User Ownership Verification** – Authenticated chat operations verify the requesting user owns the resource.
- **Image Validation** – Uploaded images are validated for type (jpg, jpeg, png, gif, svg) and size (max 1MB).
- **Input Validation** – All inputs are validated through Laravel FormRequest rules.

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).