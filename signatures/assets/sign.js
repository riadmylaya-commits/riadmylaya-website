/* Page publique : lecture du document, saisie des informations et signature. */
(function () {
  const API = "api/index.php";
  const L = window.DOC_LAYOUT;

  async function renderDocument() {
    const host = document.getElementById("doc-pages");
    try {
      pdfjsLib.GlobalWorkerOptions.workerSrc = "vendor/pdf.worker.min.js";
      const pdf = await pdfjsLib.getDocument(L.pdfUrl).promise;
      host.innerHTML = "";
      for (let n = 1; n <= pdf.numPages; n++) {
        const page = await pdf.getPage(n);
        const viewport = page.getViewport({ scale: 2 });
        const canvas = document.createElement("canvas");
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        host.appendChild(canvas);
        await page.render({ canvasContext: canvas.getContext("2d"), viewport: viewport }).promise;
      }
    } catch (error) {
      host.innerHTML =
        '<p class="error">تعذّر عرض الوثيقة داخل الصفحة. يمكنك تحميلها من الرابط أسفله.</p>';
    }
  }

  async function refreshCount() {
    try {
      const res = await fetch(API + "?action=count", { cache: "no-store" });
      const data = await res.json();
      document.getElementById("count").textContent = data.count;
    } catch (error) {
      document.getElementById("count").textContent = "—";
    }
  }

  function init() {
    const pad = window.createSignaturePad(document.getElementById("pad"));
    const form = document.getElementById("sign-form");
    const errorBox = document.getElementById("form-error");
    const submitBtn = document.getElementById("submit-btn");

    document.getElementById("clear-pad").addEventListener("click", () => pad.clear());

    function fail(message) {
      errorBox.textContent = message;
      errorBox.hidden = false;
      errorBox.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      errorBox.hidden = true;

      const payload = {
        first_name: document.getElementById("first-name").value.trim(),
        last_name: document.getElementById("last-name").value.trim(),
        cin: document.getElementById("cin").value.trim().toUpperCase(),
        phone: document.getElementById("phone").value.trim(),
      };

      if (!payload.first_name) return fail("المرجو إدخال الاسم.");
      if (!payload.last_name) return fail("المرجو إدخال النسب.");
      if (!payload.cin) return fail("المرجو إدخال رقم البطاقة الوطنية.");
      if (!document.getElementById("consent").checked)
        return fail("المرجو الموافقة على التصريح قبل الإرسال.");

      const signature = pad.toDataUrl();
      if (!signature) return fail("المرجو التوقيع داخل الإطار المخصص للتوقيع.");
      payload.signature = signature;

      submitBtn.disabled = true;
      submitBtn.textContent = "جارٍ الإرسال…";
      try {
        const res = await fetch(API + "?action=submit", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || "تعذّر إرسال التوقيع.");

        form.hidden = true;
        document.getElementById("success").hidden = false;
        refreshCount();

        const bytes = await window.PdfBuild.buildFinalPdf([payload]);
        const blob = new Blob([bytes], { type: "application/pdf" });
        const link = document.getElementById("download-mine");
        link.href = URL.createObjectURL(blob);
        link.download = "توقيع-" + payload.first_name + "-" + payload.last_name + ".pdf";
      } catch (error) {
        fail(error.message);
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "إرسال التوقيع";
      }
    });

    renderDocument();
    refreshCount();
  }

  document.addEventListener("DOMContentLoaded", init);
})();
