/* Génération du PDF final : le document original est conservé tel quel et
   les signatures sont estampées dans le tableau de la page de signatures.
   Le texte arabe est rendu par le navigateur dans un <canvas> puis inséré
   comme image, ce qui garantit une écriture arabe correcte (liaisons + RTL)
   dans le PDF. */
(function () {
  const L = window.DOC_LAYOUT;
  let originalBytesPromise = null;
  let fontReadyPromise = null;

  function loadOriginalBytes() {
    if (!originalBytesPromise) {
      originalBytesPromise = fetch(L.pdfUrl).then((r) => {
        if (!r.ok) throw new Error("تعذّر تحميل الوثيقة الأصلية");
        return r.arrayBuffer();
      });
    }
    return originalBytesPromise;
  }

  function fontReady() {
    if (!fontReadyPromise) {
      /* Si la police locale ne peut pas être chargée, le rendu se rabat sur la
         police système : le texte arabe reste correct, seule la casse change. */
      fontReadyPromise = document.fonts
        ? document.fonts
            .load('64px "Amiri"')
            .catch(() => null)
            .then(() => document.fonts.ready)
            .catch(() => null)
        : Promise.resolve();
    }
    return fontReadyPromise;
  }

  /* Rend une chaîne de texte dans un PNG transparent. */
  function textToPngDataUrl(text, opts) {
    const options = opts || {};
    const fontPx = options.fontPx || 64;
    const family = options.family || '"Amiri", "Times New Roman", serif';
    const measure = document.createElement("canvas").getContext("2d");
    measure.font = fontPx + 'px ' + family;
    const width = Math.max(8, Math.ceil(measure.measureText(text).width) + 8);
    const height = Math.ceil(fontPx * (options.tight ? 1.15 : 1.5));

    const canvas = document.createElement("canvas");
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext("2d");
    ctx.font = fontPx + 'px ' + family;
    ctx.fillStyle = "#111111";
    ctx.textBaseline = "middle";
    ctx.textAlign = "center";
    if (ctx.direction !== undefined) ctx.direction = options.ltr ? "ltr" : "rtl";
    ctx.fillText(text, width / 2, height / 2);
    return canvas.toDataURL("image/png");
  }

  function dataUrlToBytes(dataUrl) {
    const base64 = dataUrl.slice(dataUrl.indexOf(",") + 1);
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
    return bytes;
  }

  async function embedDataUrl(pdfDoc, dataUrl) {
    const bytes = dataUrlToBytes(dataUrl);
    if (/^data:image\/jpe?g/i.test(dataUrl)) return pdfDoc.embedJpg(bytes);
    return pdfDoc.embedPng(bytes);
  }

  /* Dessine une image centrée dans une cellule, en conservant ses proportions.
     `box` est exprimé depuis le haut de la page ; conversion vers pdf-lib ici. */
  function drawInCell(page, image, box) {
    const pad = box.pad !== undefined ? box.pad : L.cellPadding;
    const availW = box.x1 - box.x0 - pad * 2;
    const availH = box.bottom - box.top - pad * 2;
    if (availW <= 0 || availH <= 0) return;
    const scale = Math.min(availW / image.width, availH / image.height);
    const w = image.width * scale;
    const h = image.height * scale;
    const x = box.x0 + pad + (availW - w) / 2;
    const topY = box.top + pad + (availH - h) / 2;
    page.drawImage(image, {
      x: x,
      y: L.pageHeight - topY - h,
      width: w,
      height: h,
    });
  }

  function rowBox(rowIndex, column) {
    const top = L.firstRowTop + rowIndex * L.rowHeight;
    return {
      x0: column.x0,
      x1: column.x1,
      top: top,
      bottom: top + L.rowHeight,
    };
  }

  function chunk(items, size) {
    const out = [];
    for (let i = 0; i < items.length; i += size) out.push(items.slice(i, i + size));
    return out;
  }

  function fullName(signer) {
    return [signer.first_name, signer.last_name].filter(Boolean).join(" ").trim();
  }

  /**
   * @param {Array<{first_name:string,last_name:string,cin:string,signature:string}>} signers
   * @param {{includeLetter?:boolean, includeClosing?:boolean}} [options]
   * @returns {Promise<Uint8Array>}
   */
  async function buildFinalPdf(signers, options) {
    const opts = Object.assign({ includeLetter: true, includeClosing: true }, options || {});
    const { PDFDocument } = window.PDFLib;
    await fontReady();

    const originalBytes = await loadOriginalBytes();
    const source = await PDFDocument.load(originalBytes);
    const out = await PDFDocument.create();
    out.setTitle("عريضة الجماعة الساللية لدوار تاوريرت — صفحة التوقيعات");

    if (opts.includeLetter) {
      const [letter] = await out.copyPages(source, [L.letterPageIndex]);
      out.addPage(letter);
    }

    const groups = chunk(signers, L.rowsPerPage);
    if (groups.length === 0) groups.push([]);

    for (let g = 0; g < groups.length; g++) {
      const [page] = await out.copyPages(source, [L.signaturePageIndex]);
      out.addPage(page);

      const numberImage = await embedDataUrl(
        out,
        textToPngDataUrl(groups.length + " / " + (g + 1), { ltr: true, fontPx: 48, tight: true })
      );
      const nb = L.pageNumberBox;
      page.drawRectangle({
        x: nb.x0,
        y: L.pageHeight - nb.bottom,
        width: nb.x1 - nb.x0,
        height: nb.bottom - nb.top,
        color: window.PDFLib.rgb(1, 1, 1),
      });
      drawInCell(page, numberImage, {
        x0: nb.x0,
        x1: nb.x1,
        top: nb.top,
        bottom: nb.bottom,
        pad: 0.5,
      });

      for (let r = 0; r < groups[g].length; r++) {
        const signer = groups[g][r];
        const nameImage = await embedDataUrl(out, textToPngDataUrl(fullName(signer)));
        drawInCell(page, nameImage, rowBox(r, L.columns.fullName));

        const cinImage = await embedDataUrl(
          out,
          textToPngDataUrl(String(signer.cin || "").toUpperCase(), { ltr: true })
        );
        drawInCell(page, cinImage, rowBox(r, L.columns.cin));

        if (signer.signature) {
          const sigImage = await embedDataUrl(out, signer.signature);
          drawInCell(page, sigImage, rowBox(r, L.columns.signature));
        }
      }
    }

    if (opts.includeClosing && source.getPageCount() > L.closingPageIndex) {
      const [closing] = await out.copyPages(source, [L.closingPageIndex]);
      out.addPage(closing);
    }

    return out.save();
  }

  function downloadBytes(bytes, filename) {
    const blob = new Blob([bytes], { type: "application/pdf" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 60000);
    return url;
  }

  window.PdfBuild = { buildFinalPdf, downloadBytes, textToPngDataUrl };
})();
