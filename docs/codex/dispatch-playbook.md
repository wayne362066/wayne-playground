# 模型與工作調度守則

本文件是按需讀取的調度規則。它只記錄本 session 探測到的能力；模型、effort、工具與 connector 變動時，先重新探測再更新，不把名稱當成永久保證。

## 目前已查證的 harness

截至 2026-07-24：

- `multi_agent_v1` 暴露 `spawn_agent`、`send_input`、`wait_agent`、`resume_agent`、`close_agent`。spawn 支援 `fork_context`、可選 `model`、可選 `reasoning_effort` 與可選 `service_tier`；多個獨立 spawn 可由上層一次發出，但沒有查到名為 `parallel` 的獨立正式語法。
- spawn 工具列出的 model override 是：`gpt-5.6-sol`（low／medium／high／xhigh／max／ultra）、`gpt-5.6-terra`（low／medium／high／xhigh／max／ultra）、`gpt-5.6-luna`（low／medium／high／xhigh／max）、`gpt-5.5`（low／medium／high／xhigh）、`gpt-5.4`（low／medium／high／xhigh）。工具描述給出的定位分別是 latest frontier、balanced、fast/affordable、frontier、strong everyday；這些定位不等同於本 session 的實際主模型。
- 正式調度規則：預設不傳 `model`／`reasoning_effort`，讓 agent 繼承 parent；只有任務有清楚的模型理由且該選項仍在當前工具 schema 時才覆蓋。不可填入未列出的模型、effort、tier 或 `parallel` 參數。
- `fork_context: false` 代表新 agent 只收到派工訊息，適合 fresh-context 審查；`true` 會帶入目前 thread history，適合延續高度依賴的工作。選擇後必須在回報中寫明。
- 當前 shell 探測到 Git、rg、Make、Docker CLI、PHP 8.5、Node 25、npm 11；未找到 Composer／`gh`。Docker CLI 存在，普通 sandbox 連 Docker socket 被拒絕，但獲准的 sandbox 外重跑 `make test` 已通過 6 tests／807 assertions；主機 PHP 測試與 frontend build 也通過。這些是本 repo 的環境證據，不是所有 session 的保證。
- 磁碟上查到 system／plugin skill manifests（如 browser、documents、pdf、presentations、spreadsheets、github、sites、visualize、imagegen、openai-docs、skill/plugin creator 等），但「有 manifest」不代表當前 session 可呼叫。只使用本 session Skills 清單或工具清單明確暴露的能力；目前可見的 app connector 主要是 GitHub、Sites、Codex document control、plugin management 與 hotline。未查到 Gmail、Slack、Calendar、Drive、Notion、Box、Figma、Atlassian、Outlook、SharePoint、Teams 的可呼叫工具，且它們在 recommended plugins 中標為未安裝，不得自行假設可用。
- repo 的 `.agents/`、`.codex/` 沒有工作區檔案；沒有 CI、`.github/`、scripts 或專案專用持久化機制。治理文件落在 repo 是唯一已確認可長期保存的方式；Codex thread／agent 狀態的跨 session 持久化未確認。
- 當前主模型名稱、主模型 reasoning effort、可同時執行的 agent 上限、Docker sandbox 外的 daemon 權限、CI remote 與 connector 認證狀態均未確認。

## 主模型與 agent 的責任

主模型必須自己完成以下判斷，不能把責任轉交後就宣稱完成：

1. 讀懂使用者目標，定義不做什麼，判斷任務類型與風險。
2. 直接閱讀高槓桿證據：入口規則、相關 README／設定、目標程式、測試、失敗訊息與 agent 回報中的關鍵檔案；不可只看摘要就做跨模組或高風險決定。
3. 將工作拆成可驗收切片，決定是否派工、是否平行、是否需要第二意見或升級。
4. 功能工作開始前確認獨立分支；使用者確認 review 完成後只自主合併到 `develop`，合併後重新驗證並為 `develop` push 送審；到 `main` 前停止並交給使用者親自合併。

agent 只負責派工訊息內的明確切片。它不得擴大寫入範圍、安裝插件、改變驗收標準、執行未授權破壞性操作或把未跑的驗證寫成通過。agent 不得自行 push、merge、tag、release 或部署；主模型只可按協議整合到 `develop`，不得整合或 push `main`。

## 何時派工

優先派工的訊號：

- 工作可獨立驗收，且與主模型下一個立即步驟不互相阻塞。
- 大量掃描會污染主 context，例如盤點多個目錄、整理長 log、搜尋重複規則；agent 回傳摘要即可。
- 機械式批次工作可用固定輸入／輸出判定，例如列出所有引用路徑、檢查文件中的命令是否存在。
- 需要獨立第二意見，例如核心治理文件完成後的 fresh-context 對抗審查。
- 長產物可以直接落到指定檔案，主模型只需檢查路徑、摘要與驗證。

