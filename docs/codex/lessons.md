# 已驗證教訓

這裡只保存可重現、可泛化且有證據的候選教訓；一次性直覺與未核對的猜測不升格。核心規則仍以 `AGENTS.md` 與其路由文件為準。

## L-001 Docker 整合入口可能被 sandbox 權限阻塞

- 日期／狀態：2026-08-31；promoted（本 repo sandbox 與隔離測試入口證據）
- 觸發：執行需要 Docker socket 的 `make test`；2026-07-24 的 target 是 `docker compose exec php php artisan test`，2026-08-31 已改為 `docker compose run --rm --no-deps ... php php artisan test`。
- 證據：Docker CLI 版本 29.1.2 存在，但 Docker socket 回傳 `operation not permitted`；同一 session `cd backend && php artisan test` 通過 6 tests／807 assertions。
- 影響：容易把 Docker 權限阻塞誤報為產品測試失敗，或把 host／容器 PHPUnit 誤報為 PostgreSQL／Redis 整合通過。
- 暫時處理：先執行既有 host PHP 測試；取得 sandbox 外權限後重跑正式 `make test`。兩個入口都強制使用 SQLite `:memory:`，但 host 結果仍不能替代容器執行路徑。
- 驗證：2026-08-31 sandbox 內 `make test` 因 socket 權限失敗；獲准後的一次性容器通過 55 tests、1 skipped、1146 assertions，host 通過相同數量。惡意設定快取指向 `local + pgsql + playground` 時，guard 以 exit 2、0 assertions 中止。
- 泛化邊界：適用於本 repo 的 Docker 驗證；不代表每台主機或未來 sandbox 都會被拒絕。
- 升格決定：已同步到 rubric、README、Makefile 與測試 guard；lesson 保留歷史觸發與 sandbox 邊界，不再把容器 PHPUnit 當成 PostgreSQL／Redis 整合。

## L-002 frontend 依賴安裝狀態會阻塞 build
- 日期／狀態：2026-08-04；provisional
- 觸發：在 `frontend/` 執行 `npm run build`，目標是確認目前 Vue／Vite frontend 可建置。
- 證據：exit code 1；Vite 無法解析 `laravel-echo`，`frontend/package.json` 與 `package-lock.json` 宣告該依賴，但現有 `node_modules` 沒有安裝。
- 影響：容易把依賴未安裝誤報為 frontend 原始碼錯誤，也可能把沒有跑過的 build 寫成通過。
- 暫時處理：保留 build 失敗；用 `npm ls laravel-echo` 與 package manifest 分開確認依賴狀態，未自行執行需要 registry 的 `npm install`。
- 驗證：2026-08-04 尚缺安裝後結果；2026-09-10 同日治理審查已確認依賴存在且 build exit 0，命令與範圍見 [校正證據](evidence/2026-09-10-governance-review/report.md)。原事件已不再是當前阻塞，不代表未來依賴一定就緒。
- 泛化邊界：適用於本 repo 的依賴未安裝情境；不代表 lockfile 或 package registry 本身有問題。
- 升格決定：先維持 provisional lesson；若不同 session 重現且需要固定化，提案更新 frontend README／Makefile，不直接修改核心完成門檻。
