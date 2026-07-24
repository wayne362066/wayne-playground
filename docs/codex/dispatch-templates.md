# 派工模板

本 repo 沒有專案自訂的委派 DSL。以下是可直接貼給 agent 的純文字模板；若當前工具清單仍暴露 `multi_agent_v1__spawn_agent`，把整段文字放在 `message`，按任務需要設定 `fork_context`；`model`／`reasoning_effort` 預設省略，不要自行編造參數。模板中的 `{{...}}` 是送出前必須替換的欄位，不是完成後可留下的內容。

通用回報格式不可省略：

```text
結論：
檔案／行號：
驗證：命令、exit code、通過／失敗／未驗證；替代驗證說明不等價原因。
未解風險：沒有則寫「無」。
```

所有模板的長產物落在 `docs/codex/evidence/` 下的實際任務子目錄；只回傳路徑與摘要，不把整份長報告塞回主 context。

任何會寫入功能行為的模板都必須先讀 `docs/codex/git-review-release-protocol.md`，確認主模型已把工作放在以 `develop` 為基底的獨立功能分支。agent 不得自行 push、merge、tag、release 或部署；主模型 review 後只可自主合併到 `develop`，`main` 必須由使用者親自合併。

## 搜尋／掃描

```text
目標與背景
目標：盤點 {{問題／規則／引用}}，回答 {{主模型需要的具體問題}}。
動機：主模型需要 {{決策／驗收}}，但完整掃描會污染主 context。

範圍及禁止事項
只讀：{{目錄／檔案範圍}}。
排除：.git、backend/vendor、frontend/node_modules、frontend/dist、backend/storage、二進位資產與不相關 generated files。
禁止：不修改任何檔案、不安裝套件／插件、不執行破壞性命令、不把猜測當事實。

必讀資料
先讀：AGENTS.md、{{相關 README／設定／測試}}。
工具：優先使用 rg／rg --files；輸出超過需要時落檔。

執行方式
只讀、可與 {{其他不重疊掃描}} 平行；本次不需要主 thread history，使用 fresh context（若工具可用則 fork_context=false）。model／effort 省略，除非當前 schema 與任務理由要求明確覆蓋。

驗收與測試
列出每個命中檔案與行號、未命中範圍、命令與 exit code；確認輸出沒有依賴／二進位內容；長結果寫到 docs/codex/evidence/{{實際任務目錄}}/scan.md。

停止及升級
找不到目標檔案、路徑作用範圍不明或結果互相矛盾時停止並回報證據；同一掃描策略失敗兩次後必須改查詢／工具或升級，不得原樣重試。

回報
只用四欄回報，長內容只附實際路徑；沒有命中也要寫掃描範圍與命令。
```

## 實作

```text
目標與背景
目標：在不改變 {{既有行為／API／文件契約}} 的前提下完成 {{功能或治理變更}}。
動機：{{使用者需求／已確認缺口／測試證據}}。

範圍及禁止事項
可寫：{{明確檔案清單或不重疊目錄}}。
禁止：{{不可寫檔案}}、未授權依賴升級、資料刪除、docker compose down -v、migrate-fresh、推送或建立外部 PR；不做順手重構。
若要新增檔案，先列出路徑與用途；遇到未追蹤既有檔案先停止確認是否可覆蓋。

必讀資料
先讀：AGENTS.md、docs/codex/decision-rubric.md、docs/codex/git-review-release-protocol.md、{{目標 README／設定／程式／測試}}。
先查：git status --short、相關 package.json／composer.json／Makefile scripts。

執行方式
功能分支：{{feature/實際功能代號}}；`develop` 基底 commit：{{完整 SHA}}。主模型必須在寫入前確認此分支獨立且不在 main／master／develop／共用發佈分支。寫入集合與其他 agent 不重疊；若切片獨立可平行，明確說明各自寫入路徑。model／effort 預設省略並繼承 parent；只有當前 multi_agent schema 明列且有清楚理由才覆蓋。

驗收與測試
新增／修改後立即 read-back。backend 改動優先 make test；Docker 受阻時另跑 cd backend && php artisan test 並標示 Compose 未驗證。frontend 改動跑 cd frontend && npm run build。治理文件跑引用／placeholder／路徑檢查。記錄每個命令 exit code。

停止及升級
需求、API 契約、資料遷移、設計取捨、分支／基底、remote／ref、審核狀態或不可逆動作需要選擇時停止回報主模型，由主模型詢問使用者；測試與假設矛盾時取得第二意見；同策略失敗兩次後換路，不得原樣重試。禁止自行 push 或發佈。

回報
四欄回報；檔案列絕對或 repo-relative 路徑及關鍵行號；未驗證項不可寫成通過。另列分支、基底 SHA、HEAD SHA 與審核狀態；沒有使用者批准時最多只能是「待推送審核」。
```

