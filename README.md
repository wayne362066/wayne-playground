# Wayne's Playground

一個可長期擴充的個人 Playground 平台，用來收集作品、Side Project、技術實驗與小型工具。目前包含動態模組首頁、威力彩模擬器、塔羅與可追蹤規劃狀態的許願板；Lab 程式碼暫時保留，但不顯示在介面與路由中。

專案採 Modular Monolith：所有後端模組共用一個 Laravel 應用，所有前端模組共用一個 Vue 應用；模組各自維護路由與功能邏輯，避免拆成難以維護的微服務。

## 技術架構

- Backend：PHP 8.3、Laravel 12、REST API、Laravel Queue、Laravel Scheduler
- Frontend：Vue 3、Vite、Vue Router、Pinia、Axios
- Data：PostgreSQL 15、Redis 7
- Infrastructure：Docker Compose、Nginx、PHP-FPM、Node 22、Composer

瀏覽器 → Vue (`localhost:5173`) → Laravel API (`localhost:8080/api`)。Nginx 將 PHP 請求交給 PHP-FPM；Queue Worker 與 Scheduler 使用相同 Laravel 程式碼，但在獨立容器運行。

## 需求環境

macOS、Windows（建議 WSL2）或 Linux 均可。只需要 Docker Desktop 或 Docker Engine、Docker Compose v2、Make 與 Git。不需要在主機安裝 PHP、Composer、Node、PostgreSQL 或 Redis。

## 快速安裝

```bash
git clone https://github.com/wayne362066/wayne-playground.git
cd wayne-playground
cp .env.example .env
make build
make up
make composer-install
make npm-install
make migrate
make seed
```

若 `APP_KEY` 留白，執行以下指令，將輸出的完整 `base64:...` 值貼回根目錄 `.env` 的 `APP_KEY`：

```bash
make key-generate
docker compose restart php queue-worker scheduler
```

啟動後：

- 前端：<http://localhost:5173>
- Backend / Nginx：<http://localhost:8080>
- 健康檢查：<http://localhost:8080/up>
- 模組 API：<http://localhost:8080/api/modules>

## 常用操作

```bash
make up                 # 啟動全部服務
make down               # 停止容器，不刪資料 volume
make build              # 建置 PHP image
make restart            # 重新啟動
make logs               # 追蹤服務 log
make ps                 # 查看服務狀態
make shell              # 進入 PHP container
make composer-install   # 安裝 PHP 套件
make npm-install        # 安裝前端套件
make migrate            # 執行 migration
make migrate-fresh      # 重建資料表並 seed（會刪除資料）
make seed               # 執行 seeder
make test               # 執行 Laravel 測試
make queue-restart      # 平順重啟 queue workers
```

Windows 可在 WSL2 / Git Bash 使用 Make，也可直接執行 Makefile 內對應的 `docker compose` 指令。

## Docker 服務

| 服務 | 用途 | 對外連接 |
| --- | --- | --- |
| `nginx` | Laravel HTTP 入口 | `8080` |
| `php` | PHP 8.3 FPM / Artisan / Composer | 僅內部 `9000` |
| `postgres` | PostgreSQL 15 | 僅本機 `127.0.0.1:5432` |
| `redis` | Cache、Session、Queue | 僅本機 `127.0.0.1:6379` |
| `node` | Vite 開發伺服器 | `5173` |
| `queue-worker` | Laravel Redis Queue | 無 |
| `scheduler` | Laravel Scheduler | 無 |

資料庫與 Redis 使用 named volumes，不會因 `make down` 消失。只有明確執行 `docker compose down -v` 才會刪除它們。

## Migration 與 Seeder

第一版的模組清單由 `backend/config/modules.php` 提供，方便快速新增與版本控制。`modules` migration 與 `ModuleSeeder` 已一併建立，讓未來切換資料庫管理時不必改 schema。

```bash
make migrate
make seed
```

目前 API 透過 `ModuleRegistry` 讀取設定檔。未來需要後台管理時，可將 Registry 改為查詢 `Module` model；Controller、Resource 與前端 API 格式不需要改動。

## 前端啟動

`make up` 會啟動 Vite 並監看檔案變更。API base URL 由根目錄 `.env` 的 `VITE_API_BASE_URL` 控制，元件內不包含固定 API 網址。

也可直接在主機執行：

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

正式建置：

```bash
cd frontend
npm run build
```

## 測試

```bash
make test
```

測試涵蓋模組 API、威力彩第一區固定 6 個號碼、不重複、範圍與排序、第二區範圍、三種模擬模式，以及輸入值與損益計算。

## 專案目錄

```text
.
├── backend/
│   ├── app/Core/                  # 共用 API、服務與基礎能力
│   ├── app/Modules/
│   │   ├── Home/                  # 模組清單
│   │   ├── Access/                # 角色、權限與異動稽核
│   │   ├── Lottery/               # 威力彩 API
│   │   └── Wishes/                # 許願板 API、資料與權限邊界
│   ├── config/modules.php
│   └── database/                  # migrations / seeders
├── frontend/src/
│   ├── core/                      # API、router、layout、共用頁面
│   └── modules/                   # access / home / lottery / tarot / lab
├── docker/nginx/
├── docker/php/
├── docker-compose.yml
└── Makefile
```

