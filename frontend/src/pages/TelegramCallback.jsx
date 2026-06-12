import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'

function parseQuery(qs) {
  const params = new URLSearchParams(qs)
  const obj = {}
  for (const [k, v] of params.entries()) obj[k] = v
  return obj
}

export default function TelegramCallback() {
  const navigate = useNavigate()

  useEffect(() => {
    const data = parseQuery(window.location.search)
    if (!data || !data.id) {
      navigate('/login')
      return
    }

    ;(async () => {
      try {
        const res = await api.post('/auth/telegram-auth', data)
        const { token, user } = res.data
        localStorage.setItem('token', token)
        localStorage.setItem('user', JSON.stringify(user))
        window.location.href = '/dashboard'
      } catch (err) {
        console.error(err)
        navigate('/login')
      }
    })()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  return <div>Logging you in...</div>
}
