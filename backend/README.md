# Wayne's Playground Backend

這是 Wayne's Playground 的 Laravel 12 API backend。完整的產品背景、Docker 啟動方式、資料備份與發佈邊界請先讀 repo 根目錄的 `README.md`；本檔只保留 backend 開發需要的資訊。

## 目前責任

- Laravel 12、PHP 8.3、REST API。
- 帳號註冊／登入／訪客 session 與 profile nickname。
- 模組清單、角色與細項權限、權限異動紀錄。
- 威力彩號碼／損益模擬與 Redis-backed 1v1 對戰。
- 許願板公開投稿、管理、狀態歷史與恢復。
- Laravel Reverb 廣播、Redis queue 與 scheduler。

## 目錄邊界

```text
app/Http/Controllers/   HTTP 輸入、授權與 response orchestration
app/Http/Requests/       請求驗證
app/Http/Resources/      API 資源格式
app/Http/Responses/      共用 API response
app/Models/              Eloquent models
app/Services/            業務與共用服務
app/Events/              廣播事件
app/Jobs/                queued jobs
app/Policies/            資源授權
config/modules.php       模組 metadata 的目前 runtime source
config/permissions/      集中式 permission definitions
database/migrations/     PostgreSQL schema
database/seeders/        modules、permissions 與開發帳號同步
routes/api.php            模組 API
routes/web.php            auth 與 broadcasting routes
bootstrap/app.php         Laravel health endpoint `/up`
routes/console.php        scheduler definitions
```

目前 `ModuleRegistry` 讀取 `config/modules.php`；`Module` model、migration 與 `ModuleSeeder` 已存在，但尚未取代設定檔成為模組首頁的 runtime source。

`app/Http/Controllers` 的 orchestration 是新增／重構功能時的目標邊界，不代表現有程式已完全符合：目前 `WishController`、`AccessManagementController` 與 `AuthController` 仍各自包含部分查詢、授權或業務判斷。除非使用者明確要求重構，不因本文件描述而回溯搬移既有邏輯。

## 開發與驗證

從 repo 根目錄執行：

```bash
make up
make migrate
make seed
make test
```

也可在已有 backend dependencies 的主機環境執行：

```bash
cd backend
php artisan test
```

`make test` 與主機測試都會由 `phpunit.xml` 強制使用 SQLite `:memory:`，並在 Laravel 測試基底再次檢查實際解析出的設定；因此 `RefreshDatabase` 只會重建記憶體資料庫，不會碰 PostgreSQL 的應用資料。這些測試不等同 PostgreSQL／Redis 整合驗證。backend 沒有獨立的 lint 或 type-check script；新增或修改 API、migration、queue、scheduler 或服務時，依根目錄 `AGENTS.md` 與 `docs/codex/decision-rubric.md` 執行最低驗證。

## 重要資料邊界

- PostgreSQL 儲存帳號、權限、模組 metadata、許願板與事件歷史。
- Redis 儲存 cache、session、queue 與進行中的 1v1 房間；Redis 重建會使進行中的房間消失。
- 不執行 `migrate-fresh` 或 `docker compose down -v`，除非使用者已確認精確目標與資料回復方式。
