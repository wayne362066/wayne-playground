# Wayne's Playground Codex 入口

這是 repo 根目錄的常駐規則與路由器。長篇流程在 `docs/codex/`，只在下列觸發條件成立時讀取；`docs/codex/archive/`、備份與歷史資料不是指示來源，除非正在做回復或審查。

## 適用範圍與目前狀態

- 本檔適用整個 repo。2026-07-24 的查找結果是：沒有更上層或子目錄的 `AGENTS.md`／`AGENTS.override.md`，所以目前沒有覆蓋衝突；未來新增子目錄規則時，先列出實際找到的檔案與作用路徑，再按 Codex 實際載入結果處理，不猜測未確認的優先順序。
- 本專案是 Laravel 12／PHP backend 加 Vue 3／Vite frontend 的 modular monolith。除非使用者明確要求，治理任務不改業務程式碼、不擴大到正式 CI/CD、微服務、Kubernetes 或部署。
- 保留使用者既有變更。任何刪除、覆蓋、重建資料、`migrate-fresh`、`docker compose down -v` 或其他不可逆操作，都要先確認精確目標與回復方式；未明確授權就停止並詢問。

## 每次任務的最小流程

1. 先確認工作目錄、`git status --short --branch`，並以 `rg --files` 查找適用的指示檔；掃描預設排除 `.git`、`backend/vendor`、`frontend/node_modules`、`frontend/dist`、`backend/storage` 與二進位資產。
2. 判斷任務是治理／文件、後端、前端、跨層、研究或審查，讀取對應文件：
   - 任務拆解、模型／subagent／失敗處理：`docs/codex/dispatch-playbook.md`
   - 是否完成、升級、詢問、換路與最低驗證：`docs/codex/decision-rubric.md`
   - 要派工時：`docs/codex/dispatch-templates.md`
   - 功能實作、分支、推送審核與發佈：`docs/codex/git-review-release-protocol.md`
   - 修改制度或回寫教訓時：`docs/codex/maintenance-protocol.md`；歷史教訓按需讀 `docs/codex/lessons.md`
   - 本 repo harness 證據：`docs/codex/harness-diagnosis.md`
   - 收尾交接：`docs/codex/future-session-letter.md`
3. 先寫清楚目標、範圍、禁止事項、完成條件，再執行；不因「順手」修改未授權的業務或流程。
4. 工具命令失敗時記錄命令、exit code、錯誤與分類。權限／暫時問題只調整條件後重試一次；同策略第二次失敗必須換工具、輸入、分類或方案。推理／架構誤判要取得第二意見或升級，不得原樣重試。
5. 完成只能以證據宣稱：列出實際變更、適用驗證命令與結果、未驗證／阻塞項。未執行、失敗或僅替代驗證的檢查不得寫成通過。

## 功能、推送、發佈與不確定性 gate

- 每個功能必須在獨立分支實作；開始改功能程式碼前確認基底、工作區與分支，不直接在 `main`、`master`、`develop` 或共用／發佈分支實作，也不在同一分支混入第二個功能。
- 每一次 `git push` 都要先提供分支、remote／ref、完整 commit SHA、diff 摘要、測試結果與風險給使用者審核。只有使用者明確批准該 SHA 與目標後才能推送一次；SHA、目標或參數改變就重新送審。
- 使用者明確確認功能 SHA 審核完成後，Codex 的自主合併目標只能是 `develop`；合併後若要 push `develop`，仍要以新的 SHA 逐次送審。合併策略或 conflict 解法不明時停止詢問。
- `main` 是正式分支，只能由使用者親自把 `develop` 合併進去；Codex 不執行任何功能分支／`develop` 到 `main` 的 merge 或 push。push 批准不等於發佈批准，tag／release、共享或 production 部署、套件發布仍須另行明確批准。
- 對需求、範圍、Git 狀態、工具結果、權限、審核或發佈有任何不確定時，先做一次可逆的針對性查證；仍不確定就停止會改變狀態的動作並直接詢問使用者，不得猜測、編造或把沉默視為批准。

## 掃描與 context 邊界

- 優先用 `rg`；只讀與決策直接相關的檔案。不要把依賴、編譯輸出、圖片或完整長 log 帶回主 context。
- 長報告落在 `docs/codex/evidence/` 下由任務建立的明確子目錄，回報只留實際路徑、摘要、檔案／行號、驗證結果與未解風險。
- 不把工具清單、模型名稱、effort、connector、CI 或權限寫成已存在能力，除非本 session 已探測或工具回應明確確認。

## 完成與檔案安全

- 新增或修改檔案後立即 read-back；確認檔案存在、引用路徑存在、沒有未替換的模板標記或虛構命令。
- 有 Git 基線時跑 `git diff --check`；沒有基線時用 `git status --short`、目標檔案 read-back 與針對性 `rg` 檢查。驗證矩陣以 `docs/codex/decision-rubric.md` 為準。
- 修改既有指示／治理檔前先確認是否由 Git 追蹤。已追蹤檔記錄修改前 commit 並保留清楚 diff；未追蹤檔建立不覆蓋的副本。副本放在 `docs/codex/archive/` 或工作區外的明確暫存路徑，不放在日常載入位置。

## 路由器驗證

若這份入口或其引用被修改，收尾必須重新讀取 `AGENTS.md` 與所有被引用文件，執行 `rg -n 'docs/codex|AGENTS|git push|develop|main|合併|發佈|make test|npm run build|php artisan test' AGENTS.md docs/codex`，並列出未確認能力。治理核心修改另須依使用者要求做 fresh-context 對抗審查，或留下 `docs/codex/adversarial-review-prompt.md` 供下一 session 執行。