## 重構

```text
目標與背景
目標：在 {{明確行為契約}} 不變下整理 {{模組／檔案}}，降低 {{重複／邊界混亂／維護成本}}。
動機：{{可重現證據，例如重複命中、測試或依賴方向}}。

範圍及禁止事項
可寫：{{不重疊檔案集合}}；不可同時改功能、文案、依賴版本或資料 schema，除非明確列在目標。
禁止：以重構名義擴展 public API；刪掉沒有理解的測試；跨模組直接引用內部類別（本 repo 模組邊界規則仍適用）。

必讀資料
AGENTS.md、docs/codex/decision-rubric.md、README 的架構／新增模組段落、目標模組程式與完整相關測試。

執行方式
先寫出不變量與預期 diff，再小步修改；寫入不重疊即可平行，否則由主模型處理。model／effort 省略；fresh context 僅適合只讀第二意見。

驗收與測試
先跑重構前可取得的基線，修改後重跑相同命令；backend 用相關測試／make test，frontend 用 npm run build；比較 public API、序列化格式、路由與測試數量。任何基線缺失都標明。

停止及升級
行為改變、測試 oracle 缺失、邊界跨越三個以上模組或需要同步改 migration 時停止升級；同一編譯／測試策略兩次失敗即換路或交接。

回報
四欄回報，加一行「不變量檢查」；列出重構前後命令與結果及未覆蓋風險。
```

## 研究

```text
目標與背景
問題：回答 {{可驗證問題}}，用於 {{決策}}。
時間／範圍：{{截至日期、repo 路徑、外部來源範圍}}。

範圍及禁止事項
只研究 {{主題}}；禁止把推測、搜尋摘要或未登入 connector 的內容當作確認事實；不改程式碼。

必讀資料
先讀 AGENTS.md、相關 README／設定／測試；若需要 current external facts，使用當前可用的官方／一手來源並記錄 URL、日期與查詢時間。若來源工具未暴露，明寫未查證。

執行方式
可只讀平行查不同獨立來源；每個來源有明確問題。model／effort 預設省略；長研究落在 docs/codex/evidence/{{實際任務目錄}}/research.md。

驗收與測試
每個關鍵結論至少有一個可追溯來源／路徑／日期；列出相反證據、來源限制與是否為 inference；沒有來源的內容標「未確認」。

停止及升級
來源互相矛盾、需要帳號權限、問題其實是產品取捨或無法判斷因果時停止詢問／升級，不以語氣消除不確定性。

回報
四欄回報加「結論—證據對照」摘要；長內容只回實際檔案路徑。
```

## 審查

```text
目標與背景
目標：以 fresh context 對抗審查 {{AGENTS.md／調度守則／rubric／維護協議}}，尋找規則衝突、失效路徑、虛構能力、模糊判準、無限循環與過度設計。
動機：主模型不能把自己寫的規則視為獨立驗證。

範圍及禁止事項
只讀：AGENTS.md、docs/codex/git-review-release-protocol.md、docs/codex/dispatch-playbook.md、docs/codex/decision-rubric.md、docs/codex/maintenance-protocol.md、{{其引用的必要文件}}。
禁止：不修改檔案、不補洞、不安裝插件、不替主模型合理化矛盾；每個問題要指向實際行號。

必讀資料
先讀完整目標文件，再檢查每個引用路徑、命令、模型／effort／工具名稱是否在當前環境有證據；讀 harness-diagnosis 了解 repo 風險。

執行方式
必須是 fresh context（若可用則 fork_context=false）；只讀、獨立於主模型，可與不重疊的機械引用檢查平行。model／effort 預設省略；若工具未提供 agent，改用 docs/codex/adversarial-review-prompt.md 並標示尚未完成。

驗收與測試
逐一讀完指定文件；以 rg／test -e 檢查引用；每個 finding 有嚴重度（P0/P1/P2）、檔案、行號、規則、觸發輸入、失效結果、最小修正方向；無 finding 也要寫檢查範圍與命令 exit code。長報告落在 docs/codex/evidence/{{實際任務目錄}}/review.md。

停止及升級
若發現會使較弱模型無法安全完成、規則互相矛盾或能力虛構，先報告再由主模型修正；最多兩輪修正，第三次不再循環，改寫入 future-session-letter 的未解風險。

回報
四欄回報加 findings 清單；不要只說「看起來沒問題」。
```

