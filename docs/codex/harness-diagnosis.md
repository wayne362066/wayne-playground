# Harness 快速診斷

日期：2026-07-24

本診斷只根據目前工作區與已暴露工具的可觀察證據，不把未查證的模型、服務或流程寫成既定能力。後續制度文件必須對應這三項問題；若 repo 結構改變，先重跑本診斷的證據命令。

## P0：錯誤或虛假完成的驗收鏈不完整

### 實際證據

- `git status --short --branch` 顯示 `No commits yet on main`，並列出根目錄、`backend/`、`frontend/` 等檔案全為未追蹤；目前沒有可用的 commit 基線或 CI diff gate。
- 根目錄 `Makefile` 只有 `test`，其內容是 `docker compose exec php php artisan test`；沒有 root `build`、前端 build、lint、type-check 或 format 驗證 target。
- README 的測試段落只有 `make test`，新增模組流程才額外提到 `npm run build`；兩者沒有被一個可重複的整合檢查串起來。
- 已觀察到 `backend/vendor/`、`frontend/node_modules/`、`frontend/dist/` 與二進位 `frontend/src/assets/hero.png` 在工作區；若掃描或 diff 未排除生成物，結果容易失真。
- 實跑結果顯示執行條件重要：sandbox 內 README 指定的 `make test` 因 Docker socket `operation not permitted` 失敗；在獲准的 sandbox 外重試後通過 6 tests／807 assertions。主機 `cd backend && php artisan test` 與 `cd frontend && npm run build` 也通過。第一次失敗是環境阻塞，不是產品測試失敗；最後一次才是目前可取得的 Compose 後端測試證據。

### 原因

目前「程式能跑」與「請求已完成」沒有明確的證據門檻。驗證依賴 Docker 執行環境與執行權限，且 Make target 只覆蓋後端整合入口；本次已在獲准條件下補到 Compose 測試證據，但普通 sandbox 仍可能阻塞。Git 尚無歷史也讓修改範圍、備份與回復狀態不透明。

### 影響

較弱模型可能只跑到一個可用命令就宣稱完成，漏掉前端建置、API 行為、設定／文件引用或未追蹤檔案；也可能把失敗的環境命令誤報成產品缺陷，或把未驗證的結果寫進交接。

### 具體修法

1. 在 `AGENTS.md` 強制以「範圍、變更、驗證命令、結果、未驗證項」作為完成回報；未執行或失敗的檢查不得標成通過。
2. 在治理 rubric 中按任務類型定義最低驗證：文件／治理做機械引用檢查；後端改動優先跑 `make test`，若普通 sandbox 被 Docker 權限阻塞，先記錄阻塞並在獲准條件下重跑；若仍無法使用 Docker，才另跑 `cd backend && php artisan test` 並明確標記 Compose 未驗證；前端改動跑 `npm run build`；跨層改動兩者都跑。
3. 把生成物與依賴目錄列為掃描排除項，並要求以 `git status`／`git diff --stat`（在有基線後）確認範圍。
4. 不自行假設 CI、Docker、subagent 或模型可用；先探測，失敗後按錯誤分類升級或交接。

### 驗證方式

- 每次工作結束重新讀取新增／修改文件，檢查所有引用路徑存在。
- 執行適用命令並原樣記錄 exit code；命令不可用、權限不足、服務未啟動等列入「未驗證／阻塞」，不得列入通過。
- 用 `git status --short`、`git diff --check`（有 diff 時）及針對性 `rg` 檢查確認沒有漏改、placeholder 或虛構工具。

## P1：任務容易失焦

### 實際證據

- `find` 與 `rg --files` 顯示 repo 沒有 `AGENTS.md`、`AGENTS.override.md`、`.github/`、`scripts/` 或現有 `docs/` 治理入口。
- 根目錄 README 同時包含架構、安裝、Docker、部署、備份、FAQ 與新增模組流程；`backend/README.md` 與 `frontend/README.md` 仍是框架原始樣板，沒有本 repo 的修改邊界或驗收規則。
- README 明確寫本階段不包含 CI/CD、Kubernetes、微服務與正式高可用，但沒有一份 Codex 任務入口把「本次要求／禁止事項／完成條件」固定下來。

### 原因

沒有分層規則或任務路由。模型必須從大量產品背景自行猜測目前工作屬於文件、後端、前端、基礎設施還是治理，且沒有固定的停止條件；這會把上下文花在不相關的程式與框架樣板上。

