<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class GiftCardService
{
    /**
     * Génère un coupon WooCommerce pour une carte cadeau et le sauvegarde en meta sur l'order item.
     *
     * @param  \WC_Order_Item_Product  $item
     * @return string|null Le code coupon généré, ou null en cas d'échec
     */
    public function generateCoupon(\WC_Order $order, $item): ?string
    {
        // Éviter la double génération
        if ($item->get_meta('_gc_coupon_code')) {
            return $item->get_meta('_gc_coupon_code');
        }

        $amount = floatval($item->get_meta('_gc_amount'));
        if ($amount <= 0) {
            return null;
        }

        // Génération du code unique
        $couponCode = 'CC-'.strtoupper(uniqid());

        // Date d'expiration : +1 an depuis la date de commande
        $orderDate = $order->get_date_created();
        $expiryDate = null;
        if ($orderDate) {
            $expiry = clone $orderDate;
            $expiry->modify('+1 year');
            $expiryDate = $expiry->format('Y-m-d');
        }

        // Création du coupon WooCommerce
        $coupon = new \WC_Coupon;
        $coupon->set_code($couponCode);
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_amount($amount);
        $coupon->set_usage_limit(1);
        $coupon->set_usage_limit_per_user(1);
        $coupon->set_individual_use(false);
        $coupon->set_description(
            sprintf(
                'Carte cadeau générée automatiquement — Commande #%d',
                $order->get_id()
            )
        );

        if ($expiryDate) {
            $coupon->set_date_expires($expiryDate);
        }

        // Pas de restriction product_ids (valable sur tout le site)
        $coupon->set_product_ids([]);
        $coupon->set_excluded_product_ids([]);

        $couponId = $coupon->save();

        if (! $couponId) {
            return null;
        }

        // Sauvegarde du code en meta sur l'order item
        $item->update_meta_data('_gc_coupon_code', $couponCode);
        $item->update_meta_data('_gc_coupon_expiry', $expiryDate ?? '');
        $item->save();

        return $couponCode;
    }

    /**
     * Génère le PDF de la carte cadeau et l'envoie par email au destinataire.
     *
     * @param  \WC_Order_Item_Product  $item
     */
    public function sendGiftCardEmail(\WC_Order $order, $item): void
    {
        error_log('[GiftCard] sendGiftCardEmail called');

        $couponCode = $item->get_meta('_gc_coupon_code');
        if (! $couponCode) {
            return;
        }

        $sendToSelf = $item->get_meta('_gc_send_to_self') === '1';
        $recipientEmail = $sendToSelf
            ? $order->get_billing_email()
            : sanitize_email($item->get_meta('_gc_recipient_email'));

        if (! is_email($recipientEmail)) {
            return;
        }

        // Préparation des données pour la vue PDF
        $mode = $item->get_meta('_gc_mode') ?: 'cruise';
        $cruiseId = absint($item->get_meta('_gc_cruise_id'));
        $season = $item->get_meta('_gc_season');
        $passengersJson = $item->get_meta('_gc_passengers');
        $optionsJson = $item->get_meta('_gc_options');
        $amount = floatval($item->get_meta('_gc_amount'));
        $recipientMessage = $item->get_meta('_gc_recipient_message');
        $expiryDate = $item->get_meta('_gc_coupon_expiry');

        $passengers = $passengersJson ? json_decode($passengersJson, true) : [];
        $options = $optionsJson ? json_decode($optionsJson, true) : [];

        // Enrichissement des données passagers avec les noms de termes
        $passengersData = [];
        foreach ($passengers as $typeId => $qty) {
            if ($qty <= 0) {
                continue;
            }
            $term = get_term(absint($typeId), 'passenger_type');
            $passengersData[] = [
                'name' => ! is_wp_error($term) ? $term->name : 'Passager',
                'qty' => $qty,
            ];
        }

        // Enrichissement des données options avec les noms de termes
        $optionsData = [];
        foreach ($options as $typeId => $qty) {
            if ($qty <= 0) {
                continue;
            }
            $term = get_term(absint($typeId), 'extra_option_type');
            $optionsData[] = [
                'name' => ! is_wp_error($term) ? $term->name : 'Option',
                'qty' => $qty,
            ];
        }

        $viewData = [
            'mode' => $mode,
            'cruise_title' => $cruiseId ? get_the_title($cruiseId) : '',
            'season_label' => $season === 'high' ? 'Haute Saison' : 'Basse Saison',
            'passengers' => $passengersData,
            'options' => $optionsData,
            'amount' => $amount,
            'coupon_code' => $couponCode,
            'expiry_date' => $expiryDate,
            'recipient_message' => $recipientMessage,
            'logo_url' => get_theme_file_uri('public/images/logo.png'),
            'site_name' => get_bloginfo('name'),
            'bg_image_url' => get_field('gift_card_bg_image', 'option') ?: '',
        ];

        // Génération du HTML via la vue Blade
        $html = \Roots\view('pdf.gift-card', $viewData)->render();

        // Génération du PDF via Dompdf
        $pdfPath = $this->generatePdf($html, $couponCode);

        if (! $pdfPath) {
            return;
        }

        // Envoi de l'email
        $subject = sprintf(
            '[%s] Votre carte cadeau croisière — Code : %s',
            get_bloginfo('name'),
            $couponCode
        );

        $body = sprintf(
            "Bonjour,\n\nVeuillez trouver en pièce jointe votre carte cadeau croisière.\n\nCode : %s\nMontant : %s €\nValable jusqu'au : %s\n\nMerci et bonne navigation !\n%s",
            $couponCode,
            number_format($amount, 2, ',', ' '),
            $expiryDate ? date_i18n(get_option('date_format'), strtotime($expiryDate)) : 'N/A',
            get_bloginfo('name')
        );

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        $attachments = [$pdfPath];

        wp_mail($recipientEmail, $subject, $body, $headers, $attachments);

        // Nettoyage du fichier temporaire après envoi
        if (file_exists($pdfPath)) {
            @unlink($pdfPath);
        }
    }

    /**
     * Génère le PDF à partir du HTML et retourne le chemin du fichier temporaire.
     */
    private function generatePdf(string $html, string $couponCode): ?string
    {
        try {
            error_log('[GiftCardService] HTML length: '.strlen($html));

            $options = new Options;
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfContent = $dompdf->output();

            error_log('[GiftCardService] PDF size: '.strlen($pdfContent));

            $uploadDir = wp_upload_dir();
            $tmpDir = trailingslashit($uploadDir['basedir']).'gift-cards/';

            error_log('[GiftCardService] tmpDir: '.$tmpDir);
            error_log('[GiftCardService] tmpDir writable: '.(is_writable(dirname($tmpDir)) ? 'yes' : 'no'));

            if (! file_exists($tmpDir)) {
                wp_mkdir_p($tmpDir);
            }

            $filename = 'carte-cadeau-'.sanitize_file_name($couponCode).'.pdf';
            $pdfPath = $tmpDir.$filename;

            $written = file_put_contents($pdfPath, $pdfContent);

            // 4. Vérifier l'écriture
            error_log('[GiftCardService] Bytes written: '.($written === false ? 'FAILED' : $written));

            return $pdfPath;

        } catch (\Exception $e) {
            error_log('[GiftCardService] Erreur génération PDF : '.$e->getMessage());

            return null;
        }
    }
}
