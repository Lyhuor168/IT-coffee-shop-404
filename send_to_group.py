import os
import requests
import sys

# Prefer env vars; fall back to hard-coded values if present
BOT_TOKEN = os.getenv('TELEGRAM_BOT_TOKEN', '8032636088:AAFHVI-3YADxQEq4mKXgdDClFjBqk7nPuKI')
CHAT_ID = os.getenv('TELEGRAM_CHAT_ID', '-1003757591051')

BASE_URL = f"https://api.telegram.org/bot{BOT_TOKEN}"

def api_get(method, params=None):
    try:
        resp = requests.get(f"{BASE_URL}/{method}", params=params, timeout=10)
        return resp
    except Exception as e:
        print(f"❌ Exception calling {method}: {e}")
        return None

def send_telegram_message(message):
    # Diagnostic: check bot identity
    me = api_get('getMe')
    if not me:
        return False
    print('Bot identity:', me.json() if me.ok else me.text)

    # Diagnostic: check chat
    chat = api_get('getChat', params={'chat_id': CHAT_ID})
    if chat is None:
        return False
    if not chat.ok:
        print('getChat failed:', chat.status_code, chat.text)
        # Common cause: bot not a member or lacks permissions
        print('Hint: add the bot to the group/channel and grant it permission to post (make it an admin for channels).')
        return False
    print('Chat info:', chat.json())

    # Try sending the message
    try:
        resp = requests.post(f"{BASE_URL}/sendMessage", data={
            'chat_id': CHAT_ID,
            'text': message,
        }, timeout=10)

        if resp.ok:
            print('✅ Message sent successfully')
            return True
        else:
            print('❌ sendMessage failed:', resp.status_code, resp.text)
            if resp.status_code == 400:
                print('Hint: 400 often means the bot lacks permission to write to this chat (not a member or not admin for channels).')
            return False
    except Exception as e:
        print('❌ Exception while sending message:', e)
        return False


if __name__ == '__main__':
    msg = 'សួស្តី! ngaii ng ber kru ot rean jam jum team tver project tt ot bro2 and you ok .'
    if len(sys.argv) > 1:
        msg = ' '.join(sys.argv[1:])
    send_telegram_message(msg)
