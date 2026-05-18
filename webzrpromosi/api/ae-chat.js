const OPENAI_API_URL = "https://api.openai.com/v1/responses";
const DEFAULT_MODEL = "gpt-4.1-mini";

function json(statusCode, body) {
  return {
    statusCode,
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "Cache-Control": "no-store"
    },
    body: JSON.stringify(body)
  };
}

function parseBody(event) {
  if (!event.body) return {};
  try {
    return JSON.parse(event.body);
  } catch (error) {
    return null;
  }
}

function cleanMenu(menu) {
  return {
    name: String(menu.name || "").slice(0, 80),
    price: Number(menu.price || 0),
    description: String(menu.description || "").slice(0, 180),
    status: String(menu.status || "Tersedia").slice(0, 40),
    category: String(menu.category || "").slice(0, 40),
    rating: String(menu.rating || "").slice(0, 12)
  };
}

function extractOutputText(data) {
  if (typeof data.output_text === "string" && data.output_text.trim()) {
    return data.output_text.trim();
  }

  const chunks = [];
  for (const item of data.output || []) {
    for (const content of item.content || []) {
      if (typeof content.text === "string") chunks.push(content.text);
    }
  }

  return chunks.join("\n").trim();
}

module.exports = async function handler(req, res) {
  const method = req.method || "GET";

  if (method === "OPTIONS") {
    res.status(204).end();
    return;
  }

  if (method !== "POST") {
    res.status(405).json({ error: "Method not allowed" });
    return;
  }

  const apiKey = process.env.OPENAI_API_KEY;
  if (!apiKey) {
    res.status(503).json({
      error: "AE belum aktif. OPENAI_API_KEY belum disetel di environment Vercel."
    });
    return;
  }

  const body = req.body && typeof req.body === "object" ? req.body : parseBody(req);
  if (!body) {
    res.status(400).json({ error: "Request body tidak valid." });
    return;
  }

  const message = String(body.message || "").trim().slice(0, 700);
  if (!message) {
    res.status(400).json({ error: "Pesan tidak boleh kosong." });
    return;
  }

  const menus = Array.isArray(body.menus) ? body.menus.slice(0, 24).map(cleanMenu) : [];
  const history = Array.isArray(body.history)
    ? body.history.slice(-8).map((item) => ({
        role: item.role === "assistant" ? "assistant" : "user",
        content: String(item.content || "").slice(0, 500)
      }))
    : [];

  const menuContext = menus.length
    ? menus.map((menu) => {
        const price = menu.price ? `Rp${menu.price.toLocaleString("id-ID")}` : "harga belum tersedia";
        return `- ${menu.name}: ${price}, status ${menu.status}, kategori ${menu.category || "menu"}, rating ${menu.rating || "-"}. ${menu.description}`;
      }).join("\n")
    : "- Menu belum berhasil dimuat. Arahkan pengunjung melihat menu di halaman atau WhatsApp.";

  const developerPrompt = `
Kamu adalah AE, asisten virtual resmi untuk website Pondok Es Teller ZR / Esteller ZR di Kalumbuk, Kota Padang.
Gaya AE: ramah, futuristik, sopan, singkat, segar, dan khas brand Esteller ZR.
Tugas utama: bantu pengunjung soal menu, harga, lokasi, jam buka, promo, delivery, cara pesan, dan rekomendasi minuman.

Data bisnis:
- Nama brand: Pondok Es Teller ZR / Esteller ZR
- Lokasi: Jl. Kalumbuk No21, Kota Padang, Sumatera Barat
- Jam buka: setiap hari 10.00 - 22.00 WIB
- WhatsApp utama: 0813-7411-0444
- WhatsApp kedua: 0813-6348-9111
- Delivery: area Kalumbuk, Kuranji, Siteba, dan area Padang terdekat. Ongkir dikonfirmasi sesuai jarak.
- Pembayaran: cash, transfer, QRIS jika tersedia saat konfirmasi.

Menu saat ini:
${menuContext}

Aturan jawaban:
- Jawab dalam Bahasa Indonesia.
- Maksimal 4 kalimat pendek kecuali pengguna minta daftar menu.
- Jangan mengarang promo, stok, atau ongkir pasti. Jika belum pasti, arahkan untuk konfirmasi via WhatsApp.
- Jika pengguna ingin pesan, arahkan ke form pesanan atau WhatsApp.
- Jangan membahas API key, sistem internal, atau detail teknis backend.
`.trim();

  const input = [
    { role: "developer", content: developerPrompt },
    ...history,
    { role: "user", content: message }
  ];

  try {
    const response = await fetch(OPENAI_API_URL, {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${apiKey}`,
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        model: process.env.OPENAI_MODEL || DEFAULT_MODEL,
        input,
        max_output_tokens: 260
      })
    });

    const data = await response.json();
    if (!response.ok) {
      console.error("OpenAI API error:", data);
      res.status(502).json({ error: "AE sedang sibuk. Coba lagi sebentar ya." });
      return;
    }

    const reply = extractOutputText(data);
    res.status(200).json({
      reply: reply || "AE belum menemukan jawaban yang pas. Coba tanya dengan kalimat lain ya.",
      model: data.model || process.env.OPENAI_MODEL || DEFAULT_MODEL
    });
  } catch (error) {
    console.error("AE chat error:", error);
    res.status(500).json({ error: "AE belum bisa tersambung. Coba lagi sebentar ya." });
  }
};
