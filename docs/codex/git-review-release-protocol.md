# 功能分支、推送審核與發佈協議

本協議適用於所有功能工作。目標是讓每個功能可獨立審查、回復與發佈，並確保任何 `git push` 或發佈動作都由使用者明確批准。

## 適用起點與歷史相容

自使用者確認本次規則後，本協議適用於 Codex 後續新建的功能分支、一般 commit、push、`develop` 合併與發佈動作；既有分支與 commit 不回溯改名、重寫或判定為不合規。純文件／治理變更不因本節自動被視為功能，但仍受 push、合併與發佈審核 gate 約束。

## 何者算功能

下列任一變更即視為功能：新增或改變 UI／路由／使用者流程、API／資料格式、資料 schema／migration、queue／scheduler 行為、模組業務邏輯、外部整合或部署時的產品行為。純調查、只讀審查、文字修正與治理規則更新不算功能；若分類不確定，停止並詢問使用者。

## 一個功能一個分支

觸發：開始修改任何功能程式碼前。

動作：

1. 執行 `git status --short --branch`、`git branch --show-current`，確認工作區與目前分支。功能分支以使用者確認的 `develop` commit 為基底；本地／遠端 `develop` 是否最新不明時先詢問。
2. 每個功能建立獨立分支，預設命名 `<type>/<english-kebab-case-summary>`：
   - `type` 依工作目的選擇：新功能 `feat`、錯誤修正 `fix`、文件／治理 `docs`、不改行為的重構 `refactor`、測試 `test`、維護 `chore`、建置 `build`、CI `ci`、效能 `perf`、回復變更 `revert`。
   - `/` 後使用簡短英文小寫 kebab-case，只含英文字母、數字與單一連字號分隔，例如 `feat/tarot-reading`、`fix/lottery-prize-amount`、`docs/git-governance`。
   - type、英文簡述、基底分支或既有未提交變更歸屬不明時，先詢問使用者。
3. 不直接在 `main`、`master`、`develop` 或其他共用／發佈分支實作功能。功能分支只包含該功能，不混入另一功能、順手重構或治理變更。
4. 若工作中出現第二個功能，停止擴張；回報目前分支範圍，另建分支處理第二個功能。

停止條件：基底分支不明、工作區已有無法安全歸屬的變更、分支名稱衝突或切換分支可能覆蓋內容時，不執行 checkout／switch／stash／reset，直接詢問使用者。

驗證：新分支名稱必須符合 `^(feat|fix|docs|refactor|test|chore|build|ci|perf|revert)/[a-z0-9]+(-[a-z0-9]+)*$`；回報目前分支名稱、基底 commit、`git status --short` 與功能範圍。任何一項無法確認就不能開始功能實作。

正例：在確認 `develop` 為基底且工作區乾淨後，為 Minecraft 模組建立 `feat/minecraft-module`。

反例（生效起點後新建）：建立 `feature/新增塔羅功能`，其 type 不在規則內且摘要使用中文；或在 `develop` 直接修改 Lottery 與 Minecraft，最後一起推送。既有 `feature/*` 分支不因本例回溯改名。

## Commit 訊息格式

觸發：Codex 準備建立任何一般 commit。Git 或平台自動產生的 merge commit 不在此格式要求內；Codex 不得為了規避格式而刻意改用 merge commit。

規則：使用 `<type>(<scope>):<中文摘要>`，冒號後不加空格，例如 `feat(ui):頁面更改`。`type`、`scope` 與變更檔案要一致；純文件／治理使用 `docs`，若 type 或 scope 無法從既有歷史與任務目的確認，先詢問使用者。

驗證：建立前以 `printf '%s\n' "$message" | rg --pcre2 -q '^(feat|fix|docs|refactor|test|chore|build|ci|perf|revert)\([^()\s:]+\):(?=.*\p{Han})\S.*$'` 檢查 type 清單、非空 scope、冒號後無空白且摘要至少含一個中文字符；另以變更內容人工核對 type／scope 是否相符。建立後用 `git log -1 --format=%s` read-back。摘要必須直接說明變更對象，不使用 `update files`、`misc changes` 或沒有對象的「調整」。此檢查只適用生效起點後由 Codex 建立的一般 commit，不能用來回溯判定既有歷史。

