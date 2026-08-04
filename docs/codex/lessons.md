# 已驗證教訓

這裡只保存可重現、可泛化且有證據的候選教訓；一次性直覺與未核對的猜測不升格。核心規則仍以 `AGENTS.md` 與其路由文件為準。

## L-001 Docker 整合入口可能被 sandbox 權限阻塞

- 日期／狀態：2026-07-24；confirmed（本 repo sandbox 條件證據）
- 觸發：執行 `make test`，其 target 是 `docker compose exec php php artisan test`。
- 證據：Docker CLI 版本 29.1.2 存在，但 Docker socket 回傳 `operation not permitted`；同一 session `cd backend && php artisan test` 通過 6 tests／807 assertions。
- 影響：容易把環境阻塞誤報為產品測試失敗，或把 host 測試誤報為 Compose／PostgreSQL／Redis 整合通過。
- 暫時處理：先執行既有 host PHP 測試與 frontend build；取得 sandbox 外權限後重跑正式 `make test`，不把替代命令當正式證據。
- 驗證：sandbox 內 `make test` 失敗；獲准的 sandbox 外重跑 `make test` 通過 6 tests／807 assertions。主機替代測試與正式 Compose 測試均已分開記錄。
- 泛化邊界：適用於本 repo 的 Docker 驗證；不代表每台主機或未來 sandbox 都會被拒絕。
- 升格決定：留在 lesson，因為是否新增 host fallback target 會改變專案介面，需使用者決定；rubric 已收錄先修正執行條件、再把替代驗證標成不等價的規則。

## L-002 frontend 依賴安裝狀態會阻塞 build
- 日期／狀態：2026-08-04；provisional
- 觸發：在 `frontend/` 執行 `npm run build`，目標是確認目前 Vue／Vite frontend 可建置。
- 證據：exit code 1；Vite 無法解析 `laravel-echo`，`frontend/package.json` 與 `package-lock.json` 宣告該依賴，但現有 `node_modules` 沒有安裝。
- 影響：容易把依賴未安裝誤報為 frontend 原始碼錯誤，也可能把沒有跑過的 build 寫成通過。
- 暫時處理：保留 build 失敗；用 `npm ls laravel-echo` 與 package manifest 分開確認依賴狀態，未自行執行需要 registry 的 `npm install`。
- 驗證：重新執行 `npm run build` 仍須在依賴安裝完成後確認；目前尚缺安裝後 build 結果。
- 泛化邊界：適用於本 repo 的依賴未安裝情境；不代表 lockfile 或 package registry 本身有問題。
- 升格決定：先維持 provisional lesson；若不同 session 重現且需要固定化，提案更新 frontend README／Makefile，不直接修改核心完成門檻。
