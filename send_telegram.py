import requests

BOT_TOKEN = "8032636088:AAFHVI-3YADxQEq4mKXgdDClFjBqk7nPuKI"
CHAT_ID = "5396022926"

def send_telegram_message(message):
    url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage"
    payload = {
        "chat_id": CHAT_ID,
        "text": message
    }
    response = requests.post(url, data=payload)
    
    if response.status_code == 200:
        print("Success! Message sent.")
    else:
        print(f"Error: {response.status_code} - {response.text}")

send_telegram_message("Hello! Test message from Python")

