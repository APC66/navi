<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Carte Cadeau Croisière</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      background: #ffffff;
      color: #0A173D;
      width: 210mm;
      height: 297mm;
      overflow: hidden;
    }

    /* ── Bannière photo ── */
    .banner {
      width: 100%;
      height: 72mm;
      position: relative;
      background: #0A173D;
      overflow: hidden;
    }

    .banner-photo {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      opacity: 0.75;
    }

    .banner-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to bottom, rgba(10,23,61,0.35) 0%, rgba(10,23,61,0.75) 100%);
    }

    .banner-content {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 0 16mm;
    }

    .banner-logo {
      height: 36px;
      width: auto;
      margin-bottom: 8px;
    }

    .banner-badge {
      background: #FFD21F;
      color: #0A173D;
      font-size: 9px;
      font-weight: bold;
      letter-spacing: 2px;
      text-transform: uppercase;
      padding: 4px 14px;
      border-radius: 20px;
    }

    /* ── Corps ── */
    .body {
      padding: 8mm 12mm 6mm;
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    /* Titre croisière */
    .cruise-block {
      border-left: 4px solid #FFD21F;
      padding: 4mm 0 4mm 5mm;
      margin-bottom: 5mm;
    }

    .label-small {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: #5B6C9F;
      margin-bottom: 3px;
    }

    .cruise-title {
      font-size: 20px;
      font-weight: bold;
      color: #0A173D;
      line-height: 1.2;
      margin-bottom: 4px;
    }

    .season-badge {
      display: inline-block;
      background: #FFD21F;
      color: #0A173D;
      font-size: 8px;
      font-weight: bold;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 2px 8px;
      border-radius: 10px;
    }

    /* Détails passagers / options */
    .details-row {
      display: flex;
      gap: 6mm;
      margin-bottom: 5mm;
    }

    .details-col {
      flex: 1;
      background: #F5F7FC;
      border-radius: 6px;
      padding: 4mm;
    }

    .details-col-title {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: #5B6C9F;
      margin-bottom: 4px;
      font-weight: bold;
    }

    .detail-item {
      display: flex;
      justify-content: space-between;
      font-size: 10px;
      color: #0A173D;
      padding: 2px 0;
      border-bottom: 1px solid #E8ECF5;
    }

    .detail-item:last-child {
      border-bottom: none;
    }

    .detail-item .qty {
      font-weight: bold;
      color: #1C3787;
    }

    /* Message */
    .message-box {
      background: #FFFBEA;
      border: 1px solid #FFD21F;
      border-radius: 6px;
      padding: 4mm 5mm;
      margin-bottom: 5mm;
      font-size: 10px;
      color: #5B6C9F;
      font-style: italic;
      line-height: 1.5;
    }

    .message-box::before {
      content: '« ';
      color: #FFD21F;
      font-weight: bold;
      font-style: normal;
    }

    .message-box::after {
      content: ' »';
      color: #FFD21F;
      font-weight: bold;
      font-style: normal;
    }

    /* Montant libre */
    .free-block {
      text-align: center;
      padding: 6mm 0 4mm;
      margin-bottom: 5mm;
    }

    .free-label {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: #5B6C9F;
      margin-bottom: 4px;
    }

    .free-title {
      font-size: 24px;
      font-weight: bold;
      color: #0A173D;
    }

    .free-subtitle {
      font-size: 11px;
      color: #5B6C9F;
      margin-top: 3px;
    }

    /* ── Bloc code coupon ── */
    .coupon-block {
      background: #0A173D;
      border-radius: 10px;
      padding: 6mm 8mm;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 5mm;
    }

    .coupon-left {
      flex: 1;
    }

    .coupon-amount-label {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(255,255,255,0.5);
      margin-bottom: 2px;
    }

    .coupon-amount {
      font-size: 32px;
      font-weight: bold;
      color: #FFD21F;
      line-height: 1;
    }

    .coupon-amount span {
      font-size: 16px;
    }

    .coupon-expiry {
      font-size: 9px;
      color: rgba(255,255,255,0.5);
      margin-top: 4px;
    }

    .coupon-expiry strong {
      color: rgba(255,255,255,0.75);
    }

    .coupon-divider {
      width: 1px;
      height: 40px;
      background: rgba(255,210,31,0.25);
      margin: 0 8mm;
    }

    .coupon-right {
      text-align: center;
    }

    .coupon-code-label {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(255,255,255,0.5);
      margin-bottom: 5px;
    }

    .coupon-code {
      background: #FFD21F;
      color: #0A173D;
      font-size: 15px;
      font-weight: bold;
      letter-spacing: 2px;
      padding: 5px 12px;
      border-radius: 6px;
      white-space: nowrap;
    }

    /* ── Footer ── */
    .footer {
      border-top: 1px solid #E8ECF5;
      padding-top: 4mm;
      font-size: 8px;
      color: #9AA7CB;
      text-align: center;
      line-height: 1.5;
    }
  </style>
</head>
<body>

  {{-- Bannière photo --}}
  <div class="banner">
    @if (!empty($bg_image_url))
      <img class="banner-photo" src="{{ $bg_image_url }}" alt="" />
    @endif
    <div class="banner-overlay"></div>
    <div class="banner-content">
      @if (!empty($logo_url))
        <img class="banner-logo" src="{{ $logo_url }}" alt="{{ $site_name }}" />
      @endif
      <div class="banner-badge">Carte Cadeau Croisière</div>
    </div>
  </div>

  {{-- Corps --}}
  <div class="body">

    @if ($mode === 'cruise' && !empty($cruise_title))

      {{-- Croisière --}}
      <div class="cruise-block">
        <div class="label-small">Croisière offerte</div>
        <div class="cruise-title">{!! $cruise_title !!}</div>
        <div class="season-badge">{{ $season_label }}</div>
      </div>

      @if (!empty($passengers) || !empty($options))
        <div class="details-row">
          @if (!empty($passengers))
            <div class="details-col">
              <div class="details-col-title">Passagers</div>
              @foreach ($passengers as $p)
                <div class="detail-item">
                  <span>{{ $p['name'] }}</span>
                  <span class="qty">× {{ $p['qty'] }}</span>
                </div>
              @endforeach
            </div>
          @endif

          @if (!empty($options))
            <div class="details-col">
              <div class="details-col-title">Options incluses</div>
              @foreach ($options as $o)
                <div class="detail-item">
                  <span>{{ $o['name'] }}</span>
                  <span class="qty">× {{ $o['qty'] }}</span>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      @endif

    @else

      {{-- Montant libre --}}
      <div class="free-block">
        <div class="free-label">Bon d'achat</div>
        <div class="free-title">{{ number_format($amount, 0, ',', ' ') }} €</div>
        <div class="free-subtitle">Valable sur toutes nos croisières</div>
      </div>

    @endif

    @if (!empty($recipient_message))
      <div class="message-box">{{ $recipient_message }}</div>
    @endif

    {{-- Coupon --}}
    <div class="coupon-block">
      <div class="coupon-left">
        <div class="coupon-amount-label">Valeur</div>
        <div class="coupon-amount">
          {{ number_format($amount, 0, ',', ' ') }}<span> €</span>
        </div>
        @if (!empty($expiry_date))
          <div class="coupon-expiry">
            Valable jusqu'au <strong>{{ \DateTime::createFromFormat('Y-m-d', $expiry_date)?->format('d/m/Y') ?? $expiry_date }}</strong>
          </div>
        @endif
      </div>

      <div class="coupon-divider"></div>

      <div class="coupon-right">
        <div class="coupon-code-label">Votre code</div>
        <div class="coupon-code">{{ $coupon_code }}</div>
      </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
      Code à usage unique — À saisir lors de votre commande sur {{ $site_name }}.
      @if (!empty($expiry_date))
        Expire le {{ \DateTime::createFromFormat('Y-m-d', $expiry_date)?->format('d/m/Y') ?? $expiry_date }}.
      @endif
    </div>

  </div>

</body>
</html>
