/* Page publique : lecture du document, saisie des informations et signature. */
(function () {
  const API = "api/index.php";

  /* Lettres arabes, espace et tiret uniquement (ni latin, ni chiffres). */
  const ARABIC_ONLY = /^[\u0621-\u063A\u0641-\u065F\u066E-\u06D3\u06FA-\u06FF\s'\u2019-]+$/u;
  const NON_ARABIC = /[^\u0621-\u063A\u0641-\u065F\u066E-\u06D3\u06FA-\u06FF\s'\u2019-]/gu;

  function restrictToArabic(input) {
    input.setAttribute("lang", "ar");
    input.addEventListener("input", () => {
      const cleaned = input.value.replace(NON_ARABIC, "");
      if (cleaned !== input.value) {
        const at = input.selectionStart;
        input.value = cleaned;
        if (at !== null) input.setSelectionRange(at - 1, at - 1);
      }
    });
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

    restrictToArabic(document.getElementById("first-name"));
    restrictToArabic(document.getElementById("last-name"));

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
      if (!ARABIC_ONLY.test(payload.first_name))
        return fail("المرجو كتابة الاسم بالحروف العربية فقط.");
      if (!payload.last_name) return fail("المرجو إدخال النسب.");
      if (!ARABIC_ONLY.test(payload.last_name))
        return fail("المرجو كتابة النسب بالحروف العربية فقط.");
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

    refreshCount();
  }

  document.addEventListener("DOMContentLoaded", init);
})();
