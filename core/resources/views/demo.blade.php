<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Axis IQ demo</title>
<meta name="description" content="Try Axis IQ with sample questions — illustrative data, not a live connection.">
<style>
  :root{
    --bg:#0e1622; --bg-elev:#141e2e; --card:#17233660;
    --ink:#eef2f7; --muted:#9fb0c3; --line:#243249;
    --accent:#ff8a3d; --accent-ink:#1a0e05;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;}
  a{color:var(--accent)}
  .wrap{max-width:640px;margin:0 auto;padding:24px 20px 60px;}
  .top{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
  .brand{font-weight:700;display:flex;align-items:center;gap:8px;}
  .brand .mark{width:24px;height:24px;border-radius:6px;background:linear-gradient(135deg,var(--accent),#ffb066);display:inline-flex;align-items:center;justify-content:center;color:#1a0e05;font-weight:800;font-size:12px;}
  .back{font-size:13px;color:var(--muted);text-decoration:none;}
  .banner{background:#241c0e;border:1px solid #4a3812;color:#e3c27a;font-size:13px;padding:10px 14px;border-radius:9px;margin-bottom:18px;}
  .panel{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden;}
  .head{padding:14px 18px;border-bottom:1px solid var(--line);font-weight:600;font-size:14px;color:var(--muted);}
  #messages{min-height:220px;max-height:420px;overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:12px;}
  .msg{max-width:85%;padding:10px 13px;border-radius:10px;font-size:14.5px;line-height:1.4;}
  .msg.user{align-self:flex-end;background:var(--accent);color:var(--accent-ink);}
  .msg.bot{align-self:flex-start;background:var(--bg-elev);}
  .msg.bot .tag{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:5px;}
  .chips{padding:0 18px 14px;display:flex;flex-wrap:wrap;gap:8px;}
  .chip{background:var(--bg-elev);border:1px solid var(--line);color:var(--ink);font-size:13px;padding:7px 12px;border-radius:999px;cursor:pointer;}
  .chip:hover{border-color:var(--accent);}
  .inputrow{display:flex;border-top:1px solid var(--line);}
  #q{flex:1;background:transparent;border:none;color:var(--ink);padding:14px 16px;font-size:14.5px;outline:none;}
  #q::placeholder{color:var(--muted);}
  #send{background:var(--accent);color:var(--accent-ink);border:none;padding:0 20px;font-weight:600;cursor:pointer;}
  #send:disabled{opacity:.5;cursor:default;}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div class="brand"><span class="mark">IQ</span> Axis IQ — demo</div>
    <a class="back" href="{{ url('/') }}">← back</a>
  </div>

  <div class="banner">Illustrative demo — sample data only, not a live connection to any real project.</div>

  <div class="panel">
    <div class="head">Ask Axis IQ a question</div>
    <div id="messages"></div>
    <div class="chips" id="chips"></div>
    <div class="inputrow">
      <input id="q" type="text" placeholder="Try one of the sample questions above…" autocomplete="off">
      <button id="send">Ask</button>
    </div>
  </div>
</div>

<script>
(function(){
  // All sample data — this page never calls the real Axis IQ API or any
  // live product. Keys are matched loosely against what the visitor types.
  var SAMPLES = [
    {
      chip: "How many technicians are working today?",
      tag: "Sample · Field",
      match: ["technician", "working today", "staff"],
      answer: "3 technicians are working today: 1 on site and 2 en route. Another 5 are available, and none are off today."
    },
    {
      chip: "What's our job completion rate this week?",
      tag: "Sample · Field",
      match: ["completion rate", "job completion", "completed"],
      answer: "This week's job completion rate is 64%: 9 of 14 scheduled jobs completed so far."
    },
    {
      chip: "How many RFIs are open?",
      tag: "Sample · View (coming soon)",
      match: ["rfi", "request for information"],
      answer: "2 RFIs are open on this project, none overdue. 1 is awaiting close-out."
    },
    {
      chip: "What's our cash position?",
      tag: "Sample · Financial (coming soon)",
      match: ["cash", "budget", "financial"],
      answer: "Current cash position is PKR 2.15M across connected accounts, as of this morning."
    }
  ];

  var messages = document.getElementById('messages');
  var chips = document.getElementById('chips');
  var input = document.getElementById('q');
  var send = document.getElementById('send');

  SAMPLES.forEach(function(s){
    var chip = document.createElement('button');
    chip.className = 'chip';
    chip.textContent = s.chip;
    chip.onclick = function(){ input.value = s.chip; ask(); };
    chips.appendChild(chip);
  });

  function addMessage(text, cls, tag){
    var div = document.createElement('div');
    div.className = 'msg ' + cls;
    if (tag) {
      var t = document.createElement('span');
      t.className = 'tag';
      t.textContent = tag;
      div.appendChild(t);
    }
    div.appendChild(document.createTextNode(text));
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
    return div;
  }

  function findSample(query){
    var q = query.toLowerCase();
    for (var i = 0; i < SAMPLES.length; i++) {
      for (var j = 0; j < SAMPLES[i].match.length; j++) {
        if (q.indexOf(SAMPLES[i].match[j]) !== -1) return SAMPLES[i];
      }
    }
    return null;
  }

  function ask(){
    var query = input.value.trim();
    if (!query) return;
    addMessage(query, 'user');
    input.value = '';
    send.disabled = true;

    setTimeout(function(){
      var s = findSample(query);
      if (s) {
        addMessage(s.answer, 'bot', s.tag);
      } else {
        addMessage('This demo only knows the sample questions below — try one of those. In the real product, Axis IQ answers from your actual connected data.', 'bot', 'Sample');
      }
      send.disabled = false;
    }, 450);
  }

  send.addEventListener('click', ask);
  input.addEventListener('keydown', function(e){ if (e.key === 'Enter') ask(); });
})();
</script>
</body>
</html>
