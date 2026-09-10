# 2026-09-10 治理校正證據

這是有日期的證據與交接，不是常駐指示或未來工具保證。

## 範圍與回復基線

- 使用者授權：依前輪審查修正有證據的制度缺口，不為調整而調整。
- 修改前：`develop...origin/develop`，工作區與 index 乾淨；HEAD `13f4a6b9e8ccd1b0d1ff0312f541461922d3f15e`。
- 既有修改目標均由 Git 追蹤；以該 commit 與本輪 diff 保留原版。回復先用 `git show 13f4a6b9e8ccd1b0d1ff0312f541461922d3f15e:AGENTS.md` 之類的精確路徑讀取原文，再核對後續變更，以小 patch 回復；不覆蓋後來的使用者修改。
- 本證據檔建立前不存在，無既有檔需備份。沒有建立可被載入的 AGENTS 備份，沒有刪除歷史。
- 只改 `AGENTS.md` 與 `docs/codex/`；不改業務、依賴、Makefile、CI，不 commit／push／merge。

## 本次工具與載入證據

- 實際 spawn 工具為 `multi_agent_v1__spawn_agent`，參數為 `message`／`items`、`fork_context`、`model`、`reasoning_effort`；schema 沒有 `service_tier` 或 `parallel`。描述中的 service tier 不能推導出可傳參數。
- 可選模型：`gpt-6-astra`、`gpt-5.6-sol`、`gpt-5.6-terra`（low／medium／high／xhigh／max／ultra）；`gpt-5.6-luna`（low／medium／high／xhigh／max）；`gpt-5.5`（low／medium／high／xhigh）。`gpt-5.4` 不在本次 override 清單。未據此推斷實際主模型、價格或能力排序。
- 本次工具要求：預設不傳 model；只有使用者明確要求不同模型才指定。未做 model／effort override。
- 本次 `fork_context: false` 的 schema 語意是不帶主 thread history，只收到初始派工。`multi_agent_v1__wait_agent` 的 `timed_out: true` 只代表等待期限內尚無終態，並非 agent 執行失敗。
- 工作區指示搜尋只找到根 `AGENTS.md`；指定上層路徑無同名指示；全域 `/Users/zhanghanwen/.codex/AGENTS.md` 存在但為 0 bytes，不是「檔案不存在」。未修改全域設定。
- OpenAI Docs skill 用於查證載入與 agent 文件：[指示載入](https://learn.chatgpt.com/docs/agent-configuration/agents-md)、[subagents](https://learn.chatgpt.com/docs/agent-configuration/subagents)。官方說明支援全域／專案分層與依指示派工；它不能證明本帳號的工具、模型、權限或並行上限。

## 已取得的前輪審查與產品證據

- 同日、同一 HEAD 的前輪 fresh-context reviewer：`01a0891c-a943-7921-ba76-1d5aae274160`；主模型 spawn 使用 `fork_context: false`，省略 model／effort；取得完成結果後才關閉。
- 其 6 項 P2 是：驗證失敗仍提升狀態、預知 merge SHA、不接受無 agent 的獨立 session、只讀與寫報告衝突、教訓回寫跨出功能分支、微小修改觸發全量閱讀。主模型逐項核對原文，另核對工具漂移與等待語意。
- 前輪 `cd backend && php artisan test --compact`：exit 0，57 passed、1 skipped、1179 assertions。跳過的 PostgreSQL column comment 測試不代表通過。
- 前輪 frontend `npm run build -- --outDir "$audit_build_dir"`：exit 0；`audit_build_dir` 由 `mktemp -d /private/tmp/wayne-governance-build.XXXXXX` 建立，實際為 `/private/tmp/wayne-governance-build.DUK0HH`；`laravel-echo` 已安裝。未執行安裝或改 lockfile。
- 上述是修改前的產品環境證據，不是本輪修訂後的新測試，也不涵蓋 Docker、PostgreSQL／Redis 整合或瀏覽器互動。

## 本輪驗收狀態

本輪文件校正與指定範圍驗證完成；不代表所有模型、產品整合或正式發佈已驗收。沒有 commit／push／merge。

### 修改檔案與用途

- `AGENTS.md`：保留 50 行入口；完成門檻、按需路由、備份與報告授權摘要同步。
- `docs/codex/git-review-release-protocol.md`、`docs/codex/decision-rubric.md`：必要驗證須通過；merge 前查輸入、後查新 SHA；相關 skip、互動與服務專屬行為不被 build／SQLite 代替。
- `docs/codex/dispatch-playbook.md`、`docs/codex/dispatch-templates.md`：能力與授權檢核、等待生命週期、報告路徑與功能／治理分類一致。
- `docs/codex/maintenance-protocol.md`：既有未提交修改的備份、教訓回寫邊界，以及小修正／核心修改不同驗證範圍。
- `docs/codex/adversarial-review-prompt.md`：獨立 session 可用、只讀與輸出授權分開，附五個固定走查情境。
- `docs/codex/harness-diagnosis.md`、`docs/codex/future-session-letter.md`、`docs/codex/lessons.md`：現況與歷史分開、更新審查與依賴阻塞狀態；歷史誤寫的 root build 已按舊 Makefile 校正。

### 修訂後獨立審查

- Reviewer：`01a08938-a904-7ad1-8112-4347fa6db2fb`（Carver），由主模型以 `fork_context: false` 啟動，model／effort 省略，輸出僅回傳摘要，總上限 10 分鐘。
- 完整讀取 Prompt 指定的 5 份核心文件（含入口）、審查 Prompt 與派工模板，合計 7 份、812 行；6 次只讀 shell 批次均 exit 0。主模型另外直接核對關鍵條款，不以 reviewer 摘要代替原文。
- 結論：未發現有證據的 P1／P2；五情境均得到一致答案。一次修訂後審查即取得結果，無需第二輪修正。等待回傳 completed 後才關閉；未把等待逾時計為失敗。
- 範圍限制：reviewer 不代驗本地工具 schema、完整引用／Git 範圍或產品測試；由主模型補查。歷史診斷與本證據檔不是 reviewer 的核心審查目標。

| 固定情境 | Reviewer 實際答案摘要 |
| --- | --- |
| merge 後必要測試 exit 1 | 記已合併 SHA，但阻塞／未確認；不 push／發佈／reset，不直接修 develop |
| 批准後新增 commit | 舊批准失效，新 SHA／remote／ref 送審 |
| 兩次等待逾時、未達總上限 | 保留原 agent，不算失敗、不重派 |
| 獨立新 session 無 agent 工具 | 可完成獨立審查；記來源，不填未用參數 |
| 單檔錯字且有使用者既有修改 | 先保存原檔及 staged／unstaged 差異，只驗受影響範圍 |

### 主模型機械檢查

- 每個修改檔與新增證據檔均立即以 `sed -n` 完整 read-back；UTF-8 替代字元、結尾換行、code fence 配對、模板欄位邊界檢查通過。
- 本次 inline Node checker 讀取 11 份文件，解析 Markdown links 與行內檔案引用，確認 17 個不同本地路徑存在。搜尋候選 `AGENTS.override.md` 不當成必須存在的產物；簡寫 manifest 經實讀，分別按 frontend package 與 backend composer 解析。
- Checker 前兩次 exit 1：先將簡寫 package.json 當根路徑，接著發現 repo 有兩份同名檔；改為直接讀取句子上下文與兩份 manifest，確認範圍後才修正解析。最後 exit 0，沒有把誤判省略或以「任一路徑存在」掩蓋歧義。
- 同一 checker 直接擷取 Git 協議的兩個 regex，以 `rg --pcre2 -q` 測 11 案例：合法中文 commit／合法 feat、docs 分支通過；未知 type、英文摘要、冒號後空白、無 scope、舊 feature 前綴、中文分支與連續連字號被拒絕。只測新規則，不重判歷史 commit。
- `git diff --check`：exit 0。`git diff --binary -- AGENTS.md docs/codex | git apply --reverse --check`：exit 0，只檢查可反向套用，未實際回復檔案。
- `git rev-parse HEAD` 與 `git cat-file -e` 核對每個修改檔的基線可讀；`git diff --cached --quiet`：exit 0，index 未變。10 個已追蹤變更均為治理文件，唯一新增檔為本報告。
- 規則交叉搜尋：`rg -n 'docs/codex|AGENTS|git push|develop|main|合併|發佈|make test|npm run build|php artisan test' AGENTS.md docs/codex -g '*.md' -g '!**/archive/**' -g '!**/evidence/**' -g '!*.snapshot' -g '!*.patch'`，exit 0；依命中回讀目標文件。初次較短 glob 未排除 evidence，已改成上述完整排除形式。
- 在入口、playbook、rubric、templates、maintenance、審查 Prompt 共 6 份文件搜尋舊 namespace、模型清單、service_tier、fork_context：exit 1（無命中，符合預期）；精確工具只留在有日期證據／歷史，不當永久規則。
- `make -n test`：exit 0，只驗命令展開。`git show d1c6e9f8215c004f994b2bc7622ecefdaa105ee0:Makefile` 核對 root build 歷史用途；未執行 Docker 或改 Makefile。

### 未驗證與交接

- 本輪只有文件修改，沒有重跑產品 test／build；前輪產品證據保留日期、SHA 與覆蓋限制。沒有新增 lint／type-check／CI 來讓治理修改看起來更完整。
- 低成本模型可靠度未實測；未擅自選模型。後續使用者指定模型／effort 時，可直接使用審查 Prompt 的固定情境並保存實際答案。
- 明天從 `AGENTS.md` 路由進入；失敗依 rubric／playbook 分類，查證後仍不確定就詢問。每功能獨立分支、中文 commit、逐次 push 審核、只整合 develop、main 由使用者親自合併均保留。
