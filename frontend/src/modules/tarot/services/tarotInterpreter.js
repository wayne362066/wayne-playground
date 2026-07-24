const positions = [
  {
    key: 'present',
    label: '此刻',
    lead: '這張牌描述你目前最需要看見的狀態',
  },
  {
    key: 'influence',
    label: '核心影響',
    lead: '這張牌指出正在推動或牽制局面的力量',
  },
  {
    key: 'direction',
    label: '行動方向',
    lead: '這張牌提供接下來可以實踐的提醒',
  },
]

const openingTemplates = [
  ({ domain, focus, state }) => `你問的是「${domain.label}」。從「${focus.label}」與「${state.label}」兩個答案來看，${focus.insight}；同時，${state.insight}。`,
  ({ domain, focus, state }) => `這次解讀聚焦在${domain.label}。你選擇了「${focus.label}」，顯示${focus.insight}；而「${state.label}」則提醒我們，${state.insight}。`,
  ({ domain, focus, state }) => `在${domain.label}這個主題裡，你最關心的是「${focus.label}」。這表示${focus.insight}，目前的狀態也呈現：${state.insight}。`,
]

const synthesisTemplates = [
  ({ first, second, third, timeframe }) => `牌面從「${first.name}」走向「${third.name}」，中間由「${second.name}」承接。它不像單一答案，更像一條調整路徑：先理解現況，再處理核心影響，最後把注意力放回能採取的行動。${timeframe.insight}。`,
  ({ first, second, third, timeframe }) => `三張牌的重點不在預言固定結果，而在於「${first.name}」揭示的現況、由「${second.name}」帶出的關鍵，以及「${third.name}」提供的方向。${timeframe.insight}，適合用可觀察的小變化檢視進展。`,
  ({ first, second, third, timeframe }) => `這組牌呈現由覺察到行動的順序。「${first.name}」讓目前狀態浮現，「${second.name}」要求你正視真正的影響，「${third.name}」則把選擇權帶回手上。${timeframe.insight}。`,
]

function hashContext(value) {
  return [...value].reduce((hash, character) => (
    ((hash << 5) - hash) + character.charCodeAt(0)
  ) | 0, 0)
}

function chooseTemplate(templates, seed) {
  return templates[Math.abs(hashContext(seed)) % templates.length]
}

function getAnswer(question, value) {
  return question.options.find((option) => option.value === value)
}

function interpretCard(card, position, domain) {
  const isReversed = card.orientation === 'reversed'
  const meaning = isReversed ? card.reversed : card.upright
  const orientationLabel = isReversed ? '逆位' : '正位'

  return {
    ...card,
    position: position.key,
    positionLabel: position.label,
    orientationLabel,
    meaning,
    interpretation: `${position.lead}。${card.name}${orientationLabel}帶出「${meaning}」。放在${domain.label}的脈絡中，${domain.tone}。`,
    action: card.advice,
  }
}

/**
 * Rule-based interpreter boundary. A future AI interpreter can accept the same
 * context and return this result shape without changing the view flow.
 */
export function interpretTarotReading({ domain, answers, cards }) {
  const [focusQuestion, stateQuestion, timeframeQuestion] = domain.questions
  const focus = getAnswer(focusQuestion, answers[focusQuestion.id])
  const state = getAnswer(stateQuestion, answers[stateQuestion.id])
  const timeframe = getAnswer(timeframeQuestion, answers[timeframeQuestion.id])
  const seed = `${domain.value}:${Object.values(answers).join(':')}:${cards.map((card) => `${card.id}-${card.orientation}`).join(':')}`
  const cardReadings = cards.map((card, index) => interpretCard(card, positions[index], domain))
  const opening = chooseTemplate(openingTemplates, seed)({ domain, focus, state })
  const synthesis = chooseTemplate(synthesisTemplates, `${seed}:summary`)({
    first: cards[0],
    second: cards[1],
    third: cards[2],
    timeframe,
  })

  return {
    domain: domain.label,
    headline: `${focus.label}｜從${cards[0].name}走向${cards[2].name}`,
    opening,
    synthesis,
    cards: cardReadings,
    actions: [
      cardReadings[0].action,
      cardReadings[1].action,
      cardReadings[2].action,
    ],
    disclaimer: domain.disclaimer || '塔羅解讀提供自我反思的角度，不代表固定命運或保證結果。',
  }
}