## 角色與細項權限

系統以 `guest`、`member`、`admin` 三個系統角色起步，角色只是權限集合；後端 Policy 與 permission middleware 才是安全邊界。前端會依相同權限隱藏模組、路由與操作，API 收到未授權請求仍會回傳 `403`。

migration 執行時會把既有帳號指定為 `admin`，之後註冊的帳號固定取得 `member`。管理員可在 `/admin/access` 建立自訂角色、調整角色權限、指派使用者角色及查看權限異動紀錄；系統會阻止移除最後一位具備 `access.manage` 的使用者。

許願板仍允許訪客檢視公開內容與投稿，但管理檢視、內容編輯、狀態調整、審核、封存、恢復及事件歷史各自使用不同權限。

## 新增模組流程

以新增 `Minecraft` 為例：

1. 在 `backend/app/Modules/Minecraft` 建立實際需要的 `Controllers`、`Services`、`Requests`、`Resources` 與 `Routes/api.php`。
2. 在 `backend/config/modules.php` 增加 metadata；`key` 不可重複，並設定 `status` 與 `sort_order`。
3. 若需要資料，將 migration 放在 `backend/database/migrations`，model 可放在模組的 `Models`。
4. 在 `frontend/src/modules/minecraft` 建立 `views`、`components`、`services`、`stores`、`routes.js`。
5. 將 routes 匯入 `frontend/src/core/router/index.js`。
6. 新增 Feature 或 Unit Test，執行 `make test` 與 `npm run build`。

模組不可直接操作另一模組的內部類別。真正共用的內容移到 `Core`；若日後需要協作，優先使用明確介面或事件。

## API 與錯誤格式

成功：

```json
{
  "success": true,
  "message": "操作成功",
  "data": {}
}
```

驗證失敗回傳 HTTP 422 與 `errors`；不存在的 API 回傳 HTTP 404。正式環境請將 `APP_DEBUG=false`，避免錯誤細節外洩。Laravel 錯誤寫入 `backend/storage/logs`，也可用 `make logs` 觀察容器輸出。

## PostgreSQL 備份與還原

```bash
mkdir -p backups
docker compose exec -T postgres pg_dump \
  -U playground -d playground -Fc > backups/playground.dump
```

還原前先確認目標資料庫，因為 `--clean` 會刪除既有同名物件：

```bash
docker compose exec -T postgres pg_restore \
  -U playground -d playground --clean --if-exists < backups/playground.dump
```

換機時，Git repository 可重建程式與服務；正式資料另行搬移 PostgreSQL dump 與未來的上傳檔案。Redis 只作 cache、session 與 queue，不視為主要永久資料。

## Ubuntu Server 部署注意事項

目前 compose 設定以開發為主。移至 Ubuntu 時：

- 設定強密碼與獨立 production `.env`，使用 `APP_ENV=production`、`APP_DEBUG=false`
- 不提交 `.env`、Token、SSL private key 或任何正式憑證
- 執行 `composer install --no-dev --optimize-autoloader`
- 前端執行 `npm ci && npm run build`，以 Nginx 提供靜態檔
- 定期執行 PostgreSQL dump，並把備份同步到另一台機器或離線儲存
- 移除 PostgreSQL、Redis 的 host ports，讓它們只留在內部 network
- 可在 Nginx 前方加入 Cloudflare Tunnel；程式不依賴固定 IP 或固定網域

本階段不包含 Kubernetes、微服務、正式 CI/CD、高可用或正式 Cloudflare Tunnel。

## 暫時對外測試（選用）

安裝 `cloudflared` 後可建立 Quick Tunnel：

```bash
cloudflared tunnel --url http://localhost:8080
```

這只適合短時間展示或手機測試。若要展示 Vue 開發介面，可改為 `http://localhost:5173`，並確認 API 的 CORS 與公開 API 網址。

## 常見問題

**Port 已被占用？**

修改 `docker-compose.yml` 左側的 host port，並同步調整 `.env` 中的 URL。PostgreSQL 與 Redis 若不需要從主機連線，可移除 `ports`。

**前端顯示 API 載入失敗？**

執行 `make ps`、`make logs`，確認 `nginx`、`php`、`postgres`、`redis` 都是 running/healthy，並檢查 `VITE_API_BASE_URL`。

**Linux 出現 storage 權限問題？**

在 `.env` 將 `APP_UID`、`APP_GID` 改為 `id -u`、`id -g` 的結果，再執行 `make build && make up`。

**修改 queue 程式後沒有生效？**

執行 `make queue-restart`。

**如何完全清除本機資料？**

`docker compose down -v` 會刪除 PostgreSQL 與 Redis volumes，且不可復原；先做好 dump 再執行。
