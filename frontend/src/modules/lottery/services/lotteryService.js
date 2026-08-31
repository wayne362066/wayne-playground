import http from '../../../core/api/http'

export async function generatePowerLottery(count) {
  const response = await http.post('/lottery/power/generate', { count })
  return response.data.data
}

export async function simulatePowerLottery(ticketCount, mode) {
  const response = await http.post('/lottery/power/simulate', {
    ticket_count: ticketCount,
    mode,
  })
  return response.data.data
}

export async function simulateSelectedPowerLottery(zoneOne, zoneTwo, periodCount) {
  const response = await http.post('/lottery/power/simulate-selected', {
    zone_one: zoneOne,
    zone_two: zoneTwo,
    period_count: periodCount,
  })
  return response.data.data
}
