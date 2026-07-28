import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import http from '../api/http'

let echo = null
let csrfToken = ''

async function token() {
  if (csrfToken) return csrfToken

  const response = await http.get('/auth/csrf-cookie')
  csrfToken = response.data.data.token

  return csrfToken
}

export function realtime() {
  if (echo) return echo

  const key = import.meta.env.VITE_REVERB_APP_KEY
  if (!key) return null

  window.Pusher = Pusher
  const scheme = import.meta.env.VITE_REVERB_SCHEME || 'http'

  echo = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8081),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => ({
      authorize: async (socketId, callback) => {
        try {
          const response = await http.post(
            '/broadcasting/auth',
            {
              socket_id: socketId,
              channel_name: channel.name,
            },
            {
              headers: { 'X-CSRF-TOKEN': await token() },
            },
          )
          callback(false, response.data)
        } catch (error) {
          callback(true, error)
        }
      },
    }),
  })

  return echo
}
