# 制度維護協議

本協議防止治理文件逐 session 變長、過時或把猜測當規則。`AGENTS.md` 是常駐入口；本文件、`lessons.md` 與 archive 都按需讀取。

## 可自行更新的低風險內容

只要不改變核心決策，執行者可自行更新：

- 已確認的檔案路徑、行號、命令名稱、package script 或 Make target；更新前先讀當前檔案，更新後跑同一個 `test -e`／`rg`／命令驗證。
- 本 repo 新出現且可重現的工具／權限錯誤與不等價替代驗證；先寫 `docs/codex/lessons.md`，不要立即改 `AGENTS.md`。
- 重複的說明、錯字、格式與失效的例子；必須 read-back 並檢查交叉引用。
- 已經存在且被目前工具清單明確確認的 skill／connector 名稱；只更新能力盤點，不承諾其登入、容量或所有 session 都可用。

低風險更新也必須保留使用者變更、避免覆蓋既有檔，並在回報列出路徑與驗證。

## 修改前必須詢問使用者的核心內容

除非使用者在當前請求明確授權，以下變更不得自行做：

- `AGENTS.md` 的適用範圍、規則優先級、禁止事項、完成門檻、升級／詢問條件或破壞性操作政策。
- 調度守則的責任分工、模型／effort 選擇規則、派工欄位、平行寫入邊界、第二意見與停止條件。
- rubric 的「完成」定義、最低驗證、P0/P1 風險與何時可宣稱通過。
- `git-review-release-protocol.md` 的功能分類、分支隔離、每次 push 審核、審核後只自主合併至 `develop`、`main` 只由使用者親自合併、發佈批准與不確定性停止條件。
- 引入新的插件／connector／外部帳號、CI／部署流程、永久 agent／automation，或把治理檔案移到可能改變載入作用的路徑。
- 刪除制度、覆蓋未追蹤檔、改寫 Git 基底／歷史、推送遠端或其他會改變外部狀態的操作。

詢問時先列出現有證據、可逆方案、各自影響與需要的最小選擇；不要只問「要不要改好一點」。只有使用者在當前請求明確指定的核心變更可直接執行；批准不延伸到未提及的其他核心規則。

## 踩坑是否值得制度化

先把事件當作教訓，不直接變成常駐規則。只有符合下列條件才可提案升格：

1. 可重現：有確切輸入／命令／環境、錯誤或結果，另一執行者能重跑或從檔案證據核對。
2. 可泛化：不只適用一個檔案或一次偶然狀態，而是同類任務會再次遇到。
3. 有價值：能降低 context 浪費、錯誤完成、失焦、資料損失或重複返工。
4. 有邊界：能寫出觸發、動作、停止、驗證與反例；無法寫成這五項的留在 lessons，不進入口規則。

升格門檻：通常需兩次不同任務／session 的同類證據；若是一次即可造成資料損失、權限越界或虛假完成的高風險事件，則一個 deterministic reproduction 加一次獨立核對即可提案。提案不等於採用，核心規則仍遵守上節的詢問要求。

## 教訓回寫格式與位置

所有候選教訓寫入 `docs/codex/lessons.md`，每筆使用以下欄位：

```text
## L-{{遞增編號}} {{短標題}}
- 日期／狀態：YYYY-MM-DD；provisional|confirmed|promoted|retired
- 觸發：任務類型、目標、精確命令／輸入／環境
- 證據：錯誤、exit code、檔案／行號或兩次重現結果
- 影響：context、失焦、錯誤完成或資料／權限風險
- 暫時處理：已做的安全替代與不等價處
- 驗證：如何確認處理有效，仍缺什麼
- 泛化邊界：適用與不適用情況
- 升格決定：維持 lesson／提案寫入哪個文件／理由
```

`{{...}}` 只存在於此模板；實際教訓不得留下未替換欄位。長 log 落在 `docs/codex/evidence/`，lesson 只引用路徑與摘要。

## 合併、封存與刪除

- 兩條規則只有在觸發、動作、停止與驗證完全相同時才合併；合併後保留較短且有證據的一條，更新所有引用並 read-back。
- 規則引用了不存在的路徑／命令／模型／skill 時先標為 stale，不能悄悄刪掉；修正路徑或移到 lessons，並記錄查證命令。
- 只有當替代規則已生效、全文引用為零、且至少兩次後續檢查沒有需要舊規則時，才可將舊版移到 `docs/codex/archive/`；不覆蓋 archive 中同名檔。
- 刪除歷史、lesson 或備份不是一般清理；除非使用者明確要求，保留並標 `retired`，避免失去復原證據。

## 修改、備份與回復流程

1. 先跑 `git status --short --branch`、`git rev-parse --verify HEAD`，查明目標是否已追蹤並記錄修改前 commit。2026-07-24 本次查證的可回復基線是 `20420e8a0281cf1a547548302f80f53974515294`；未來必須以當前 HEAD 重新查證，不能永久沿用此 SHA。
2. 目標已由 Git 追蹤時，以修改前 commit 與清楚 diff 作為回復證據；目標未追蹤時，先用明確不重複的檔名在 `docs/codex/archive/` 或 `/private/tmp` 建立副本，確認備份不存在且不覆蓋舊備份。若目標不存在，記錄「本次無既有檔，無需備份」。
3. 用小 patch 修改；每完成一個核心文件就立即 read-back、做引用檢查，再改下一個文件。
4. 驗證：`test -s`；對引用路徑用 `test -e`；用 `rg` 檢查工具／命令／模板欄位；有 Git 基線才跑 `git diff --check`，無基線則檢查 `git status --short` 與目標檔案。
5. 若驗證失敗，依 dispatch playbook 分類；不要靠格式化或重跑掩蓋內容錯誤。回復時先確認精確備份，再以小 patch 還原並重新 read-back。

## 失效路徑、模型、工具與指令檢查

每次涉及制度收尾或 session 開始時，做下列查證：

- 路徑：`rg --files` 找到目標，對每個引用跑 `test -e`；不因舊文件提到就假設存在。
- 指令：讀 `Makefile`、`package.json`、`composer.json`；確認 script／target 仍在。需要執行時記錄 exit code，命令不可用標未確認。
- 模型／effort／agent：只採用當前工具 schema；若沒有 `multi_agent_v1` 或某個選項，退回主模型或留下審查 Prompt，不編造平行語法。
- skill／plugin／connector：先看當前 Skills／工具清單，再看 manifest；manifest 只能證明磁碟存在，不證明可呼叫或已登入。未安裝 plugin 不自行 request install。
- 載入作用：查找所有實際 `AGENTS.md`／`AGENTS.override.md`，只把當前作用路徑的檔案當規則；archive、backup、lessons 不作常駐指示。
- 驗證入口：目前 `make test` 需要 Docker；host PHP test 不等價。README、Makefile、rubric 三者若矛盾，保留證據並由主模型決定是否詢問或修規則。
- Git／發佈 gate：確認 `git-review-release-protocol.md` 仍由 `AGENTS.md`、rubric 與實作模板引用；任何 push 或發佈批准必須能對應精確 SHA 與目標，不能從舊對話推定。抽查審核完成後 Codex 只整合至 `develop`，且 Codex 不 merge 或 push `main`；正式 `main` 必須保留給使用者親自合併。

## 協議的完成條件

一輪維護只有在變更範圍、備份／無需備份理由、read-back、引用檢查、適用測試與未確認項都已寫入回報後才算完成。若核心規則對抗審查發現未解衝突，狀態維持未完成，並把問題與下一步寫入 `future-session-letter.md`。
