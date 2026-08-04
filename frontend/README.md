# Wayne's Playground Frontend

這是 Wayne's Playground 的 Vue 3／Vite frontend。完整的產品背景、Docker 啟動方式、API base URL 與發佈邊界請先讀 repo 根目錄的 `README.md`；本檔只保留 frontend 開發需要的資訊。

## 目前責任

- Vue 3、Vue Router、Pinia、Axios。
- auth：註冊、登入、登出、目前帳號與 profile nickname。
- access：管理角色、權限與使用者角色指派。
- home：從 `/api/modules` 取得可見模組並顯示首頁卡片。
- lottery：威力彩模擬與 Reverb-backed 1v1 對戰。
- tarot：引導式三張牌問答。
- wishes：公開許願板與依權限顯示的管理操作。
- lab：程式碼仍保留，但目前沒有匯入主 router，且 backend module metadata 設為 disabled，不應視為公開功能。

## 目錄邊界

```text
src/core/api/          Axios HTTP client
src/core/layouts/      共用 layout
src/core/realtime/     Laravel Echo／Reverb client
src/core/router/       主 router 與權限 guard
src/modules/<name>/    該模組的 views、components、services、stores、routes
src/assets/            靜態資產
```

目前主 router 會匯入 `auth`、`access`、`home`、`lottery`、`tarot` 與 `wishes`；不要只新增檔案就假設路由已公開，需確認 route 匯入與 permission metadata。

## 開發與驗證

從 repo 根目錄執行：

```bash
make up
make npm-install
```

或在 frontend 目錄直接執行：

```bash
cd frontend
npm install
npm run dev
npm run build
```

目前 frontend `package.json` 提供 `dev`、`build`、`preview`，沒有獨立 lint 或 type-check script。若 build 因 `node_modules` 缺少已宣告套件失敗，先執行 `npm install`；不要把未安裝依賴誤判為 Vue 程式碼錯誤。

API base URL 由 `VITE_API_BASE_URL` 提供；Reverb 使用 `VITE_REVERB_*` 相關環境變數。實際值由根目錄 `.env` 與 `docker-compose.yml` 注入，元件內不要寫死 localhost API。

## 變更流程

新增前端功能時，依根目錄 `AGENTS.md` 與 `docs/codex/git-review-release-protocol.md` 建立獨立分支；在 `src/modules/<name>/` 維持模組邊界，將 routes 匯入 `src/core/router/index.js`，需要權限時同步後端 permission metadata，並至少執行 `cd frontend && npm run build`。不把視覺偏好或瀏覽器互動結果誤寫成 build 已驗證。
