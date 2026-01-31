#!/usr/bin/env python3
import os

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
sid = cookies.get(COOKIE, "")
name = ""

if sid and os.path.exists(session_file(sid)):
    with open(session_file(sid)) as f:
        name = f.read().strip()
qs = os.environ.get("QUERY_STRING", "")
if qs == "clear=CLEARDATA" and sid:
    try:
        os.remove(session_file(sid))
    except:
        pass
    name = ""

print("Content-Type: text/html\n")
print(f"""
<!DOCTYPE html>
<html>
<head><title>Python Sessioning</title></head>
<body>
<h1>Python Sessioning View Data</h1>
<p><b>Saved Name:</b> {name if name else "No Name yet"}</p>
<a href="state-python.py">Back to Save Page</a><br><br>
<a href="state-2-python.py?clear=CLEARDATA">Clear Data</a>

</body>
</html>
""")
