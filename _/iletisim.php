<?php require_once '../config.php'; include 'header.php'; ?>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:30px;max-width:1000px;margin:0 auto;">
    <div class="card">
        <h2 style="color:#1B365D;margin-bottom:20px;">İLETİŞİM <span style="color:#D4AF37;">MERKEZİ</span></h2>

        <div class="card" style="background:#f9f9f9;margin-bottom:15px;">
            <h4 style="color:#1B365D;font-size:13px;font-weight:bold;text-transform:uppercase;margin-bottom:8px;">📍 Genel Merkez</h4>
            <p style="font-size:14px;">Adile Naşit Caddesi, Olcay Sevalista Residence Kat 2,<br>Esenyurt / İSTANBUL</p>
        </div>

        <div class="card" style="background:#f9f9f9;margin-bottom:15px;">
            <h4 style="color:#1B365D;font-size:13px;font-weight:bold;text-transform:uppercase;margin-bottom:8px;">✉️ E-Posta</h4>
            <a href="mailto:kurt.hakki@gmail.com" style="color:#2563eb;font-size:15px;">kurt.hakki@gmail.com</a>
        </div>

        <div class="card" style="background:#e8f9ee;border:1px solid #bbf7d0;">
            <h4 style="color:#166534;font-size:13px;font-weight:bold;text-transform:uppercase;margin-bottom:8px;">📱 WhatsApp</h4>
            <a href="https://wa.me/905333715577" target="_blank" style="color:#15803d;font-size:22px;font-weight:bold;">0533 371 55 77</a>
            <p style="color:#16a34a;font-size:12px;margin-top:5px;">7/24 Borsa ve Sevkiyat Destek</p>
        </div>
    </div>

    <div class="card" style="background:#1B365D;">
        <h3 style="color:#D4AF37;font-size:22px;margin-bottom:24px;">Bize Mesaj Gönderin</h3>
        <form method="POST" action="/iletisim-gonder.php" style="display:flex;flex-direction:column;gap:15px;">
            <input type="text" name="ad" placeholder="Adınız Soyadınız" required
                style="padding:13px;border-radius:6px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:white;font-size:15px;font-family:inherit;">
            <input type="email" name="email" placeholder="E-Posta Adresiniz" required
                style="padding:13px;border-radius:6px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:white;font-size:15px;font-family:inherit;">
            <textarea name="mesaj" placeholder="Mesajınız..." rows="5" required
                style="padding:13px;border-radius:6px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:white;font-size:15px;font-family:inherit;resize:vertical;"></textarea>
            <button type="submit"
                style="padding:14px;background:#D4AF37;color:#1B365D;border:none;border-radius:6px;font-weight:900;font-size:16px;cursor:pointer;text-transform:uppercase;">
                Gönder
            </button>
        </form>
    </div>
</div>

<style>@media(max-width:700px){.grid{grid-template-columns:1fr!important;}}</style>
<?php include 'footer.php'; ?>
