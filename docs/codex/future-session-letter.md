# 給未來 Session 的信

日期：2026-09-10

這個 repo 已有制度入口；2026-09-10 修正可觀察的執行缺口，沒有重建整套制度。當日基線、能力、測試與審查進度集中在 [治理校正證據](evidence/2026-09-10-governance-review/report.md)，不是所有 session 的能力保證。

## 三件使用者未提出，但現在最重要的事

1. Git 現在有 `develop...origin/develop` 與可用 commit 基線。所有功能必須從使用者確認的 `develop` 基底建立符合 `<type>/<english-kebab-case-summary>` 的獨立分支；Codex 一般 commit 使用 `<type>(<scope>):<中文摘要>`；每次 push 都以精確 SHA／remote／ref 重新送審。功能審核完成後 Codex 最多只能自主合併至 `develop`，合併後的 `develop` push 仍要重新送審；正式 `main` 只由使用者親自合併。push、審核或整合批准都不等於發佈批准。
2. `make test` 與 host `cd backend && php artisan test` 都必須強制使用 SQLite `:memory:`，並由 `backend/tests/TestCase.php` 在 migration 前驗證實際設定；它們都不代表 PostgreSQL／Redis 整合通過。需要跑相關驗證時才查 Docker／依賴狀態；不沿用舊結果，也不為純文件任務強制重跑產品測試。
3. repo 沒有 CI、root lint/type-check/format 或統一跨 backend/frontend 驗證入口；不要把本次治理文件完成誤解成專案工程驗證已完善。若要補入口，先由使用者決定是否擴大到 Makefile／CI，並另立任務。

## 最可能的退化方式、早期訊號與預防法

| 退化 | 早期訊號 | 預防 |
| --- | --- | --- |
| `AGENTS.md` 變成長手冊 | line count 持續增加、每個任務都被要求讀所有 docs、路由重複 | 只保留常駐規則；長內容移到按需文件；每次改核心規則先做引用與篇幅檢查 |
| 虛假 green | 回報只有「完成」沒有命令／exit code；只跑一層測試；把替代測試寫成整合通過 | 使用 rubric 的狀態定義；將通過／失敗／未驗證分欄；強制列出未解風險 |
| 無限派工／驗證迴圈 | 同一失敗命令第三次重試、把等待逾時當失敗重派、沒有總執行上限 | 按 playbook 區分等待與失敗；固定回報；遵守輸出授權與最多兩輪對抗修正 |
| 能力漂移 | 舊能力名稱被複製回常規；工具 schema 不接受文件參數 | 使用前查當前 schema；歷史清單留 evidence，不每次全面盤點 |
| 規則被一次性事故污染 | 功能分支混入 lessons、為單次偏好增加常駐例外 | 先回報候選教訓，已授權治理工作才寫 `lessons.md`；有可泛化證據才提案升格 |

## 未確認的環境能力

- 未由回應明示的主模型／effort、實際 agent 並行上限、寫入隔離與跨 session 保留時間。
- 本次未重驗的 Docker 執行權限、Composer／gh、remote 認證與正式 pipeline；不把過往「找不到」或「被拒絕」當現況。
- 下一 session 的 skill／plugin／connector 載入、登入與權限；manifest 只證明檔案存在。
- repo 之外的持久化未在本輪驗收；後續必須以已落檔制度和可查證結果交接，不依賴舊 agent 還活著。

## 已完成的補查

- 同日修訂前 host backend 與 frontend build 通過，`laravel-echo` 已安裝；確切數量與命令見上方證據。產品內容變更後依 rubric 重驗，不能把這次結果當成新變更通過。
- 同日修訂前 fresh-context reviewer 已成功回報 6 項 P2，主模型核對後納入修正。先前兩次 60 秒等待後關閉 agent，只證明未取得報告，不足以證明執行失敗。
- 修訂後 fresh-context 審查已完成：reviewer 未發現有證據的 P1／P2，五個固定情境取得一致答案；機械檢查與差異可回復性檢查通過。範圍、命令與 reviewer 紀錄見上方證據，不外推所有模型可靠度。

## 未完成事項、原因與下一步

- 低成本模型的實際遵循率未驗證：已有 `adversarial-review-prompt.md` 的固定情境；使用者選定模型／effort 後可重做，保留實際答案，不從高階模型結果外推。
- 統一跨 backend／frontend 驗證入口、frontend 自動化互動測試、PostgreSQL／Redis 隔離整合驗證仍不在本輪範圍；有需求再立明確任務，不削弱既有 SQLite 測試安全防護。
- 本輪沒有修改業務程式碼；若未來治理規則要求業務改動，另開任務並依 backend／frontend／跨層最低驗證。

## 明天開始的最短用法

從 repo 開新 session，先確認載入 `AGENTS.md` 與 Git 狀態，再讀任務觸發的文件，可能不只一份：功能至少需 Git 協議與 rubric；派工才加 playbook／templates；改制度才加 maintenance。只在使用前查相關工具能力。回報結論、檔案／行號、驗證、未解風險；查證後仍不確定就問，不把舊批准、舊測試或等待逾時當新證據。