正例：`docs(codex):同步目前專案規劃`

反例：`docs: sync docs`，缺少 scope 且摘要不是中文。

## 每一次推送都要先審核

觸發：任何會更新遠端 ref 的動作，包括第一次或後續 `git push`、tag push、force push、修正後再次 push。

推送前動作：

1. 不執行 push；先向使用者提供審核封包：
   - 本地分支、remote 與目標 ref。
   - 即將推送的完整 commit SHA。
   - commit 訊息及其格式檢查結果。
   - 相對基底的 commit 清單、變更檔案與 diff 摘要。
   - 已執行的測試／build、exit code、未驗證項與風險。
2. 明確詢問，且問題中必須逐字列出本次實際的完整 commit SHA、本地分支、remote 與目標 ref；不得留下代號或省略值。
3. 只有使用者對該 SHA 與目標 ref 明確回答批准後，才可執行一次推送。

批准是單次且綁定 SHA／remote／ref。批准後若 HEAD、commit 歷史、remote、目標 ref 或 push 參數改變，原批准立即失效，必須重新送審。沉默、「繼續」「看起來可以」或先前對其他 SHA 的批准都不算本次 push 授權。

禁止：未審核 push、以建立 PR 為由先推再說、把第一次批准套用到後續 push、使用 `--force`。若確實需要重寫遠端歷史，必須另列影響並取得使用者對精確 `--force-with-lease` 動作的明確批准。

完成條件：push 命令成功且遠端 ref 指向獲批 SHA。失敗時記錄命令與錯誤；若原因或安全影響不確定，停止並詢問，不嘗試其他 remote／ref 或 force 方案。

## 自主合併只進 develop

`develop` 是 Codex 可操作的唯一整合分支，`main` 是正式分支。功能分支不能直接合併到 `main`，也不能繞過 `develop`。

觸發：使用者已對確切功能分支 SHA 明確表示審核完成。

動作：

1. 核對已審核的功能 SHA、功能分支、目標 `develop` commit、測試結果與未解意見；任一項改變就重新送審。
2. 審核完成後，Codex 可自主把該功能分支合併到本地 `develop`，不再改選其他目標。merge／squash／rebase 策略必須沿用 repo 明文慣例；查不到慣例時先詢問使用者。
3. 發生 conflict、目標 `develop` 前進、來源不再是已審核 SHA 或合併會帶入未審核範圍時，停止並詢問；不得自行猜測 conflict 解法或改用另一種合併策略。事前核對的是來源／目標 SHA、策略與預期 diff，不要求預知尚未建立的 merge／squash commit SHA。
4. 合併後重新執行適用測試，記錄新的 `develop` HEAD SHA 與相對遠端的 diff。
5. 若要把合併後的 `develop` 推到遠端，仍須按「每一次推送都要先審核」提供新 `develop` SHA／remote／ref 並取得單次批准。自主合併不包含自主 push。

停止條件：使用者尚未明確確認該功能 SHA 審核完成、合併策略不明、來源／目標 SHA 不一致、存在未解 review 意見、conflict 或測試失敗時，不合併。

完成條件：功能已合併到本地 `develop`，且合併後必要驗證全部通過；若遠端 `develop` 尚未獲批 push，才標「已合併 develop，待 push 審核」。若合併後必要驗證失敗或未完成，標「阻塞／未確認」，另註本地已合併的事實與 SHA；不自主 push、發佈或 reset 回復。需要修功能程式碼時仍在獨立分支處理，新 SHA 重新審核，不直接在 `develop` 修補；安全修復路徑不明則詢問。

正例：已核對來源／目標 SHA 與 merge 策略，無 conflict 時建立 merge commit；取得新 SHA 並通過必要驗證後，另送該 SHA 的 push 審核。

反例：合併後 `php artisan test` exit 1，因為「已有結果且已明示」就標待推送，或為了預知 SHA 而猜測 commit 值。

