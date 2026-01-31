#!/usr/bin/env python3
import os
import sys
from datetime import datetime

print("Content-Type: text/html\n")

method = os.environ.get("REQUEST_METHOD", "")
protocol = os.environ.get("SERVER_PROTOCOL", "")
query = os.environ.get("QUERY_STRING", "")
host = os.environ.get("HTTP_HOST", "")
useragent = os.environ.get("HTTP_USER_AGENT", "")
ip = os.environ.get("REMOTE_ADDR", "")
time = datetime.now().strftime("%a %b %d %H:%M:%S %Y")

body = ""
if method in ("POST", "PUT", "DELETE"):
    try:
        length = int(os.environ.get("CONTENT_LENGTH", 0))
    except:
        length = 0
    body = sys.stdin.read(length)

print(f"""
<!DOCTYPE html>
<html>
<head>
  <title>General Request Echo</title>
</head>
<body>
  <h1 align="center">General Request Echo Python Version</h1>
  <hr>

  <p><b>HTTP Protocol:</b> {protocol}</p>
  <p><b>HTTP Method:</b> {method}</p>
  <p><b>Hostname:</b> {host}</p>
  <p><b>Time:</b> {time}</p>
  <p><b>User Agent:</b> {useragent}</p>
  <p><b>IP Address:</b> {ip}</p>

  <p><b>Query String:</b></p>
  <pre>{query}</pre>

  <p><b>Message Body:</b></p>
  <pre>{body}</pre>

</body>
</html>
""")