### 影響

下游模型可能改動業務程式碼來「順手修正」問題，或在未被要求的 Docker／部署範圍擴張；平行工作也可能重複掃描同一棵樹，回報冗長但不可整合。

### 具體修法

1. 建立精簡的 root `AGENTS.md` 作為入口與路由器，只放每次都要遵守的範圍、驗收、升級及文件索引。
2. 將調度守則、rubric、模板與維護協議放在 `docs/codex/`，只有任務觸發時才讀取。
3. 先定義任務類型與禁止範圍；沒有明確授權時不改業務程式碼、不新增正式流程、不安裝外部插件。
4. 派工只交付獨立、可驗收的切片，長產物落檔，主模型只整合結論與證據。

### 驗證方式

- 任務開始時能從 `AGENTS.md` 在一個入口找到對應文件與最低驗證命令。
- 每份派工回報都能指出目標、檔案範圍、禁止事項、驗收結果與未解風險；缺任一欄即退回補充。
- 以 `rg` 檢查治理文件中的路徑與命令，不需要載入歷史或依賴目錄才能理解流程。

## P1：token／context 浪費

### 實際證據

- 工作區包含 `backend/vendor/`、`frontend/node_modules/`、`frontend/dist/` 與圖片資產；未指定排除規則的 `find`／全文讀取會把依賴、編譯輸出與二進位資料帶入上下文。
- 根 README 約 260 行，涵蓋多種日常與部署情境；兩份子 README 大多是框架樣板，對目前 Codex 任務價值低。
- 目前沒有短入口、按需文件、掃描清單或派工回報格式，工具能力盤點也不在 repo 內留痕。

### 原因

資訊沒有按決策價值分層。模型容易先讀整棵樹、整份 README 或二進位／依賴檔，再開始定義任務；沒有規則要求把大輸出壓縮成摘要或把長結果落檔。

### 影響

可用 context 被低價值內容消耗，真正重要的路由、測試與風險證據被截斷；長 session 更容易遺失完成條件，增加重複掃描、漏驗證與錯誤判斷。

### 具體修法

1. `AGENTS.md` 固定先讀任務相關文件，再用 `rg --files`／`rg`，預設排除 `vendor`、`node_modules`、`dist`、`storage`、`.git` 與二進位資產。
2. 規定工具輸出採「先摘要、再按需展開」；超過需要整合的長內容落到 `docs/codex/` 或任務專用暫存路徑，只回傳路徑、摘要與行號。
3. 使用有明確觸發條件的模板與 rubric，避免每個 session 重新發明驗收流程。
4. 只有需要時才啟用 subagent；派工結果預設回傳四欄，不把整段探索內容帶回主 context。

### 驗證方式

- 執行掃描時確認命令含排除項，且輸出不包含依賴與二進位內容。
- 用 `wc -l`／`git diff --stat` 檢查回報與變更是否維持在任務所需規模；長報告必須有落檔路徑。
- 新 session 只讀 `AGENTS.md` 加上任務觸發的單一制度文件，即可找到執行方式、停止條件與驗證標準。

## 後續文件對應

| 診斷問題 | 主要制度 | 機械檢查 |
| --- | --- | --- |
| 錯誤／虛假完成 | `AGENTS.md`、`decision-rubric.md`、`maintenance-protocol.md` | 驗證命令與 exit code、引用路徑、git 狀態 |
| 任務失焦 | `AGENTS.md`、`dispatch-playbook.md`、`dispatch-templates.md` | 目標／範圍／禁止事項／停止條件欄位齊全 |
| context 浪費 | `AGENTS.md`、`dispatch-playbook.md` | 掃描排除清單、長產物落檔、四欄摘要回報 |

## 查證限制

- 根目錄尚無 commit，因此無法用歷史 diff 驗證修改前後差異；本次新增文件會以 `git status`、檔案 read-back 與 `git diff --no-index /dev/null` 方式檢查。
- Docker daemon 與 Compose 後端測試已在獲准的 sandbox 外條件確認可用並通過；普通 sandbox 的直接 socket 權限、其他服務／網路情境與未來 session 狀態仍未確認。
- 尚未確認是否存在未暴露於本工具清單的其他模型或正式委派語法；制度只引用已暴露的 `multi_agent_v1` 能力，並保留未確認標記。