Codex 永遠不執行 `develop` 到 `main` 的 merge，也不直接把功能分支 merge／push 到 `main`。Codex 只提供 `develop` HEAD SHA、相對 `main` 的 commits／diff、驗證與風險；由使用者親自在 Git 平台或本地把 `develop` 合併到正式 `main`。若使用者要求 Codex 代為合併或 push `main`，不得執行；改為提供 `main` 手動合併交接資料。只有使用者另行明確要求修改治理制度時，才可把制度變更當成獨立任務處理，且不得在同一動作中順帶合併 `main`。

## 審核完畢才可發佈

正式 `main` 的合併只能由使用者主動執行。其他發佈包括：部署到任何共享或 production 環境、建立 release／tag、發布套件或啟動會對外生效的 release workflow。無法判斷某動作是否屬於發佈時，視為發佈並詢問使用者。

發佈前必須同時滿足：

1. 使用者已審核確切候選 SHA 的變更與驗證結果。
2. 候選 SHA 的必要測試／build 全部通過，並記錄命令、exit code 與驗證範圍；必要項失敗、未執行或被跳過時停止。只揭露問題或取得一般 push／發佈批准，不等於取消必要驗證；若使用者要改驗收範圍，先作明確的範圍決策，不由 Codex 自行降級門檻。
3. 已列出發佈目標、候選 SHA、操作命令／工具、影響範圍與可用回復方式。
4. `develop` 到 `main` 已由使用者親自合併，或使用者已對非 main 的確切發佈動作明確批准。

push 批准不等於發佈批准，功能 review 完成只授權按本協議整合到 `develop`，不授權 Codex 合併 `main` 或部署。候選 SHA、發佈目標或操作方式在批准後改變時，批准失效並重新送審。

停止條件：缺少任何一項、審核意見未解、候選 SHA 不一致、回復方式不明或工具／權限狀態不確定時，不合併、不 tag、不部署、不發布，直接詢問使用者。

完成條件：獲批 SHA 已發佈到獲批目標，並以可觀察結果確認；若只能確認命令送出而不能確認生效，狀態是「發佈結果未確認」，不能宣稱已完成。

## 不確定就停止詢問

先讀 repo、指示檔、Git 狀態與可取得的工具結果。若一次針對性查證後，對需求、範圍、功能分類、基底／分支、remote／ref、SHA、測試結果、權限、審核狀態、發佈目標、命令效果或使用者意圖仍有任何不確定：

1. 停止會改變檔案、Git 或外部狀態的動作。
2. 說明已確認事實、不確定點、可選方案與各自影響。
3. 向使用者提出最小且具體的問題，等待回答。

不得編造缺少的需求、Git 狀態、工具能力、審核結果、使用者批准或發佈結果。可由本地檔案直接查證的事實先查證；查不到才問，不以反覆試錯取代詢問。

## 狀態回報

功能工作只使用以下可觀察狀態：

- `功能分支中`：已確認獨立分支，尚未要求推送。
- `待推送審核`：功能與驗證已整理，但尚未取得本次 SHA／remote／ref 的批准。
- `功能分支已推送，待審核完成`：獲批功能 SHA 已推送，使用者尚未明確確認 review 完成。
- `待合併 develop`：使用者已確認確切功能 SHA review 完成，尚未自主合併。
- `已合併 develop，待 push 審核`：本地 `develop` 合併後必要驗證全部通過，遠端更新尚未取得批准。
- `develop 已就緒，待使用者合併 main`：合併後 `develop` 已獲批推送，Codex 停止；下一步只能由使用者主動合併正式 `main`。
- `待發佈`：使用者已親自合併 `main` 或已批准其他精確發佈動作，尚未執行或確認結果。
- `已發佈`：獲批 SHA 已在獲批目標生效並完成驗證。
- `阻塞／未確認`：存在不確定、未解審核意見、失敗或缺少證據。

不得跳過中間 gate，也不得把「待審核」「已推送」回報成「已發佈」。
