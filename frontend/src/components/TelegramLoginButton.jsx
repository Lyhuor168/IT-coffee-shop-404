import { useEffect } from 'react'

export default function TelegramLoginButton() {
  useEffect(() => {
    if (typeof window === 'undefined') return

    const bot = import.meta.env.VITE_TELEGRAM_BOT_USERNAME
    const authUrl = `${import.meta.env.VITE_FRONTEND_URL || ''}/telegram-callback`
    if (!bot) return

    const inject = () => {
      const container = document.getElementById('telegram-login')
      if (!container) return
      container.innerHTML = ''
      const script = document.createElement('script')
      script.setAttribute('async', '')
      script.setAttribute('src', 'https://telegram.org/js/telegram-widget.js?19')
      script.setAttribute('data-telegram-login', bot)
      script.setAttribute('data-size', 'large')
      script.setAttribute('data-auth-url', authUrl)
      script.setAttribute('data-request-access', 'write')
      container.appendChild(script)
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') inject()
    else window.addEventListener('DOMContentLoaded', inject)
  }, [])

  return <div id="telegram-login" />
}