## Push 審核封包

```text
請審核本次 push：
- 功能：{{單一功能名稱與範圍}}
- 本地分支：{{branch}}
- 基底 commit：{{完整 base SHA}}
- HEAD commit：{{完整 HEAD SHA}}
- Commit 訊息：{{type(scope):中文摘要}}
- Remote／目標 ref：{{remote}}／{{remote ref}}
- Commits：{{base..HEAD 的 commit 清單}}
- Diff：{{變更檔案與統計；附可檢視路徑}}
- 驗證：{{命令、exit code、通過／失敗／未驗證}}
- 風險：{{未解風險；沒有則寫無}}

是否批准把 commit {{完整 HEAD SHA}} 從 {{branch}} 推送到 {{remote}}/{{remote ref}}？

本批准只適用上述 SHA、remote、ref 與本次 push；任一內容改變會重新送審。
```

## 發佈審核封包

```text
請審核本次發佈：
- 功能：{{單一功能名稱與範圍}}
- 候選 commit：{{完整 SHA}}
- 已推送 ref／PR：{{可核對位置}}
- 發佈目標：{{正式分支、release、tag、共享環境或 production}}
- 發佈方式：{{確切命令／工具與主要參數}}
- 驗證：{{命令、exit code、review 結果、失敗／未驗證項}}
- 影響：{{使用者、資料、服務與停機影響}}
- 回復方式：{{可執行回復步驟；不確定則停止詢問}}

是否批准把 commit {{完整 SHA}} 以 {{發佈方式}} 發佈到 {{發佈目標}}？

Push 批准不等於本次發佈批准；SHA、目標或方式改變會重新送審。
正式 `main` 的 merge 不使用此封包要求 Codex 代執行；改用下方 main 手動合併交接，由使用者親自完成。
```

## Develop 自主合併檢核

```text
自主合併前檢核：
- 功能：{{單一功能名稱與範圍}}
- 已審核功能分支／SHA：{{branch}}／{{完整 SHA}}
- 使用者確認 review 完成的訊息：{{可核對訊息摘要}}
- 目標 develop SHA：{{合併前完整 SHA}}
- 合併策略：{{repo 明文規範與來源；找不到則停止詢問}}
- Review 意見：{{全部已解；否則停止}}
- 功能分支驗證：{{命令、exit code、結果}}
- 預期帶入 commits／檔案：{{清單}}

只有以上內容一致且無 conflict，才可自主合併到本地 develop。不得改成 main 或其他分支。

合併後：
- 新 develop SHA：{{完整 SHA}}
- 合併後驗證：{{命令、exit code、結果}}
- 遠端差異：{{origin/develop..develop 的 commits／diff}}
- 下一狀態：已合併 develop，待 push 審核
```

## Main 手動合併交接

```text
請使用者親自將 develop 合併到正式 main：
- Develop ref／SHA：{{remote develop ref}}／{{完整 SHA}}
- Main ref／SHA：{{remote main ref}}／{{目前完整 SHA}}
- 相對 main 的 commits：{{commit 清單}}
- Diff：{{變更檔案、統計與可檢視位置}}
- 驗證：{{命令、exit code、結果}}
- 未解風險：{{沒有則寫無}}
- 建議合併方式：{{只引用 repo 明文慣例；沒有慣例就標未確認}}

Codex 到此停止，不執行 merge／push main。使用者完成後，再以遠端 main SHA 或平台結果確認。
```
