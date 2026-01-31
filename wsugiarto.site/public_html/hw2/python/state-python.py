#!/usr/bin/env python3
import os, uuid, sys, urllib.parse

SESSION_DIR = "/tmp"
COOKIE = "SessionID"
def get_cookies():
    cookies = {}
    raw = os.environ.get("HTTP_COOKIE", "")
    for part in raw.split(";"):
        if "=" in part:
            k, v = part.strip().split("=", 1)
            cookies[k] = v
    return cookies
def session_file(sid):
    return f"{SESSION_DIR}/py_session_{sid}.txt"
cookies = get_cookies()
sid = cookies.get(COOKIE)
new_session = False
if not sid:
    sid = uuid.uuid4().hex
    new_session = True
name = ""
if os.environ.get("REQUEST_METHOD") == "POST":
    length = int(os.environ.get("CONTENT_LENGTH", 0))
    data = sys.stdin.read(length)
    params = urllib.parse.parse_qs(data)
    name = params.get("name", [""])[0].strip()
    if name:
        with open(session_file(sid), "w") as f:
            f.write(name)

print("Content-Type: text/html")
if new_session:
    print(f"Set-Cookie: {COOKIE}={sid}; Path=/")
print()

print("""
<!DOCTYPE html>
<html>
<head><title>Python Sessioning</title></head>
<body>
<h1>Python Sessioning Save Page</h1>
<form method="POST" action="state-python.py">
  Name: <input type="text" name="name">
  <button type="submit">Save</button>
</form>
<br>
<a href="state-2-python.py">Goto Saved Data</a>
</body>
</html>
""")
