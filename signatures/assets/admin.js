/* Page d'administration : liste des signatures et génération du PDF final. */
(function () {
  const API = "api/index.php";
  let password = "";
  let signatures = [];

  const els = {};

  function fullName(row) {
    return [row.first_name, row.last_name].filter(Boolean).join(" ");
  }

  async function apiGet(action) {
    const res = await fetch(API + "?action=" + action, {
      headers: { "X-Admin-Password": password },
      cache: "no-store",
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "خطأ غير متوقع.");
    return data;
  }

  async function apiPost(action, body) {
    const res = await fetch(API + "?action=" + action, {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-Admin-Password": password },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "خطأ غير متوقع.");
    return data;
  }

  function renderRows() {
    els.total.textContent = signatures.length;
    els.rows.innerHTML = "";
    signatures.forEach((row, index) => {
      const tr = document.createElement("tr");

      const num = document.createElement("td");
      num.textContent = index + 1;

      const name = document.createElement("td");
      name.textContent = fullName(row);

      const cin = document.createElement("td");
      cin.className = "cin-cell";
      cin.textContent = row.cin;

      const sig = document.createElement("td");
      const img = document.createElement("img");
      img.src = row.signature;
      img.alt = "توقيع " + fullName(row);
      sig.appendChild(img);

      const phone = document.createElement("td");
      phone.className = "cin-cell";
      phone.textContent = row.phone || "—";

      const date = document.createElement("td");
      date.textContent = new Date(row.created_at).toLocaleString("ar-MA");

      const actions = document.createElement("td");
      const del = document.createElement("button");
      del.className = "btn ghost";
      del.textContent = "حذف";
      del.addEventListener("click", async () => {
        if (!window.confirm("حذف توقيع " + fullName(row) + "؟")) return;
        try {
          await apiPost("delete", { id: row.id });
          await load();
        } catch (error) {
          setStatus(error.message);
        }
      });
      actions.appendChild(del);

      [num, name, cin, sig, phone, date, actions].forEach((cell) => tr.appendChild(cell));
      els.rows.appendChild(tr);
    });
  }

  function setStatus(message) {
    els.status.textContent = message || "";
  }

  async function load() {
    setStatus("جارٍ التحميل…");
    const data = await apiGet("list");
    signatures = data.signatures;
    renderRows();
    setStatus("");
  }

  async function buildPdf(includeLetter) {
    if (signatures.length === 0) {
      setStatus("لا توجد توقيعات بعد.");
      return;
    }
    setStatus("جارٍ إنشاء ملف PDF…");
    try {
      const bytes = await window.PdfBuild.buildFinalPdf(signatures, {
        includeLetter: includeLetter,
        includeClosing: includeLetter,
      });
      const name = includeLetter ? "العريضة-مع-التوقيعات.pdf" : "صفحات-التوقيعات.pdf";
      window.PdfBuild.downloadBytes(bytes, name);
      setStatus("تم إنشاء الملف (" + signatures.length + " توقيع).");
    } catch (error) {
      setStatus("تعذّر إنشاء الملف: " + error.message);
    }
  }

  function exportCsv() {
    const header = ["#", "الاسم", "النسب", "رقم البطاقة الوطنية", "الهاتف", "التاريخ"];
    const lines = [header.join(";")];
    signatures.forEach((row, index) => {
      lines.push(
        [index + 1, row.first_name, row.last_name, row.cin, row.phone || "", row.created_at]
          .map((value) => '"' + String(value).replace(/"/g, '""') + '"')
          .join(";")
      );
    });
    const blob = new Blob(["\uFEFF" + lines.join("\r\n")], { type: "text/csv;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "قائمة-التوقيعات.csv";
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 60000);
  }

  function init() {
    ["login-card", "data-card", "password", "login-error", "login-btn", "rows", "total", "status"].forEach(
      (id) => {
        els[id.replace(/-(\w)/g, (m, c) => c.toUpperCase())] = document.getElementById(id);
      }
    );

    els.loginBtn.addEventListener("click", async () => {
      password = els.password.value;
      els.loginError.hidden = true;
      try {
        await load();
        els.loginCard.hidden = true;
        els.dataCard.hidden = false;
        sessionStorage.setItem("signatures_admin_password", password);
      } catch (error) {
        els.loginError.textContent = error.message;
        els.loginError.hidden = false;
      }
    });

    els.password.addEventListener("keydown", (event) => {
      if (event.key === "Enter") els.loginBtn.click();
    });

    document.getElementById("build-pdf").addEventListener("click", () => buildPdf(true));
    document.getElementById("build-signatures-only").addEventListener("click", () => buildPdf(false));
    document.getElementById("export-csv").addEventListener("click", exportCsv);
    document.getElementById("reload").addEventListener("click", () => load().catch((e) => setStatus(e.message)));

    const saved = sessionStorage.getItem("signatures_admin_password");
    if (saved) {
      els.password.value = saved;
      els.loginBtn.click();
    }
  }

  document.addEventListener("DOMContentLoaded", init);
})();
