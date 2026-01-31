#!/usr/bin/python3
import os, json, datetime, socket

ip = os.environ.get("REMOTE_ADDR", "unknown")
date = datetime.datetime.now().strftime("%a %b %d %H:%M:%S %Y")


message = {
    "title": "Hello, Python!",
    "heading": "Hello, Python!",
    "message": "This page was generated with the Python programming language",
    "time": date,
    "IP": ip
}

print("Content-Type: application/json\r\nCache-Control: no-cache\r\n\r\n")
print(json.dumps(message))
