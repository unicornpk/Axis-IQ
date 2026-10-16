<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Axis IQ — Ask your projects anything</title>
<meta name="description" content="Axis IQ is the AI query assistant for the AxisOne Suite. Ask a plain-language question inside any AxisOne product and get an answer sourced from that product's own data.">
<style>
  :root{
    --bg:#0e1622; --bg-elev:#141e2e; --card:#17233660;
    --ink:#eef2f7; --muted:#9fb0c3; --line:#243249;
    --accent:#ff8a3d; --accent-ink:#1a0e05;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;}
  a{color:inherit}
  .wrap{max-width:1040px;margin:0 auto;padding:0 20px;}
  header{padding:22px 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);}
  .brand{font-weight:700;letter-spacing:.02em;font-size:18px;display:flex;align-items:center;gap:8px;}
  .brand .mark{width:26px;height:26px;border-radius:7px;background:linear-gradient(135deg,var(--accent),#ffb066);display:inline-flex;align-items:center;justify-content:center;color:#1a0e05;font-weight:800;}
  nav a{color:var(--muted);text-decoration:none;margin-left:22px;font-size:14px;}
  nav a:hover{color:var(--ink)}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border-radius:9px;font-weight:600;font-size:14px;text-decoration:none;border:1px solid transparent;}
  .btn-accent{background:var(--accent);color:var(--accent-ink);}
  .btn-ghost{border-color:var(--line);color:var(--ink);}
  .hero{padding:72px 0 56px;text-align:center;}
  .eyebrow{display:inline-block;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);background:#2a1a0e;border:1px solid #4a2d12;padding:6px 12px;border-radius:999px;margin-bottom:22px;}
  h1{font-size:clamp(32px,5vw,52px);line-height:1.1;margin:0 0 18px;font-weight:700;letter-spacing:-0.01em;}
  h1 span{color:var(--accent);}
  .sub{max-width:640px;margin:0 auto 34px;color:var(--muted);font-size:18px;}
  .cta-row{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;}
  .example{max-width:560px;margin:56px auto 0;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px 20px;text-align:left;}
  .example .q{color:var(--muted);font-size:13px;margin-bottom:8px;}
  .example .bubble{background:var(--bg-elev);border-radius:10px;padding:14px 16px;font-size:15px;}
  section{padding:56px 0;border-top:1px solid var(--line);}
  h2{font-size:26px;margin:0 0 10px;}
  .lead{color:var(--muted);max-width:620px;margin:0 0 36px;}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;}
  .card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;}
  .card .num{color:var(--accent);font-weight:700;font-size:13px;margin-bottom:8px;}
  .card h3{margin:0 0 8px;font-size:16px;}
  .card p{margin:0;color:var(--muted);font-size:14px;}
  .products{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;}
  .product{border:1px solid var(--line);border-radius:12px;padding:16px 18px;background:var(--card);display:flex;align-items:center;justify-content:space-between;}
  .product .name{font-weight:600;}
  .pill{font-size:11px;letter-spacing:.04em;text-transform:uppercase;padding:4px 9px;border-radius:999px;font-weight:700;white-space:nowrap;}
  .product{gap:10px;}
  .product .name{white-space:nowrap;}
  .pill-live{background:#133a24;color:#6fe3a0;border:1px solid #1e5f3b;}
  .pill-soon{background:#241c33;color:#b39ddb;border:1px solid #382b4d;}
  footer{padding:36px 0 50px;color:var(--muted);font-size:13px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;border-top:1px solid var(--line);}
  @media (max-width:600px){header{flex-direction:column;gap:14px;} nav{display:flex;flex-wrap:wrap;justify-content:center;} nav a{margin:0 10px;}}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="brand"><span class="mark">IQ</span> Axis IQ</div>
    <nav>
      <a href="#how-it-works">How it works</a>
      <a href="#products">Products</a>
      <a href="/demo">Try the demo</a>
    </nav>
  </header>

  <div class="hero">
    <span class="eyebrow">AxisOne Suite · Intelligence layer</span>
    <h1>Ask your projects<br><span>anything.</span></h1>
    <p class="sub">Axis IQ answers plain-language questions about your AxisOne data, right inside the product you're already using — no reports to pull, no dashboards to learn.</p>
    <div class="cta-row">
      <a class="btn btn-accent" href="/demo">Try a live demo →</a>
      <a class="btn btn-ghost" href="#products">See what's connected</a>
    </div>

    <div class="example">
      <div class="q">Asked inside Field</div>
      <div class="bubble">"How many technicians are working today?"<br><br>→ <em>3 technicians are working today: 1 on site, 2 en route. 5 available, 0 off today.</em></div>
    </div>
  </div>

  <section id="how-it-works">
    <h2>How it works</h2>
    <p class="lead">Axis IQ is a small chat button embedded in each AxisOne product — it only ever answers using that product's own data.</p>
    <div class="grid">
      <div class="card"><div class="num">01</div><h3>Ask a question</h3><p>Type it in plain language, right where you're already working.</p></div>
      <div class="card"><div class="num">02</div><h3>Axis IQ checks the data</h3><p>It calls that product's own read-only data, scoped to what you're already allowed to see.</p></div>
      <div class="card"><div class="num">03</div><h3>Get a clear answer</h3><p>A direct answer, not a dashboard — with the numbers behind it.</p></div>
    </div>
  </section>

  <section id="products">
    <h2>Connected products</h2>
    <p class="lead">Rolling out across the AxisOne Suite, one product at a time.</p>
    <div class="products">
      <div class="product"><span class="name">AxisField</span><span class="pill pill-live">Live</span></div>
      <div class="product"><span class="name">AxisView</span><span class="pill pill-soon">Coming soon</span></div>
      <div class="product"><span class="name">AxisFinancial</span><span class="pill pill-soon">Coming soon</span></div>
      <div class="product"><span class="name">AxisFlow</span><span class="pill pill-soon">Coming soon</span></div>
    </div>
  </section>

  <footer>
    <span>Axis IQ — part of the AxisOne Suite.</span>
    <span><a href="/demo">Try the demo</a></span>
  </footer>
</div>
</body>
</html>
