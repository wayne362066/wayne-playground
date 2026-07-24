# 給未來 Session 的信

日期：2026-07-24

這個 repo 的 Codex 制度已從零建立。請把「未確認」當成真的未知，不要用一次成功的命令或工具名稱推導永久能力。

## 三件使用者未提出，但現在最重要的事

1. Git 現在有 `develop...origin/develop` 與可用 commit 基線。所有功能必須從使用者確認的 `develop` 基底建立獨立 `feature/` 分支；每次 push 都以精確 SHA／remote／ref 重新送審。功能審核完成後 Codex 最多只能自主合併至 `develop`，合併後的 `develop` push 仍要重新送審；正式 `main` 只由使用者親自合併。push、審核或整合批准都不等於發佈批准。
2. `make test` 的 Docker Compose 路徑在普通 sandbox 會被 socket 權限阻塞；本輪已在獲准的 sandbox 外重跑並通過 6 tests／807 assertions。host `php artisan test` 仍不能單獨代表 PostgreSQL／Redis／Compose 整合通過；未來若普通 sandbox 再阻塞，先記錄錯誤並只在獲准條件下重跑正式入口。
3. repo 沒有 CI、root lint/type-check/format 或統一跨 backend/frontend 驗證入口；不要把本次治理文件完成誤解成專案工程驗證已完善。若要補入口，先由使用者決定是否擴大到 Makefile／CI，並另立任務。

## 最可能的退化方式、早期訊號與預防法

| 退化 | 早期訊號 | 預防 |
| --- | --- | --- |
| `AGENTS.md` 變成長手冊 | line count 持續增加、每個任務都被要求讀所有 docs、路由重複 | 只保留常駐規則；長內容移到按需文件；每次改核心規則先做引用與篇幅檢查 |
| 虛假 green | 回報只有「完成」沒有命令／exit code；只跑一層測試；把替代測試寫成整合通過 | 使用 rubric 的狀態定義；將通過／失敗／未驗證分欄；強制列出未解風險 |
| 無限派工／驗證迴圈 | 同一命令或 prompt 第三次重試、agent 回報越來越長、沒有寫入路徑 | 兩次相同策略失敗就換路；固定四欄；長產物落 `docs/codex/evidence/`；最多兩輪對抗修正 |
| 能力漂移 | 文件提到的 model／effort／skill／connector 不在當前工具清單；命令不在 Makefile／package.json | session 開始重新探測；manifest 不等於可呼叫；過時能力標未確認並寫回 lesson |
| 規則被一次性事故污染 | 為單一檔案或單次偏好增加常駐例外，開始互相矛盾 | 先寫 `lessons.md`；只有可重現、可泛化、有證據且有邊界才提案升格 |

## 未確認的環境能力

- 本 session 的主模型名稱與 reasoning effort。
- `multi_agent_v1` 的實際 concurrency 上限、agent 執行時的工作區隔離與跨 session 保留時間。
- 普通 sandbox 是否能直接使用 Docker daemon；Docker CLI 已存在，但本 session 直接 socket 存取被拒絕，獲准外部條件可通過。
- Composer 主機命令（未找到）、`gh`（未找到）、CI、remote 認證／寫入權限與任何正式 pipeline。
- 磁碟上的 skill manifest 是否在下一 session 被載入／可呼叫；recommended plugins 是否會安裝與各 connector 是否已登入。
- Codex thread／agent 狀態是否有 repo 之外的長期持久化；已確認的持久化只有這些 repo 文件。

## 已完成的補查

- 獲准的 sandbox 外 `make test` 已通過 6 tests／807 assertions；普通 sandbox 若再次阻塞，仍須保留「Compose 未驗證」而不是改寫成通過。

## 未完成事項、原因與下一步

- fresh-context 對抗審查：本輪以 `fork_context: false` 實際啟動兩次只讀 `multi_agent_v1` agent（agent ids `019f92d7-23e2-77d1-9cf8-3bd0974c9fab`、`019f92de-76aa-7721-9e81-19e04ecc5d80`），兩次都在回報前逾時並以 `close_agent` 關閉，沒有取得 findings；依相同策略失敗兩次規則已停止重試，不能把主模型機械自查當作獨立驗證。下一 session 使用 `adversarial-review-prompt.md` 重跑，並納入 `git-review-release-protocol.md`，最多兩輪修正。
- 是否補充 root verification target：需要使用者授權／產品範圍決策，本輪不擅自改 Makefile。
- 本輪沒有修改業務程式碼；若未來治理規則要求業務改動，另開任務並依 backend／frontend／跨層最低驗證。

## 明天開始的最短用法

先讀 `AGENTS.md`，再依任務類型只讀一份路由文件；需要派工才讀 templates／playbook，需要判斷才讀 rubric，需要改制度才讀 maintenance。開始與收尾都查實際指示檔、工具／模型能力與 `git status`。回報只用「結論、檔案／行號、驗證、未解風險」四欄；遇到未知或兩次同策略失敗就停止猜測、換路或升級。
