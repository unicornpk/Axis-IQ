/**
 * Axis IQ embeddable chat widget.
 *
 * Usage (inside any AxisOne product page):
 *
 *   <script>
 *     // Each product wires this once: return the current user's bearer token
 *     // exactly as it already uses for its own API calls.
 *     window.getAxisAuthToken = () => window.App.authToken;
 *   </script>
 *   <script src="https://iq.axisone.si/widget/axis-iq-widget.js"
 *           data-product="field"
 *           data-api-base="https://iq.axisone.si"></script>
 *
 * No build step, no dependencies. Safe to include once per page.
 */
(function () {
  "use strict";

  if (window.__axisIqLoaded) return;
  window.__axisIqLoaded = true;

  var currentScript = document.currentScript;
  var PRODUCT = currentScript.getAttribute("data-product");
  var API_BASE = (currentScript.getAttribute("data-api-base") || "").replace(/\/$/, "");

  if (!PRODUCT || !API_BASE) {
    console.error("[Axis IQ] widget requires data-product and data-api-base attributes");
    return;
  }

  function getToken() {
    if (typeof window.getAxisAuthToken === "function") {
      return window.getAxisAuthToken();
    }
    console.warn("[Axis IQ] window.getAxisAuthToken() is not defined — requests will be unauthenticated");
    return null;
  }

  // ---- styles -------------------------------------------------------

  var style = document.createElement("style");
  style.textContent = [
    "#axis-iq-launcher{position:fixed;bottom:20px;right:20px;width:52px;height:52px;border-radius:50%;",
    "background:#1a56db;color:#fff;border:none;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,.25);",
    "font-size:22px;z-index:99998;}",
    "#axis-iq-panel{position:fixed;bottom:84px;right:20px;width:340px;max-height:460px;background:#fff;",
    "border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.2);display:none;flex-direction:column;",
    "overflow:hidden;z-index:99999;font-family:-apple-system,Segoe UI,Roboto,sans-serif;font-size:14px;}",
    "#axis-iq-panel.open{display:flex;}",
    "#axis-iq-header{background:#1a56db;color:#fff;padding:12px 14px;font-weight:600;}",
    "#axis-iq-messages{flex:1;overflow-y:auto;padding:12px;background:#f7f8fa;}",
    "#axis-iq-messages .msg{margin-bottom:10px;max-width:85%;padding:8px 10px;border-radius:8px;",
    "line-height:1.35;white-space:pre-wrap;}",
    "#axis-iq-messages .msg.user{background:#1a56db;color:#fff;margin-left:auto;}",
    "#axis-iq-messages .msg.assistant{background:#e9edf3;color:#1a1a1a;}",
    "#axis-iq-messages .msg.error{background:#fdecea;color:#b91c1c;}",
    "#axis-iq-inputrow{display:flex;border-top:1px solid #e5e7eb;}",
    "#axis-iq-input{flex:1;border:none;padding:10px 12px;font-size:14px;outline:none;}",
    "#axis-iq-send{border:none;background:#1a56db;color:#fff;padding:0 16px;cursor:pointer;}",
    "#axis-iq-send:disabled{opacity:.5;cursor:default;}"
  ].join("");
  document.head.appendChild(style);

  // ---- markup ---------------------------------------------------------

  var launcher = document.createElement("button");
  launcher.id = "axis-iq-launcher";
  launcher.setAttribute("aria-label", "Ask Axis IQ");
  launcher.textContent = "IQ";

  var panel = document.createElement("div");
  panel.id = "axis-iq-panel";
  panel.innerHTML =
    '<div id="axis-iq-header">Axis IQ — ' + escapeHtml(PRODUCT) + '</div>' +
    '<div id="axis-iq-messages"></div>' +
    '<div id="axis-iq-inputrow">' +
      '<input id="axis-iq-input" type="text" placeholder="Ask a question…" />' +
      '<button id="axis-iq-send">Ask</button>' +
    '</div>';

  document.body.appendChild(launcher);
  document.body.appendChild(panel);

  var messagesEl = panel.querySelector("#axis-iq-messages");
  var inputEl = panel.querySelector("#axis-iq-input");
  var sendBtn = panel.querySelector("#axis-iq-send");

  launcher.addEventListener("click", function () {
    panel.classList.toggle("open");
    if (panel.classList.contains("open")) inputEl.focus();
  });

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  function addMessage(text, cls) {
    var div = document.createElement("div");
    div.className = "msg " + cls;
    div.textContent = text;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return div;
  }

  function sendQuery() {
    var query = inputEl.value.trim();
    if (!query) return;

    addMessage(query, "user");
    inputEl.value = "";
    sendBtn.disabled = true;
    var thinking = addMessage("…", "assistant");

    var token = getToken();

    fetch(API_BASE + "/api/query", {
      method: "POST",
      headers: Object.assign(
        { "Content-Type": "application/json" },
        token ? { Authorization: "Bearer " + token } : {}
      ),
      body: JSON.stringify({ query: query, product: PRODUCT })
    })
      .then(function (res) {
        if (!res.ok) throw new Error("HTTP " + res.status);
        return res.json();
      })
      .then(function (data) {
        thinking.textContent = data.answer || "No answer returned.";
        thinking.className = "msg assistant";
      })
      .catch(function (err) {
        thinking.textContent = "Sorry, I couldn't get an answer (" + err.message + ").";
        thinking.className = "msg error";
      })
      .finally(function () {
        sendBtn.disabled = false;
      });
  }

  sendBtn.addEventListener("click", sendQuery);
  inputEl.addEventListener("keydown", function (e) {
    if (e.key === "Enter") sendQuery();
  });
})();
