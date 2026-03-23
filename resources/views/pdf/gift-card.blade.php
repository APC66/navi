<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Carte Cadeau Croisière</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      background: #0A173D;
      color: #ffffff;
      width: 297mm;
      height: 210mm;
      overflow: hidden;
    }

    .card {
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #0A173D 0%, #1C3787 50%, #101F4D 100%);
      position: relative;
      display: flex;
      flex-direction: column;
      padding: 30px 40px;
    }

    /* Décoration vague */
    .wave-decoration {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 80px;
      background: rgba(255, 210, 31, 0.08);
      border-radius: 50% 50% 0 0 / 30px 30px 0 0;
    }

    .wave-decoration-2 {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 50px;
      background: rgba(255, 210, 31, 0.05);
      border-radius: 50% 50% 0 0 / 20px 20px 0 0;
    }

    /* Header */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
      border-bottom: 1px solid rgba(255, 210, 31, 0.3);
      padding-bottom: 15px;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo-area img {
      height: 40px;
      width: auto;
    }

    .site-name {
      font-size: 20px;
      font-weight: bold;
      color: #FFD21F;
      letter-spacing: 2px;
      text-transform: uppercase;
    }

    .gift-badge {
      background: #FFD21F;
      color: #0A173D;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Corps principal */
    .body {
      display: flex;
      gap: 30px;
      flex: 1;
    }

    /* Colonne gauche */
    .left-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .label-small {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(255, 255, 255, 0.5);
      margin-bottom: 4px;
    }

    .cruise-title {
      font-size: 22px;
      font-weight: bold;
      color: #FFD21F;
      margin-bottom: 6px;
      line-height: 1.2;
    }

    .season-badge {
      display: inline-block;
      background: rgba(255, 210, 31, 0.15);
      border: 1px solid rgba(255, 210, 31, 0.4);
      color: #FFD21F;
      padding: 3px 10px;
      border-radius: 10px;
      font-size: 10px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 16px;
    }

    .details-section {
      margin-bottom: 12px;
    }

    .details-title {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(255, 255, 255, 0.5);
      margin-bottom: 6px;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      font-size: 11px;
      color: rgba(255, 255, 255, 0.85);
      padding: 2px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .detail-row:last-child {
      border-bottom: none;
    }

    .detail-row .qty {
      font-weight: bold;
      color: #ffffff;
    }

    /* Message personnalisé */
    .message-box {
      background: rgba(255, 255, 255, 0.06);
      border-left: 3px solid #FFD21F;
      border-radius: 0 8px 8px 0;
      padding: 10px 14px;
      margin-top: 12px;
      font-size: 11px;
      color: rgba(255, 255, 255, 0.8);
      font-style: italic;
      line-height: 1.5;
    }

    /* Colonne droite — Code coupon */
    .right-col {
      width: 200px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 210, 31, 0.2);
      border-radius: 16px;
      padding: 20px 16px;
      text-align: center;
    }

    .amount-label {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(255, 255, 255, 0.5);
      margin-bottom: 4px;
    }

    .amount-value {
      font-size: 36px;
      font-weight: bold;
      color: #FFD21F;
      line-height: 1;
      margin-bottom: 16px;
    }

    .amount-currency {
      font-size: 18px;
    }

    .divider {
      width: 100%;
      height: 1px;
      background: rgba(255, 210, 31, 0.2);
      margin: 12px 0;
    }

    .code-label {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(255, 255, 255, 0.5);
      margin-bottom: 8px;
    }

    .coupon-code {
      background: #FFD21F;
      color: #0A173D;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: bold;
      letter-spacing: 2px;
      word-break: break-all;
      margin-bottom: 12px;
    }

    .expiry-label {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: rgba(255, 255, 255, 0.4);
      margin-bottom: 3px;
    }

    .expiry-value {
      font-size: 11px;
      font-weight: bold;
      color: rgba(255, 255, 255, 0.7);
    }

    /* Footer */
    .footer {
      margin-top: 15px;
      padding-top: 10px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      font-size: 9px;
      color: rgba(255, 255, 255, 0.3);
      text-align: center;
    }

    /* Mode montant libre */
    .free-amount-title {
      font-size: 28px;
      font-weight: bold;
      color: #FFD21F;
      margin-bottom: 8px;
    }

    .free-subtitle {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 16px;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="wave-decoration"></div>
    <div class="wave-decoration-2"></div>

    {{-- Header --}}
    <div class="header">
      <div class="logo-area">
        @if (!empty($logo_url))
          <img src="{{ $logo_url }}" alt="{{ $site_name }}" />
        @else
          <span class="site-name">{{ $site_name }}</span>
        @endif
      </div>
      <div class="gift-badge">🎁 Carte Cadeau Croisière</div>
    </div>

    {{-- Corps --}}
    <div class="body">

      {{-- Colonne gauche --}}
      <div class="left-col">

        @if ($mode === 'cruise' && !empty($cruise_title))
          {{-- Mode croisière --}}
          <div class="label-small">Croisière offerte</div>
          <div class="cruise-title">{{ $cruise_title }}</div>
          <div class="season-badge">{{ $season_label }}</div>

          @if (!empty($passengers))
            <div class="details-section">
              <div class="details-title">Passagers</div>
              @foreach ($passengers as $p)
                <div class="detail-row">
                  <span>{{ $p['name'] }}</span>
                  <span class="qty">× {{ $p['qty'] }}</span>
                </div>
              @endforeach
            </div>
          @endif

          @if (!empty($options))
            <div class="details-section">
              <div class="details-title">Options incluses</div>
              @foreach ($options as $o)
                <div class="detail-row">
                  <span>{{ $o['name'] }}</span>
                  <span class="qty">× {{ $o['qty'] }}</span>
                </div>
              @endforeach
            </div>
          @endif

        @else
          {{-- Mode montant libre --}}
          <div class="label-small">Carte cadeau</div>
          <div class="free-amount-title">Bon d'achat</div>
          <div class="free-subtitle">Valable sur toutes nos croisières</div>
        @endif

        @if (!empty($recipient_message))
          <div class="message-box">
            "{{ $recipient_message }}"
          </div>
        @endif

      </div>

      {{-- Colonne droite — Code --}}
      <div class="right-col">
        <div class="amount-label">Valeur</div>
        <div class="amount-value">
          {{ number_format($amount, 0, ',', ' ') }}<span class="amount-currency"> €</span>
        </div>

        <div class="divider"></div>

        <div class="code-label">Votre code</div>
        <div class="coupon-code">{{ $coupon_code }}</div>

        @if (!empty($expiry_date))
          <div class="expiry-label">Valable jusqu'au</div>
          <div class="expiry-value">
            {{ \DateTime::createFromFormat('Y-m-d', $expiry_date)?->format('d/m/Y') ?? $expiry_date }}
          </div>
        @endif
      </div>

    </div>

    {{-- Footer --}}
    <div class="footer">
      Ce code est à usage unique. Saisissez-le lors de votre commande sur {{ $site_name }}.
      @if (!empty($expiry_date))
        — Expire le {{ \DateTime::createFromFormat('Y-m-d', $expiry_date)?->format('d/m/Y') ?? $expiry_date }}.
      @endif
    </div>

  </div>
</body>
</html>
