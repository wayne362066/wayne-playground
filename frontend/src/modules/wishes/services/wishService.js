import http from '../../../core/api/http'

export async function fetchWishes(filters = {}) {
  const response = await http.get('/wishes', { params: filters })
  return response.data.data
}

export async function fetchManagedWishes() {
  const response = await http.get('/wish-management')
  return response.data.data
}

export async function createWish(payload) {
  const response = await http.post('/wishes', payload)
  return response.data.data
}

export async function updateWish(id, payload) {
  const response = await http.patch(`/wishes/${id}`, payload)
  return response.data.data
}

export async function deleteWish(id) {
  await http.delete(`/wishes/${id}`)
}

export async function restoreWish(id) {
  const response = await http.post(`/wish-management/${id}/restore`)
  return response.data.data
}
