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
      height: 65mm;
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

    .banner-badge {
      background: #0A173D;
      color: #FFD21F;
      font-size: 26px;
      font-weight: bold;
      letter-spacing: 2px;
      padding-top:2mm;
      padding-bottom:2mm;
      text-transform: uppercase;
      text-align:center;
      width:100%;
    }

    .banner-badge span{
      color:white;
    }

    /* ── Corps ── */
    .body {
      padding: 4mm 6mm 3mm;
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
      font-size: 12px;
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
      margin-bottom: 2mm;
    }

    .details-col {
      flex: 1;
      background: #F5F7FC;
      border-radius: 6px;
      padding: 2mm;
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

    /* Coordonnées */
    .people-table{
      width: 100%;
      margin-bottom: 5mm;
    }

    .people-row {
      margin-bottom: 5mm;
    }

    .people-col {
      background: #F5F7FC;
      border-radius: 6px;
      padding: 3mm 4mm;
    }

    .people-col-title {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: #5B6C9F;
      margin-bottom: 3px;
      font-weight: bold;
    }

    .people-col-name {
      font-size: 13px;
      font-weight: bold;
      color: #0A173D;
    }

    .people-col-email {
      font-size: 9px;
      color: #5B6C9F;
      margin-top: 2px;
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
      padding: 3mm 4mm;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2mm;
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
      margin-top: 1mm;
    }

    .coupon-expiry strong {
      color: rgba(255,255,255,0.75);
    }

    .coupon-divider {
      width: 1px;
      height: 40px;
      background: rgba(255,210,31,0.25);
      margin: 0 4mm;
    }

    .coupon-right {
      text-align: center;
    }

    .coupon-code-label {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(255,255,255,0.5);
      margin-bottom: 1mm;
    }

    .coupon-code {
      background: #FFD21F;
      color: #0A173D;
      font-size: 15px;
      font-weight: bold;
      letter-spacing: 2px;
      padding: 2mm 4mm;
      border-radius: 6px;
      white-space: nowrap;
    }

    .mentions-legales-title{
      font-weight:bold;
    }

    .mentions-legales-content{
      font-size:9px;
    }

    /* ── Footer ── */
    .footer {
      border-top: 1px solid #E8ECF5;
      padding-top: 2mm;
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
  </div>
  <div class="banner-badge"><span>Carte</span> Cadeau</div>


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

    {{-- Coordonnées bénéficiaire & offrant --}}
    <table class="people-table" style="width: 100%">
      <tr class="people-row">
        <td colspan="1" class="people-col">
          <div class="people-col-title">Pour</div>
          <div class="people-col-name">{{ trim($recipient_first_name.' '.$recipient_last_name) ?: '—' }}</div>
          @if (!empty($recipient_phone))
            <div class="people-col-email">{{ $recipient_phone }}</div>
          @endif
        </td>
        <td colspan="1" class="people-col">
          <div class="people-col-title">Offert par</div>
          <div class="people-col-name">{{ trim($buyer_first_name.' '.$buyer_last_name) }}</div>
        </td>
      </tr>
    </table>

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

      <div class="coupon-right">
        <div class="coupon-code-label">Votre code</div>
        <div class="coupon-code">{{ $coupon_code }}</div>
      </div>
    </div>
    <div class="mentions-legales">
      <div class="mentions-legales-title">
        Comment utiliser ma carte cadeau ?
      </div>
      <div class="mentions-legales-content">
          Veuillez-vous référer au "Planning & Réservation" du menu principal de notre site web wwww.navivoile.com pour connaître les dates des sorties en mer.
          Pour profiter de cette carte cadeau, utilisez le code inscrit ci-dessus.
          Vous devrez utiliser ce code lorsque vous réserverez votre croisière sur notre site web wwww.navivoile.com (onglet "Planning & Réservation") en l'insérant dans le champ "code promo" (puis le valider).
          Vous pourrez si vous le souhaitez commander n'importe quelle autre croisière avec ce même code.
          Le montant de cette carte cadeau sera déduit sur la nouvelle commande (valable uniquement pour toute commande supérieure ou égale au montant total du bon cadeau offert).
          Nous vous conseillons vivement de réserver au plus vite votre croisière si elle est déjà en ligne.
          Aucun remboursement de carte cadeau ne sera accepté quel que soit le motif, sauf en cas de problème technique ou d'avarie avec impossibilité pour NAVIVOILE d'assurer la croisière.
          Lorsque vous réserverez prochainement votre croisière sur notre site web, toutes les informations relatives au point d'embarquement et horaires vous seront précisées sur votre bon de commande. Nous vous remercions pour votre confiance et à très bientôt!
          Pour tout renseignement complémentaire : Tél. 06 23 20 69 76 - Courriel contact@navivoile.com
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
