const timeframeQuestion = {
  id: 'timeframe',
  prompt: '你最想看見哪一段時間的方向？',
  helper: '時間範圍會改變解讀著重的節奏。',
  options: [
    { value: 'soon', label: '接下來一個月', insight: '你期待先看見近期可以採取的調整' },
    { value: 'season', label: '未來三個月', insight: '你關注的是一段能逐步發展的過程' },
    { value: 'half-year', label: '未來半年', insight: '你希望理解較長期的方向與累積' },
  ],
}

export const tarotDomains = [
  {
    value: 'career',
    label: '事業',
    icon: '↗',
    description: '工作、轉職與發展方向',
    tone: '把焦點放在選擇、能力與可持續的推進方式',
    questions: [
      {
        id: 'focus',
        prompt: '目前最牽動你的是哪一件事？',
        helper: '選最接近此刻狀態的項目即可。',
        options: [
          { value: 'growth', label: '現職成長', insight: '你正在評估如何讓現有位置繼續成長' },
          { value: 'change', label: '轉職或轉換跑道', insight: '你正站在改變方向的門口' },
          { value: 'decision', label: '重要工作決定', insight: '你需要在幾個現實選項中做取捨' },
          { value: 'team', label: '合作與職場關係', insight: '你在意的是合作品質與彼此的位置' },
        ],
      },
      {
        id: 'state',
        prompt: '現在的工作感受比較接近？',
        helper: '這會決定牌義偏向機會、阻礙或調整。',
        options: [
          { value: 'stuck', label: '有些卡住', insight: '目前的停滯感比機會更容易被你察覺' },
          { value: 'uncertain', label: '方向不明', insight: '你需要的可能不是更快，而是更清楚的判斷依據' },
          { value: 'changing', label: '正在變動', insight: '環境已經開始移動，你也在尋找合適節奏' },
          { value: 'steady', label: '穩定但想突破', insight: '你擁有一定基礎，下一步在於如何突破慣性' },
        ],
      },
      timeframeQuestion,
    ],
  },
  {
    value: 'love',
    label: '愛情',
    icon: '♡',
    description: '關係、互動與情感選擇',
    tone: '把焦點放在感受、界線與雙方真正能做到的互動',
    questions: [
      {
        id: 'focus',
        prompt: '你想理解哪一種關係狀態？',
        helper: '不需要輸入姓名，保留屬於自己的隱私。',
        options: [
          { value: 'single', label: '目前單身', insight: '你想知道自己如何迎接新的情感連結' },
          { value: 'dating', label: '曖昧或認識中', insight: '這段互動還在形成，你正在觀察彼此的真實意圖' },
          { value: 'relationship', label: '穩定交往中', insight: '你在意如何理解並經營目前的關係' },
          { value: 'distance', label: '疏遠或待修復', insight: '你正在衡量這段關係是否仍有修復與靠近的空間' },
        ],
      },
      {
        id: 'state',
        prompt: '你此刻最在意的是？',
        helper: '選擇真正讓你反覆想到的部分。',
        options: [
          { value: 'clarity', label: '對方的想法', insight: '不確定感讓你希望得到更清楚的訊號' },
          { value: 'communication', label: '如何溝通', insight: '你期待找到既誠實又不失去界線的表達方式' },
          { value: 'choice', label: '該前進或放下', insight: '你正在評估繼續投入是否符合自己的需要' },
          { value: 'connection', label: '讓關係更靠近', insight: '你重視的是如何建立更穩定而真實的連結' },
        ],
      },
      timeframeQuestion,
    ],
  },
  {
    value: 'wellbeing',
    label: '身心',
    icon: '≈',
    description: '能量、壓力與自我照顧',
    tone: '把焦點放在日常節奏與自我覺察，而不是醫療診斷',
    disclaimer: '塔羅內容僅供自我反思，不能取代醫師、心理師或其他專業意見。',
    questions: [
      {
        id: 'focus',
        prompt: '你想先照顧哪一個面向？',
        helper: '這裡不判斷疾病，只整理你能觀察的日常狀態。',
        options: [
          { value: 'energy', label: '精神與能量', insight: '你想重新理解精力的分配與消耗' },
          { value: 'stress', label: '壓力與情緒', insight: '你正在尋找承接壓力、恢復平衡的方法' },
          { value: 'habits', label: '生活習慣', insight: '你希望讓日常選擇更貼近真正的需要' },
          { value: 'rest', label: '休息與恢復', insight: '你的注意力正回到休息品質與恢復空間' },
        ],
      },
      {
        id: 'state',
        prompt: '目前最接近哪一種感受？',
        helper: '照直覺選擇，不需要過度分析。',
        options: [
          { value: 'tired', label: '容易疲憊', insight: '你可能已經持續輸出一段時間，需要重新分配容量' },
          { value: 'tense', label: '緊繃難放鬆', insight: '身心仍在警戒，需要更明確的緩衝與安定感' },
          { value: 'uneven', label: '狀態起伏', insight: '你的節奏並不穩定，適合先辨認造成波動的條件' },
          { value: 'rebuilding', label: '正在慢慢恢復', insight: '你已經在恢復路上，現在更需要耐心而非催促' },
        ],
      },
      timeframeQuestion,
    ],
  },
  {
    value: 'finance',
    label: '財務',
    icon: '◇',
    description: '資源、規劃與金錢選擇',
    tone: '把焦點放在資源配置、風險感受與可掌握的行動',
    disclaimer: '解讀僅供整理想法，不構成投資、稅務或其他財務建議。',
    questions: [
      {
        id: 'focus',
        prompt: '你最想整理哪個財務主題？',
        helper: '牌卡不預測價格，而是幫助你看見決策狀態。',
        options: [
          { value: 'planning', label: '收支與規劃', insight: '你希望重新建立更有掌控感的資源安排' },
          { value: 'work-income', label: '收入與工作', insight: '你正在思考能力與收入之間如何形成更好連結' },
          { value: 'purchase', label: '一筆重要支出', insight: '你需要分辨當下需要與長期負擔之間的界線' },
          { value: 'risk', label: '風險與選擇', insight: '你正在評估不確定性是否落在可以承擔的範圍' },
        ],
      },
      {
        id: 'state',
        prompt: '目前的財務感受比較接近？',
        helper: '選感受，而不是透露實際金額。',
        options: [
          { value: 'tight', label: '有些壓力', insight: '壓力可能讓你更容易只看見眼前限制' },
          { value: 'unclear', label: '缺少方向', insight: '你需要先建立清楚的優先順序與判斷標準' },
          { value: 'stable', label: '穩定但想改善', insight: '你已有基礎，適合從結構與長期效率著手' },
          { value: 'opportunity', label: '出現新機會', insight: '新選項帶來期待，也需要和承擔能力一起衡量' },
        ],
      },
      timeframeQuestion,
    ],
  },
  {
    value: 'relationships',
    label: '人際',
    icon: '◎',
    description: '朋友、家人與合作互動',
    tone: '把焦點放在溝通、期待與彼此界線',
    questions: [
      {
        id: 'focus',
        prompt: '這段互動主要發生在哪裡？',
        helper: '選一個最需要你投入注意力的關係。',
        options: [
          { value: 'friends', label: '朋友', insight: '你在意友誼中的理解、支持與互相回應' },
          { value: 'family', label: '家人', insight: '熟悉的關係裡可能同時存在責任、期待與情感' },
          { value: 'work', label: '工作合作', insight: '你需要在任務、立場與關係之間找到平衡' },
          { value: 'community', label: '團體或社群', insight: '你正在確認自己在群體中的位置與歸屬感' },
        ],
      },
      {
        id: 'state',
        prompt: '目前最希望改善的是？',
        helper: '答案會影響結果裡的行動提醒。',
        options: [
          { value: 'misunderstanding', label: '化解誤會', insight: '你希望資訊與感受能被更準確地理解' },
          { value: 'boundaries', label: '建立界線', insight: '你需要一種既保護自己又不必切斷關係的距離' },
          { value: 'trust', label: '恢復信任', insight: '信任需要由一致而可驗證的互動重新累積' },
          { value: 'cooperation', label: '找到合作方式', insight: '你想讓彼此差異成為分工，而不是持續摩擦' },
        ],
      },
      timeframeQuestion,
    ],
  },
  {
    value: 'growth',
    label: '自我成長',
    icon: '✦',
    description: '選擇、目標與內在整理',
    tone: '把焦點放在真正重視的價值與下一個可行步驟',
    questions: [
      {
        id: 'focus',
        prompt: '你想探索哪一個方向？',
        helper: '選擇最近最常回到心裡的主題。',
        options: [
          { value: 'direction', label: '人生方向', insight: '你希望辨認什麼值得成為下一階段的重心' },
          { value: 'confidence', label: '信心與勇氣', insight: '你正在重新建立對自己選擇與能力的信任' },
          { value: 'pattern', label: '反覆出現的模式', insight: '你已經注意到某種循環，希望找到新的回應方式' },
          { value: 'purpose', label: '目標與動力', insight: '你想讓投入的力氣和真正重視的事重新連線' },
        ],
      },
      {
        id: 'state',
        prompt: '此刻的內在狀態比較像？',
        helper: '沒有好壞，只是確認現在站的位置。',
        options: [
          { value: 'searching', label: '正在尋找', insight: '你願意探索，但答案還沒有形成清楚輪廓' },
          { value: 'transition', label: '正經過轉換', insight: '舊階段正在退場，新位置仍需要一點時間適應' },
          { value: 'blocked', label: '知道方向但跨不出', insight: '你缺少的也許不是方向，而是安全可行的第一步' },
          { value: 'ready', label: '準備開始', insight: '你的動力已經聚集，現在適合把想法落成行動' },
        ],
      },
      timeframeQuestion,
    ],
  },
]

export function findTarotDomain(value) {
  return tarotDomains.find((domain) => domain.value === value)
}
