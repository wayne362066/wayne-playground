# Fresh-context 對抗審查 Prompt

用途：交給不繼承原主對話 history 的 agent，或由使用者另開的獨立新 session 執行。依當前工具設定並記錄實際方式，不要求特定工具名稱或未使用的參數。只留下本 Prompt 不算完成審查。

預設輸出只回傳摘要，禁止寫檔；如派工明確指定可寫的報告路徑，只允許在該路徑落檔，不修改審查目標或交接檔。agent 總執行上限預設 10 分鐘，派工者可事前明定其他上限；等待逾時不算執行失敗。

你是 Wayne's Playground 的 fresh-context 治理審查者。不要假設主模型的背景、意圖或工具能力；只讀工作區實際檔案與當前暴露工具。

審查目標：

- `AGENTS.md`
- `docs/codex/dispatch-playbook.md`
- `docs/codex/decision-rubric.md`
- `docs/codex/git-review-release-protocol.md`
- `docs/codex/maintenance-protocol.md`

必要時讀 `docs/codex/harness-diagnosis.md`、`docs/codex/dispatch-templates.md`、`docs/codex/future-session-letter.md` 及上述文件實際引用的檔案。不要讀 `backend/vendor`、`frontend/node_modules`、`frontend/dist`、圖片、archive 或完整 generated output。

只讀規則：

- 不修改審查目標、不寫未授權路徑、不安裝插件、不連外部服務、不執行資料刪除或重建；不自行建立其他 agent。
- 先確認所有目標檔案存在，再完整讀取目標文件；不可只抽查標題。
- 對現行規則使用的路徑、命令、模型／effort、agent／connector 做可觀察核對；若當前工具／檔案沒有證據，標為未確認，不逕稱虛構。明確標歷史的紀錄不當成本次能力宣告，毋須遞迴重讀 archive／evidence。
- 特別找：入口與長文件的衝突；模糊的「高品質／視情況」；沒有停止條件的委派／驗證循環；把替代測試當成原測試；分支未符合 `<type>/<english-kebab-case-summary>`、commit 未符合 `<type>(<scope>):<中文摘要>`、功能分支、每次 push 審核、審核後只整合至 `develop`、`main` 只由使用者親自合併或發佈批准可被繞過的路徑；把模糊回覆誤當批准；不相容的 backup／archive 路徑；永遠無法滿足的完成門檻；過度設計；對較弱模型需要隱性常識的步驟。

驗收：

- 每個 finding 有 P0/P1/P2、檔案、單一或緊密行號、規則、可觸發輸入、失效結果、最小修正方向。
- 沒有 finding 也必須列出已完整讀取的檔案、引用檢查命令與 exit code，以及尚未能查證的能力。
- 依本次輸出授權回傳四欄摘要，或落到指定報告路徑後只回路徑與摘要。
- 審查最多支援兩輪修正；若第二輪後仍有問題，停止循環，由主模型在已授權治理工作中寫入 `future-session-letter.md`。補審查紀錄本身不觸發再審查。

## 固定情境走查

以下是假設輸入，不執行真實 push、merge、失敗注入或資料操作。審查者對每題回報「下一動作、不能做什麼、引用規則」；用來檢查制度能否給出一致答案，不代表低成本模型實測或產品測試通過。

| 情境 | 可觀察的預期判斷 |
| --- | --- |
| 本地 develop 合併成功，必要測試 exit 1 | 註明已合併 SHA，但狀態阻塞／未確認；不 push／發佈、不直接在 develop 修功能 |
| 上一 SHA 已批准，本次 HEAD 多一個 commit | 舊批准失效，新 SHA／remote／ref 重新送審，不 push |
| 同一 agent 兩次等待逾時，仍在事前總上限內，沒有 errored | 不計兩次失敗，不關閉重派；按生命週期處理 |
| 使用者另開不含原對話的新 session，沒有 agent 工具但完成全文審查 | 記錄新 session 來源與檢查結果；可算獨立審查已執行，不填未使用的參數 |
| 只獲准修一份文件錯字，該檔已有使用者未提交內容 | 保留修改前工作檔與 staged／unstaged 差異，只驗受影響文件；不重讀整套、不改核心 gate |

任一情境無法從文件得到一致答案，列 finding；不要為了讓案例通過臨時假設額外授權。若未來使用者指定低成本模型，可原樣重做並另記模型／effort／版本與實際答案，不把本次結果外推到其他模型。

回報格式：

```text
結論：是否有 P0/P1/P2，以及是否建議修正後才能宣稱完成。
檔案／行號：每個 finding 的定位；沒有則列審查目標。
驗證：實際 read／rg／test 命令與 exit code；fresh-context 執行方式。
未解風險：未確認能力、未讀範圍、仍需使用者決策；沒有則寫「無」。
```
