/*
 * Équivalent de `api/index.php` pour un hébergement Cloudflare Pages.
 * Répond à toutes les URL /api/... (dont /api/index.php?action=...) et stocke
 * les signatures dans une base D1 au lieu de SQLite.
 *
 * Variables d'environnement attendues (Pages → Settings → Variables) :
 *   ADMIN_PASSWORD  secret : mot de passe de la page /admin.html
 *   UNIQUE_CIN      optionnel : "0" pour autoriser plusieurs signatures par CIN
 * Binding D1 attendu : DB
 */

const MAX_SIGNATURE_BYTES = 800 * 1024;

function json(payload, status = 200) {
  return new Response(JSON.stringify(payload), {
    status,
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "Cache-Control": "no-store",
    },
  });
}

/* Le nom et le prénom doivent être écrits en caractères arabes uniquement. */
const ARABIC_ONLY = /^[\u0621-\u063A\u0641-\u065F\u066E-\u06D3\u06FA-\u06FF\s'\u2019-]+$/u;

function cleanText(value, maxLength) {
  return String(value ?? "")
    .trim()
    .replace(/\s+/gu, " ")
    .slice(0, maxLength);
}

/** Comparaison à durée constante, pour ne pas fuiter le mot de passe. */
function sameSecret(a, b) {
  const left = new TextEncoder().encode(String(a ?? ""));
  const right = new TextEncoder().encode(String(b ?? ""));
  if (left.length !== right.length) return false;
  let diff = 0;
  for (let i = 0; i < left.length; i++) diff |= left[i] ^ right[i];
  return diff === 0;
}

function adminError(request, env) {
  const url = new URL(request.url);
  const sent = request.headers.get("X-Admin-Password") || url.searchParams.get("password") || "";
  if (!env.ADMIN_PASSWORD || !sameSecret(env.ADMIN_PASSWORD, sent)) {
    return json({ error: "كلمة السر غير صحيحة." }, 401);
  }
  return null;
}

async function body(request) {
  try {
    const data = await request.json();
    return data && typeof data === "object" ? data : {};
  } catch (error) {
    return {};
  }
}

async function ensureSchema(db) {
  await db
    .prepare(
      `CREATE TABLE IF NOT EXISTS signatures (
         id INTEGER PRIMARY KEY AUTOINCREMENT,
         first_name TEXT NOT NULL,
         last_name TEXT NOT NULL,
         cin TEXT NOT NULL,
         phone TEXT,
         signature TEXT NOT NULL,
         ip TEXT,
         user_agent TEXT,
         created_at TEXT NOT NULL
       )`
    )
    .run();
}

async function handle(request, env) {
  const db = env.DB;
  if (!db) return json({ error: "خطأ في الخادم، المرجو المحاولة مرة أخرى." }, 500);
  await ensureSchema(db);

  const url = new URL(request.url);
  const action = url.searchParams.get("action") || "";
  const uniqueCin = env.UNIQUE_CIN !== "0";

  if (action === "count") {
    const row = await db.prepare("SELECT COUNT(*) AS c FROM signatures").first();
    return json({ count: Number(row.c) });
  }

  if (action === "submit") {
    if (request.method !== "POST") return json({ error: "طريقة غير مسموحة." }, 405);
    const data = await body(request);
    const firstName = cleanText(data.first_name, 80);
    const lastName = cleanText(data.last_name, 80);
    const cin = cleanText(data.cin, 20).toUpperCase().replace(/ /g, "");
    const phone = cleanText(data.phone, 20);
    const signature = String(data.signature ?? "");

    if (!firstName || !lastName || !cin) {
      return json({ error: "المرجو تعبئة الاسم والنسب ورقم البطاقة الوطنية." }, 422);
    }
    if (!ARABIC_ONLY.test(firstName) || !ARABIC_ONLY.test(lastName)) {
      return json({ error: "المرجو كتابة الاسم والنسب بالحروف العربية فقط." }, 422);
    }
    if (!/^[A-Z0-9]{4,20}$/.test(cin)) {
      return json({ error: "رقم البطاقة الوطنية غير صحيح (مثال: JB123456)." }, 422);
    }
    if (!/^data:image\/png;base64,[A-Za-z0-9+/=]+$/.test(signature)) {
      return json({ error: "التوقيع غير صالح، المرجو إعادة التوقيع." }, 422);
    }
    if (signature.length > MAX_SIGNATURE_BYTES) {
      return json({ error: "حجم التوقيع كبير جدًا، المرجو إعادة التوقيع." }, 413);
    }
    if (uniqueCin) {
      const row = await db.prepare("SELECT COUNT(*) AS c FROM signatures WHERE cin = ?").bind(cin).first();
      if (Number(row.c) > 0) {
        return json({ error: "تم تسجيل توقيع بهذا الرقم الوطني من قبل." }, 409);
      }
    }

    const inserted = await db
      .prepare(
        `INSERT INTO signatures
           (first_name, last_name, cin, phone, signature, ip, user_agent, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         RETURNING id`
      )
      .bind(
        firstName,
        lastName,
        cin,
        phone,
        signature,
        request.headers.get("CF-Connecting-IP") || "",
        (request.headers.get("User-Agent") || "").slice(0, 255),
        new Date().toISOString()
      )
      .first();

    return json({ ok: true, id: Number(inserted.id) }, 201);
  }

  if (action === "list") {
    const denied = adminError(request, env);
    if (denied) return denied;
    const { results } = await db
      .prepare(
        `SELECT id, first_name, last_name, cin, phone, signature, created_at
           FROM signatures ORDER BY id ASC`
      )
      .all();
    return json({ signatures: results });
  }

  if (action === "delete") {
    const denied = adminError(request, env);
    if (denied) return denied;
    if (request.method !== "POST") return json({ error: "طريقة غير مسموحة." }, 405);
    const id = Number((await body(request)).id || 0);
    if (!Number.isInteger(id) || id <= 0) return json({ error: "معرّف غير صحيح." }, 422);
    const result = await db.prepare("DELETE FROM signatures WHERE id = ?").bind(id).run();
    return json({ ok: true, deleted: result.meta.changes });
  }

  return json({ error: "طلب غير معروف." }, 404);
}

export async function onRequest({ request, env }) {
  try {
    return await handle(request, env);
  } catch (error) {
    console.error("[signatures]", error && error.message);
    return json({ error: "خطأ في الخادم، المرجو المحاولة مرة أخرى." }, 500);
  }
}
