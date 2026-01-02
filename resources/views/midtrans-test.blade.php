<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Midtrans Sandbox Test</title>

  <!-- Snap JS (SANDBOX) -->
  <script
    src="https://app.sandbox.midtrans.com/snap/snap.js"
    data-client-key="{{ config('midtrans.client_key') }}">
  </script>
</head>
<body>
  <h2>Midtrans Sandbox Test</h2>

  <button id="pay-button">Bayar Sekarang</button>

  <script>
    document.getElementById('pay-button').addEventListener('click', async function () {
      try {
        console.log("[1] Requesting snap token...");

        const res = await fetch("/test-midtrans", {
          method: "GET",
          headers: { "Accept": "application/json" }
        });

        // Biar kalau server ngembaliin HTML/error, kelihatan jelas
        const raw = await res.text();
        console.log("[2] Raw response:", raw);

        let data;
        try {
          data = JSON.parse(raw);
        } catch (e) {
          console.error("[ERROR] Response bukan JSON. Kemungkinan route / controller error.");
          return;
        }

        if (!data.snap_token) {
          console.error("[ERROR] snap_token tidak ada di response:", data);
          return;
        }

        console.log("[3] Snap token OK:", data.snap_token);

        window.snap.pay(data.snap_token, {
          onSuccess: function (result) {
            console.log("✅ onSuccess", result);
            alert("SUCCESS");
          },
          onPending: function (result) {
            console.log("🟡 onPending", result);
            alert("PENDING");
          },
          onError: function (result) {
            console.log("❌ onError", result);
            alert("ERROR");
          },
          onClose: function () {
            console.log("⚪ onClose: popup ditutup user");
          }
        });

      } catch (err) {
        console.error("[FATAL] JS error:", err);
      }
    });
  </script>
</body>
</html>
