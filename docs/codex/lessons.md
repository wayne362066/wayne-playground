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
