<?php

namespace App\Http\Controllers\Api;

use App\Models\Sailing;
use WP_REST_Request;

class CancellationController
{
    public function analyze(WP_REST_Request $request)
    {
        $sailingId = (int) $request->get_param('sailing_id');
        $sailing = Sailing::find($sailingId);

        if (! $sailing) {
            return new \WP_Error('not_found', 'Départ introuvable', ['status' => 404]);
        }

        $impactedOrders = $sailing->getOrders();

        $totalPassengers = 0;
        foreach ($impactedOrders as $orderData) {
            $totalPassengers += $orderData['passenger_count'];
        }

        return [
            'sailing_title' => $sailing->getTitle,
            'sailing_date' => $sailing->start,
            'impact' => [
                'orders_count' => count($impactedOrders),
                'passengers_count' => $totalPassengers,
                'orders' => $impactedOrders,
            ],
        ];
    }

    /**
     * Confirme le changement de statut
     */
    public function confirm(WP_REST_Request $request)
    {
        $sailingId = (int) $request->get_param('sailing_id');
        $reason = $request->get_param('reason');
        $status = $request->get_param('status') ?: 'Annulé'; // Par défaut Annulé si vide, mais accepte 'Reporté'

        $sailing = Sailing::find($sailingId);
        if (! $sailing) {
            return new \WP_Error('not_found', 'Départ introuvable');
        }

        // 1. Mise à jour de la Taxonomie
        // Cela permet de passer en 'Annulé', 'Reporté', ou même de revenir à 'Actif'
        wp_set_object_terms($sailingId, $status, 'sailing_status');

        // Sauvegarde de la raison
        if ($reason) {
            update_post_meta($sailingId, '_cancellation_reason', sanitize_textarea_field($reason));
        }

        // 2. Notification sur les commandes
        $impactedOrders = $sailing->getOrders();

        foreach ($impactedOrders as $o) {
            $order = wc_get_order($o['id']);
            if ($order) {
                // Message adapté selon le statut
                if ($status === 'Actif') {
                    $note = "ℹ️ INFO : Le départ (ID $sailingId) a été réactivé.";
                } else {
                    $note = "ℹ️ STATUT DÉPART MODIFIÉ : $status (ID $sailingId).\n";
                    $note .= 'Raison : '.($reason ?: 'Non spécifiée');
                    $note .= "\nAction requise : Vérifier si remboursement/report nécessaire.";
                }

                $order->add_order_note($note);
            }
        }

        return [
            'success' => true,
            'message' => "Statut mis à jour vers '$status'.",
            'new_status' => $status,
        ];
    }

    // ... (reschedule inchangé) ...
    public function reschedule(WP_REST_Request $request)
    {
        $oldSailingId = (int) $request->get_param('old_sailing_id');
        $newSailingId = (int) $request->get_param('new_sailing_id');
        $orderIds = $request->get_param('order_ids') ?: [];

        $newSailing = Sailing::find($newSailingId);
        if (! $newSailing) {
            return new \WP_Error('not_found', 'Nouveau départ introuvable');
        }

        $processed = 0;

        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);
            if (! $order) {
                continue;
            }

            $changed = false;
            foreach ($order->get_items() as $item) {
                if ($item->get_meta('_sailing_id') == $oldSailingId) {

                    $item->update_meta_data('_sailing_id', $newSailingId);

                    if ($newSailing->start) {
                        try {
                            $dt = new \DateTime($newSailing->start);
                            $item->update_meta_data('Date de départ', $dt->format('d/m/Y à H:i'));

                            $raw = json_decode($item->get_meta('_booking_data_raw'), true);
                            if ($raw) {
                                $raw['sailing_id'] = $newSailingId;
                                $raw['date'] = $newSailing->start;
                                $item->update_meta_data('_booking_data_raw', json_encode($raw));
                            }
                        } catch (\Exception $e) {
                        }
                    }
                    $item->save();
                    $changed = true;
                }
            }

            if ($changed) {
                $order->add_order_note("🔄 REPROGRAMMATION : Client déplacé vers le départ #$newSailingId (".$newSailing->start.').');
                $order->save();
                $processed++;
            }
        }

        return [
            'success' => true,
            'message' => "$processed commandes déplacées vers le départ du ".$newSailing->start,
        ];
    }
}
