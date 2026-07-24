import http from '../../../core/api/http'

export async function generatePowerLottery(count) {
  const response = await http.post('/lottery/power/generate', { count })
  return response.data.data
}
