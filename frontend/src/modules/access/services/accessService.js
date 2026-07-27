import http from '../../../core/api/http'

async function csrfToken() {
  const response = await http.get('/auth/csrf-cookie')
  return response.data.data.token
}

async function mutation(method, url, data = null) {
  const token = await csrfToken()
  return http.request({
    method,
    url,
    data,
    headers: { 'X-CSRF-TOKEN': token },
  })
}

export async function fetchAccessManagement() {
  const response = await http.get('/admin/access')
  return response.data.data
}

export async function createRole(payload) {
  const response = await mutation('post', '/admin/access/roles', payload)
  return response.data.data
}

export async function updateRole(id, payload) {
  const response = await mutation('patch', `/admin/access/roles/${id}`, payload)
  return response.data.data
}

export async function deleteRole(id) {
  await mutation('delete', `/admin/access/roles/${id}`)
}

export async function updateUserRoles(id, roleKeys) {
  const response = await mutation('patch', `/admin/access/users/${id}/roles`, {
    role_keys: roleKeys,
  })
  return response.data.data
}
