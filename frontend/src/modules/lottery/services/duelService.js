import http from '../../../core/api/http'

let csrfToken = ''

async function token() {
  if (csrfToken) return csrfToken

  const response = await http.get('/auth/csrf-cookie')
  csrfToken = response.data.data.token

  return csrfToken
}

async function post(path, payload = null) {
  const response = await http.post(path, payload, {
    headers: { 'X-CSRF-TOKEN': await token() },
  })

  return response.data.data
}

export async function fetchDuelRooms() {
  const response = await http.get('/lottery/duels')
  return response.data.data.rooms
}

export async function fetchCurrentDuelRoom() {
  const response = await http.get('/lottery/duels/current')
  return response.data.data.room
}

export async function fetchDuelRoom(roomId) {
  const response = await http.get(`/lottery/duels/${roomId}`)
  return response.data.data.room
}

export async function createDuelRoom(payload) {
  return (await post('/lottery/duels', payload)).room
}

export async function joinDuelRoom(roomId, payload) {
  return (await post(`/lottery/duels/${roomId}/join`, payload)).room
}

export async function readyDuelRoom(roomId) {
  return (await post(`/lottery/duels/${roomId}/ready`)).room
}

export async function heartbeatDuelRoom(roomId) {
  return (await post(`/lottery/duels/${roomId}/heartbeat`)).room
}

export async function leaveDuelRoom(roomId) {
  return (await post(`/lottery/duels/${roomId}/leave`)).room
}

export async function rematchDuelRoom(roomId) {
  return (await post(`/lottery/duels/${roomId}/rematch`)).room
}
