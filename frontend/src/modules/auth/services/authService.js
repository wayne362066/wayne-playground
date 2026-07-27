import http from '../../../core/api/http'

async function ensureCsrfCookie() {
  const response = await http.get('/auth/csrf-cookie')
  return response.data.data.token
}

export async function fetchCurrentAccount() {
  const response = await http.get('/auth/me')
  return response.data.data
}

export async function fetchCurrentPermissions() {
  const response = await http.get('/auth/permissions')
  return response.data.data
}

export async function registerAccount(payload) {
  const csrfToken = await ensureCsrfCookie()
  const response = await http.post('/auth/register', payload, {
    headers: { 'X-CSRF-TOKEN': csrfToken },
  })
  return response.data.data
}

export async function loginAccount(payload) {
  const csrfToken = await ensureCsrfCookie()
  const response = await http.post('/auth/login', payload, {
    headers: { 'X-CSRF-TOKEN': csrfToken },
  })
  return response.data.data
}

export async function logoutAccount() {
  const csrfToken = await ensureCsrfCookie()
  await http.post('/auth/logout', null, {
    headers: { 'X-CSRF-TOKEN': csrfToken },
  })
}
