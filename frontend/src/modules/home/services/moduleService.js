import http from '../../../core/api/http'

export async function fetchModules() {
  const response = await http.get('/modules')
  return response.data.data
}
