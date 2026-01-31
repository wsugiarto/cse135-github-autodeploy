#!/usr/bin/python3
import os
import datetime

ip = os.environ.get("REMOTE_ADDR", "unknown")
now = datetime.datetime.now().strftime("%a %b %d %H:%M:%S %Y")

print("Content-Type: text/html\r\n\r\n")
print(f"""<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Hello HTML - Python</title></head>
<body>
  <h1 align=center>Hello HTML World</h1><hr/>
  <p>Hello World, this is Wilson Sugiarto</p>
  <p>This page was generated with the Python programming langauge</p>
  <p>This program was generated at: {now}</p>
  <p>Your current IP Address is: {ip}</p>
  
</body>
</html>
""")