保留主模型本地處理的訊號：

- 下一個關鍵決策依賴該結果，派工會讓 critical path 空等。
- 範圍仍不清楚、需要使用者選擇，或兩個工作會寫同一檔案。
- 需要跨模組架構判斷、不可逆操作、安全／資料風險，或 agent 沒有足夠上下文。
- 只是一次很小的讀取／編輯，派工成本大於節省的 context。

若適合平行，分割成不重疊的讀取或寫入集合；需要寫檔的 agent 只能寫被明確分配的路徑。沒有辦法保證寫入隔離時，改成只讀派工或由主模型自己處理。

## 派工封包的必要欄位

每次派工都要明確提供：

1. 目標與動機：要回答哪個問題，結果如何幫助主模型。
2. 工作範圍與禁止範圍：目錄／檔案、只讀或可寫、不可碰的業務／工具／操作。
3. 必讀資料：入口規則、相關檔案與現有證據；不要只寫「自行研究」。
4. 執行方式：直接工作或 fresh context、是否可平行、model／effort 是否省略或使用當前 schema 的明確值。
5. 驗收條件：輸出檔案、必須通過的機械檢查、允許的未驗證狀態。
6. 停止／升級條件：遇到哪些錯誤要停、問人或換路。
7. 回報格式：結論、檔案與行號、驗證結果、未解風險；長內容落檔只回路徑與摘要。
8. 若為功能寫入：獨立分支、基底 commit、禁止 agent push／merge／發佈、review 後唯一整合目標 `develop`，以及 `main` 由使用者親自合併。

缺欄的派工不可送出。回報缺欄時退回補充，不自行猜測。

## 失敗分類與下一步

| 類型 | 可觀察訊號 | 動作 | 停止條件 |
| --- | --- | --- | --- |
| 工具／權限／暫時環境 | command not found、socket／permission denied、服務未啟動、網路暫斷 | 先修正一個條件後重試一次；若仍失敗，換已存在的本地替代命令並標註不等價 | 第二次同條件失敗；不可把替代驗證當原驗證 |
| 資訊不足 | 目標檔案／需求／測試 oracle 缺失，兩種方案會造成不同外部行為，或 Git／審核／發佈狀態不確定 | 做一次針對性查證；仍無法確定時立即向使用者詢問，附上已知、未知、選項與影響 | 使用者選擇或足夠證據出現前不改狀態、不 push、不發佈 |
| 推理／架構／需求誤判 | 回報與原始檔案矛盾、測試反駁假設、兩模組邊界不明、出現幻覺工具 | 立即停止原策略，取得第二意見或用更高能力／較高 effort 的已暴露選項；攜帶完整上下文 | 第二意見仍不能決定時交接，不重複猜測 |
| 相同策略兩次失敗 | 同一命令／同一輸入／同一假設再次產生同類錯誤 | 改變問題分類、工具、輸入、隔離方式或解法；若不能改變，詢問或交接 | 不得第三次原樣重試 |

升級封包至少帶：目標、範圍、假設、已試方法與確切命令、錯誤／exit code、已驗證結果、推測原因、希望獲得的決策。升級後仍由主模型驗收。

## 報告與持久化

預設回報固定四欄：

```text
結論：一句話回答目標是否達成。
檔案／行號：列出實際讀寫的絕對或 repo-relative 路徑與關鍵行號。
驗證：命令、exit code、通過／失敗／未驗證；替代驗證要寫不等價原因。
未解風險：阻塞、假設、下一步；沒有則明寫「無」。
```

功能回報另加「分支／審核狀態」：分支、基底 SHA、HEAD SHA，以及 `功能分支中`／`待推送審核`／`功能分支已推送，待審核完成`／`待合併 develop`／`已合併 develop，待 push 審核`／`develop 已就緒，待使用者合併 main`／`待發佈`／`已發佈`／`阻塞／未確認`。沒有對應證據不得提升狀態；Codex 不得把狀態提升為「已合併 main」。

超過主模型需要的長掃描、研究或審查報告，落在 `docs/codex/evidence/` 的明確任務子目錄；不要只留在 agent 對話。可重現、可泛化且有證據的模式才寫回 `docs/codex/lessons.md`，達到 maintenance protocol 的升格條件後才改核心規則或 Skill。

## 調度自查

派工前回答「為何獨立、寫入是否不重疊、誰驗收、失敗怎麼換路」；派工後回答「回報四欄齊全、路徑存在、主模型看過高槓桿證據、驗證 exit code 已記錄」。任一答案為否，主模型不得宣稱完成。
