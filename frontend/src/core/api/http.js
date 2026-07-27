import axios from 'axios'

let forbiddenHandler = null

const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api',
  timeout: 10000,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 403) {
      forbiddenHandler?.()
    }

    return Promise.reject(error)
  },
)

export function setForbiddenHandler(handler) {
  forbiddenHandler = handler
}

export default http
