/* Pad de signature (souris, doigt, stylet) sans dépendance externe. */
(function () {
  function createSignaturePad(canvas) {
    const ctx = canvas.getContext("2d");
    let drawing = false;
    let dirty = false;
    let last = null;
    let lastMid = null;

    function resize() {
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      const rect = canvas.getBoundingClientRect();
      const data = dirty ? canvas.toDataURL("image/png") : null;
      canvas.width = Math.round(rect.width * ratio);
      canvas.height = Math.round(rect.height * ratio);
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      ctx.lineWidth = 2.6;
      ctx.lineCap = "round";
      ctx.lineJoin = "round";
      ctx.strokeStyle = "#111111";
      if (data) {
        const img = new Image();
        img.onload = () => ctx.drawImage(img, 0, 0, rect.width, rect.height);
        img.src = data;
      }
    }

    function pointOf(event) {
      const rect = canvas.getBoundingClientRect();
      return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    }

    function start(event) {
      event.preventDefault();
      drawing = true;
      last = pointOf(event);
      lastMid = last;
      ctx.beginPath();
      ctx.arc(last.x, last.y, ctx.lineWidth / 2, 0, Math.PI * 2);
      ctx.fillStyle = ctx.strokeStyle;
      ctx.fill();
      dirty = true;
    }

    /* Trace continu : chaque segment relie les milieux successifs
       avec le point capté comme point de contrôle. */
    function drawTo(point) {
      const mid = { x: (last.x + point.x) / 2, y: (last.y + point.y) / 2 };
      ctx.beginPath();
      ctx.moveTo(lastMid.x, lastMid.y);
      ctx.quadraticCurveTo(last.x, last.y, mid.x, mid.y);
      ctx.stroke();
      last = point;
      lastMid = mid;
    }

    function move(event) {
      if (!drawing) return;
      event.preventDefault();
      const events =
        typeof event.getCoalescedEvents === "function" ? event.getCoalescedEvents() : [];
      if (events.length) {
        for (const e of events) drawTo(pointOf(e));
      } else {
        drawTo(pointOf(event));
      }
    }

    function end(event) {
      if (!drawing) return;
      drawing = false;
      if (last && lastMid) {
        ctx.beginPath();
        ctx.moveTo(lastMid.x, lastMid.y);
        ctx.lineTo(last.x, last.y);
        ctx.stroke();
      }
      last = null;
      lastMid = null;
    }

    canvas.addEventListener("pointerdown", start);
    canvas.addEventListener("pointermove", move);
    window.addEventListener("pointerup", end);
    window.addEventListener("pointercancel", end);
    window.addEventListener("resize", resize);
    resize();

    function clear() {
      ctx.save();
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.restore();
      dirty = false;
    }

    /* Recadre le tracé et renvoie un PNG transparent, ou null si vide. */
    function toTrimmedDataUrl() {
      if (!dirty) return null;
      const w = canvas.width;
      const h = canvas.height;
      const pixels = ctx.getImageData(0, 0, w, h).data;
      let minX = w, minY = h, maxX = -1, maxY = -1;
      for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
          if (pixels[(y * w + x) * 4 + 3] > 8) {
            if (x < minX) minX = x;
            if (x > maxX) maxX = x;
            if (y < minY) minY = y;
            if (y > maxY) maxY = y;
          }
        }
      }
      if (maxX < 0) return null;
      const margin = 6;
      minX = Math.max(0, minX - margin);
      minY = Math.max(0, minY - margin);
      maxX = Math.min(w - 1, maxX + margin);
      maxY = Math.min(h - 1, maxY + margin);
      const out = document.createElement("canvas");
      out.width = maxX - minX + 1;
      out.height = maxY - minY + 1;
      out.getContext("2d").drawImage(canvas, minX, minY, out.width, out.height, 0, 0, out.width, out.height);
      return out.toDataURL("image/png");
    }

    return {
      clear: clear,
      isEmpty: () => !dirty,
      toDataUrl: toTrimmedDataUrl,
    };
  }

  window.createSignaturePad = createSignaturePad;
})();
