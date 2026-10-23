<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Configuration recommandée du plugin de facturation apc-billing pour navivoile.
 *
 * Sans option : affiche les réglages recommandés, l'écart avec les réglages
 * enregistrés et l'état de la numérotation (dernier numéro de facture de WP
 * Overnight, compteurs du plugin), puis les points à vérifier avant la bascule
 * (cartes cadeaux, mode de règlement imprimé, commandes de mai jamais facturées par
 * WP Overnight, avoirs). Rien n'est écrit.
 *
 * Cartes cadeaux : leur classe de taxe est un réglage du plugin (section « Cartes
 * cadeaux » de l'onglet Facturation), le thème ne la change plus. La classe et la
 * catégorie recommandées sont résolues à l'exécution (avertissement si l'une manque).
 *
 * Mode de règlement imprimé : seule la ligne « Aucun moyen de paiement » (__none__ :
 * commandes sans moyen de paiement avec un montant dû, c'est-à-dire les commandes
 * d'agence) est recommandée, avec le texte validé par le client (AGENCY_LABEL). Les
 * commandes à 0 € sans crédit (jeux radio, cartes cadeaux traitées comme remise) ont leur
 * propre ligne dans le plugin (__free__, sans cadre par défaut) : rien n'est recommandé.
 * Les autres textes du réglage payment_labels restent libres : --appliquer n'écrit le
 * réglage que s'il est absent ; s'il est déjà enregistré sans cette ligne (ou avec un
 * autre texte), il est signalé et --appliquer --forcer n'écrit que cette ligne, en
 * gardant les autres textes.
 *
 * Mises à jour du plugin depuis GitHub : les rappels recommandent le jeton
 * APC_BILLING_GITHUB_TOKEN dans le .env de production et DISALLOW_FILE_MODS à false.
 * L'état du jeton est testé comme le plugin (Updater::token_source() quand la classe
 * existe : constante, puis $_ENV, $_SERVER, getenv(), valeur non vide et bien formée) :
 * la valeur n'est testée qu'en mémoire, jamais affichée ni journalisée.
 *
 * Aucune date de début n'est recommandée : les commandes de mai 2026 jamais facturées
 * par WP Overnight doivent être facturées par le plugin (onglet Anciennes commandes),
 * sauf les commandes « Remboursée » (décision du client : ventes annulées, non facturées).
 *
 * --appliquer : enregistre les réglages recommandés ABSENTS de l'option. Un réglage
 * déjà enregistré (choix validé par le comptable, site déjà basculé) n'est remplacé
 * qu'avec --forcer. « Facturation activée » (enable_invoicing) et « Mode test
 * facturation » (invoicing_test_mode) ne sont posés que s'ils sont absents et ne sont
 * jamais remplacés, même avec --forcer : une facturation déjà activée n'est jamais
 * coupée par la commande. Sur un site neuf, la facturation reste donc désactivée et en
 * mode test : son activation se fait ensuite à la main.
 *
 * --compteur : règle le compteur de la série unique (part_all) sur le dernier
 * numéro de facture WP Overnight lu en base, seulement si aucun compteur du plugin
 * n'existe encore. Refusé tant que WP Overnight est actif (sauf --forcer). Le compteur
 * est écrit par Invoice_Numbering::set_next(), sous le verrou du plugin (classes
 * chargées en mémoire si le plugin n'est pas actif). À lancer en production, au moment
 * de la bascule : WP Overnight coupé, juste avant l'activation de la facturation.
 *
 * --forcer : avec --appliquer, remplace aussi les réglages enregistrés qui diffèrent
 * de la recommandation (jamais enable_invoicing ni invoicing_test_mode) ; avec
 * --compteur, passe outre WP Overnight actif.
 *
 * Utilisation : wp acorn navi:facturation-config [--appliquer] [--compteur] [--forcer]
 */
class BillingConfigCommand extends Command
{
    protected $signature = 'navi:facturation-config
        {--appliquer : Enregistre les réglages recommandés absents (un réglage déjà enregistré n\'est remplacé qu\'avec --forcer)}
        {--compteur : Règle le compteur part_all sur le dernier numéro WPO (WP Overnight coupé, juste avant l\'activation de la facturation), si aucun compteur APC n\'existe}
        {--forcer : Avec --appliquer, remplace aussi les réglages enregistrés qui diffèrent (jamais enable_invoicing ni invoicing_test_mode) ; avec --compteur, passe outre WP Overnight actif}';

    protected $description = 'Affiche (et applique sur demande) la configuration recommandée du plugin de facturation apc-billing';

    /** Option des réglages du plugin. */
    protected const OPTION = 'apc_billing_settings';

    /** Option des compteurs de numérotation du plugin. */
    protected const COUNTERS = 'apc_billing_counters';

    /**
     * Préfixe du verrou MySQL de numérotation (Invoice_Numbering::LOCK). Depuis la 2.75,
     * le plugin verrouille un nom propre au site : voir lockName().
     */
    protected const LOCK = 'apc_billing_invnum';

    /**
     * Réglages posés seulement s'ils sont absents, jamais remplacés (même avec --forcer) :
     * une facturation activée ou sortie du mode test n'est jamais coupée par la commande.
     */
    protected const NEVER_REPLACED = ['enable_invoicing', 'invoicing_test_mode'];

    /** Ordre de la bascule (rappels et refus de --compteur). */
    protected const SWITCH_ORDER = 'Ordre de la bascule : 1) couper WP Overnight (le désactiver ; ou, pour garder ses anciens PDF consultables, vider « Joindre à » et ne plus créer de facture WPO à la main) ; 2) lancer --compteur en production (avec --forcer si WP Overnight reste actif) ; 3) aussitôt après, activer la facturation et quitter le mode test dans le plugin ; 4) facturer les commandes de mai 2026 jamais facturées par WP Overnight (onglet Anciennes commandes, voir les rappels).';

    /** Classe de taxe des cartes cadeaux (bons à usages multiples vendus sans TVA). */
    protected const GIFT_TAX_CLASS = 'taux-zero';

    /** Catégorie de produits des cartes cadeaux (créée par le thème à la première vente). */
    protected const GIFT_CATEGORY = 'carte-cadeau';

    /**
     * Commandes jamais facturées par WP Overnight : de la première commande du site à la
     * veille de la première facture WPO (18/05/2026). Bornes incluses, heure du site.
     */
    protected const MAY_FROM = '2026-05-04';

    protected const MAY_TO = '2026-05-17';

    /** Réglages généraux de WP Overnight (identité de l'émetteur). */
    protected const WPO_GENERAL = 'wpo_wcpdf_settings_general';

    /** Réglages de la facture WP Overnight (e-mails auxquels elle est jointe). */
    protected const WPO_INVOICE = 'wpo_wcpdf_documents_settings_invoice';

    protected const WPO_PLUGIN = 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php';

    protected const APC_PLUGIN = 'apc-billing/apc-billing.php';

    /**
     * Réglages dont seules certaines entrées (tableau clé => texte) sont recommandées : les
     * autres entrées enregistrées sont libres, jamais comparées ni effacées (--forcer
     * n'écrit que les entrées recommandées).
     */
    protected const PARTIAL = ['payment_labels'];

    /**
     * Mode de règlement imprimé des commandes sans moyen de paiement avec un montant dû
     * (commandes d'agence) : texte validé par le client. Pas les commandes à 0 € (ligne
     * __free__ du plugin, sans cadre).
     */
    protected const AGENCY_LABEL = 'Règlement auprès de l\'agence partenaire';

    /** Jeton GitHub des mises à jour du plugin (constante ou variable d'environnement). */
    protected const UPDATE_TOKEN = 'APC_BILLING_GITHUB_TOKEN';

    /** Dépôt GitHub du plugin par défaut (constante APC_BILLING_UPDATE_REPO sinon). */
    protected const UPDATE_REPO = 'APC66/apc-billing';

    /** Format du numéro : celui de WP Overnight (NAVI + année + mois + compteur). */
    protected const FORMAT = 'NAVI{AAAA}{MM}{NUM}';

    protected const DIGITS = 4;

    /** Taille maximale d'une valeur dans le tableau (le détail suit le tableau). */
    protected const CELL = 46;

    /** WP Overnight est-il actif ? (lu par pluginsStatus()) */
    protected bool $wpoActive = false;

    /** E-mails auxquels WP Overnight joint encore sa facture (vide s'il est inactif). */
    protected array $wpoAttached = [];

    /** Classe de taxe et catégorie des cartes cadeaux (lues une fois par giftCards()). */
    protected ?array $giftCards = null;

    /**
     * Réglages enregistrés encore différents de la recommandation, après l'éventuelle
     * écriture (--appliquer ne les remplace qu'avec --forcer). Relus par reminders() pour
     * dire ce que --forcer remplacerait aussi.
     */
    protected array $differKeys = [];

    public function handle(): int
    {
        if (! function_exists('get_option')) {
            $this->error('WordPress n\'est pas chargé : lancez la commande avec « wp acorn ».');

            return self::FAILURE;
        }

        $this->pluginsStatus();

        $wpo = get_option(self::WPO_GENERAL, []);
        $wpo = is_array($wpo) ? $wpo : [];
        if (! $wpo) {
            $this->warn('Réglages de WP Overnight introuvables : l\'identité de l\'émetteur n\'est pas proposée.');
        }

        [$recommended, $notes] = $this->recommended($wpo);

        $saved = get_option(self::OPTION, null);
        $current = is_array($saved) ? $saved : [];

        [$absent, $differ] = $this->showDiff($current, $recommended, $notes);

        if ($this->option('appliquer')) {
            $keys = $absent;
            if ($differ) {
                $this->newLine();
                if ($this->option('forcer')) {
                    $this->warn('--forcer : réglages déjà enregistrés remplacés par la valeur recommandée : '.implode(', ', $differ).'.');
                    $keys = array_merge($keys, $differ);
                } else {
                    $this->line(sprintf(
                        'Réglages déjà enregistrés, différents de la recommandation, conservés : %s (relancez avec --appliquer --forcer pour les remplacer).',
                        implode(', ', $differ)
                    ));
                }
                $this->partialNote($differ);
            }
            $current = $this->apply($current, $recommended, $keys);
        } elseif ($absent || $differ) {
            $this->newLine();
            if ($absent) {
                $this->info(sprintf(
                    '%d réglage(s) absent(s) à enregistrer. Rien n\'a été écrit : relancez avec --appliquer pour les enregistrer.',
                    count($absent)
                ));
            }
            if ($differ) {
                $this->line(sprintf(
                    '%d réglage(s) déjà enregistré(s) différent(s) de la recommandation, conservé(s) par --appliquer : %s. Pour les remplacer : --appliquer --forcer.',
                    count($differ),
                    implode(', ', $differ)
                ));
                $this->partialNote($differ);
            }
        }

        // Réglages encore différents après l'éventuelle écriture (conseils des rappels).
        $this->differKeys = array_values(array_filter(
            $differ,
            fn ($key) => ! $this->matches($key, $current, $recommended[$key])
        ));

        $last = $this->numberingStatus($current);

        if ($this->option('compteur')) {
            $this->setCounter($last, $current);
        }

        $this->reminders($current);

        return self::SUCCESS;
    }

    /**
     * Version et état des deux extensions de facturation (apc-billing et WP Overnight).
     */
    protected function pluginsStatus(): void
    {
        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH.'wp-admin/includes/plugin.php';
        }

        $file = WP_PLUGIN_DIR.'/'.self::APC_PLUGIN;
        if (! is_file($file)) {
            $this->warn('Extension apc-billing introuvable ('.$file.') : seuls les réglages sont affichés.');
        } else {
            $version = (string) (get_plugin_data($file, false, false)['Version'] ?? '');
            $this->line(sprintf(
                'apc-billing : version %s, %s.',
                $version !== '' ? $version : 'inconnue',
                is_plugin_active(self::APC_PLUGIN) ? 'activée' : 'non activée'
            ));
            if ($version !== '' && version_compare($version, '2.75', '<')) {
                $this->warn('Les réglages de la version 2.75 (accès, notes du nom, infos d\'articles masquées, ancien plugin, e-mail « Nouvelle commande », recherche, cartes cadeaux, mode de règlement imprimé) ne sont lus qu\'à partir de la version 2.75.');
            }
        }

        $wpoActive = is_plugin_active(self::WPO_PLUGIN);
        $wpoInvoice = get_option(self::WPO_INVOICE, []);
        $attached = is_array($wpoInvoice) ? array_keys(array_filter((array) ($wpoInvoice['attach_to_email_ids'] ?? []))) : [];
        // Mémorisé pour --compteur : refusé tant que WP Overnight est actif.
        $this->wpoActive = $wpoActive;
        $this->wpoAttached = $wpoActive ? $attached : [];
        $this->line(sprintf(
            'PDF Invoices & Packing Slips (WP Overnight) : %s%s.',
            $wpoActive ? 'activée' : 'non activée',
            ($wpoActive && $attached) ? ', facture jointe aux e-mails '.implode(', ', $attached) : ''
        ));
        if ($wpoActive && $attached) {
            $this->warn('WP Overnight crée encore ses factures : à couper au moment de la bascule (deux compteurs pour la même série NAVI sinon).');
        }
        if ($wpoActive) {
            $this->warn('WP Overnight est actif : --compteur est refusé tant qu\'il l\'est (sauf --forcer).');
        }
        $this->newLine();
    }

    /**
     * Réglages recommandés pour navivoile.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>} réglages, remarques par clé
     */
    protected function recommended(array $wpo): array
    {
        $settings = [];
        $notes = [];

        // Identité de l'émetteur, reprise de WP Overnight.
        $name = $this->wpoValue($wpo, 'shop_name');
        if ($name !== '') {
            $settings['emitter_name'] = sanitize_text_field($name);
        }

        $address = $this->wpoAddress($wpo);
        if ($address !== '') {
            $settings['emitter_address'] = $address;
        }

        [$siren, $vat] = $this->parseIdentity($this->wpoValue($wpo, 'shop_address_additional'));
        if ($siren === '') {
            $siren = preg_replace('/\D+/', '', $this->wpoValue($wpo, 'coc_number'));
        }
        if ($vat === '') {
            $vat = strtoupper((string) preg_replace('/\s+/', '', $this->wpoValue($wpo, 'vat_number')));
        }
        if ($siren !== '') {
            $settings['emitter_siret'] = $siren;
            $notes['emitter_siret'] = 'imprimé « SIREN » (9 chiffres)';
        }
        if ($vat !== '') {
            $settings['emitter_vat'] = sanitize_text_field($vat);
        }

        $phone = $this->wpoValue($wpo, 'shop_phone_number');
        if ($phone !== '') {
            $settings['emitter_phone'] = sanitize_text_field($phone);
        }

        $email = sanitize_email($this->wpoValue($wpo, 'shop_email_address'));
        if ($email !== '') {
            $settings['emitter_email'] = $email;
        }

        $footer = $this->wpoValue($wpo, 'footer');
        if ($footer !== '') {
            $fixed = (string) preg_replace('/\bCondititions\b/u', 'Conditions', $footer);
            if ($fixed !== $footer) {
                $notes['emitter_footer'] = 'faute de frappe de WPO corrigée (« Condititions »)';
            }
            $settings['emitter_footer'] = wp_kses_post($fixed);
        }

        $logoId = absint($this->wpoValue($wpo, 'header_logo'));
        $logoUrl = $logoId ? (string) wp_get_attachment_url($logoId) : '';
        if ($logoUrl !== '') {
            $settings['emitter_logo_url'] = esc_url_raw($logoUrl);
            $notes['emitter_logo_url'] = 'logo de WPO (média #'.$logoId.')';
        }

        // Présentation et numérotation (série WPO continue, jamais remise à zéro).
        $settings['invoice_accent_color'] = '#101F4D';
        $settings['invoice_format_part'] = self::FORMAT;
        $settings['invoice_digits_part'] = self::DIGITS;
        $settings['invoice_reset_part'] = 'no';
        $settings['invoice_number_separate'] = 'no';
        $settings['invoice_statuses'] = ['wc-processing', 'wc-completed'];

        // Contenu de la facture.
        $settings['enable_fields'] = 'no';
        $notes['enable_fields'] = 'le thème gère déjà Société et SIRET';
        $settings['inv_show_note'] = 'no';
        $settings['hide_name_notes'] = 'yes';
        $notes['hide_name_notes'] = 'ex. « (doit 18€) » dans le nom';
        $settings['hidden_item_meta'] = implode("\n", [
            '_gc_display_recipient',
            '_gc_display_phone',
            '_gc_display_email',
            '_gc_display_message',
            '_gc_display_coupon_code',
        ]);
        $notes['hidden_item_meta'] = 'bénéficiaire et code des cartes cadeaux';

        // Accès, reprise de WPO, envois.
        $settings['view_capability'] = 'manage_woocommerce';
        $notes['view_capability'] = 'les agents guichet passent par Mon compte';
        $settings['legacy_meta_key'] = '_wcpdf_invoice_number';
        $notes['legacy_meta_key'] = 'numéros WP Overnight repris tels quels';
        $settings['attach_admin_new_order'] = 'yes';
        $settings['search_invoice_number'] = 'yes';

        // Mode de règlement imprimé des commandes sans moyen de paiement avec un montant dû
        // (commandes d'agence, réglées auprès de l'agence) : texte validé par le client.
        // Seule la ligne « Aucun moyen de paiement » (__none__) est recommandée (PARTIAL) :
        // les textes des autres moyens restent libres et ne sont jamais effacés. Les
        // commandes à 0 € sans crédit relèvent de la ligne __free__ du plugin (sans cadre).
        $settings['payment_labels'] = ['__none__' => sanitize_text_field(self::AGENCY_LABEL)];
        $notes['payment_labels'] = 'montant dû sans moyen de paiement (agences) ; 0 € : sans cadre ; autres textes libres';

        // Pas de date de début (invoice_from_date) : les commandes de mai 2026 jamais
        // facturées par WP Overnight doivent l'être par le plugin (voir reminders()).

        // Cartes cadeaux (bons à usages multiples) : vendues sans TVA, la TVA est due lors
        // de l'utilisation. Classe de taxe dédiée réglée dans le plugin (le thème ne la
        // change plus). Seulement pour les ventes futures : les commandes passées gardent
        // leur TVA. La mention imprimée sous la ligne (gift_tax_mention) n'est pas imposée.
        $gift = $this->giftCards();
        if ($gift['class_ok']) {
            // Activation seulement si la classe existe et n'a aucun taux non nul : sinon
            // les cartes resteraient (ou passeraient) dans une classe taxée.
            $settings['gift_tax_enabled'] = 'yes';
            $notes['gift_tax_enabled'] = 'ventes futures seulement';
            $settings['gift_tax_class'] = self::GIFT_TAX_CLASS;
            $notes['gift_tax_class'] = '« '.$gift['class_name'].' », '.($gift['rates'] ? count($gift['rates']).' taux, tous à 0 %' : 'aucun taux');
        } elseif ($gift['class_exists']) {
            $this->warn(sprintf(
                'Cartes cadeaux : la classe de taxe « %s » (%s) a un taux non nul (%s) : classe et activation non recommandées. Corrigez ses taux (WooCommerce > Réglages > TVA), puis relancez la commande.',
                $gift['class_name'],
                self::GIFT_TAX_CLASS,
                implode(', ', $gift['non_zero'])
            ));
        } else {
            $this->warn(sprintf(
                'Cartes cadeaux : classe de taxe « %s » absente (classes : %s) : classe et activation non recommandées. Créez-la sans taux (WooCommerce > Réglages > TVA), puis relancez la commande.',
                self::GIFT_TAX_CLASS,
                $gift['slugs'] ? implode(', ', $gift['slugs']) : 'aucune'
            ));
        }

        if ($gift['term']) {
            $settings['gift_tax_categories'] = [(int) $gift['term']->term_id];
            $notes['gift_tax_categories'] = sprintf('« %s » (%s), %d produit(s)', $gift['term']->name, self::GIFT_CATEGORY, $gift['products']);
        } else {
            $this->warn(sprintf(
                'Cartes cadeaux : catégorie de produits « %s » absente (le thème la crée à la première vente d\'une carte cadeau) : catégorie non recommandée. Relancez la commande une fois la catégorie créée.',
                self::GIFT_CATEGORY
            ));
        }

        // Envoi au client : mêmes e-mails que WP Overnight (effectif hors mode test seulement).
        $settings['deliver_to_customer'] = 'yes';
        $settings['deliver_emails'] = ['customer_processing_order', 'customer_completed_order', 'customer_invoice'];
        $notes['deliver_to_customer'] = 'comme WP Overnight, après la sortie du mode test';

        // Factures d'avoir : nécessaires pour reprendre la TVA des remboursements futurs et
        // des avoirs AV- de la liste d'embarquement. Série commune aux factures (décision
        // du client : numérotation NAVI continue). Pas d'avoirs pour les remboursements
        // passés (voir reminders()).
        $settings['enable_credit_notes'] = 'yes';
        $settings['credit_numbering'] = 'same';
        $settings['credit_deliver_to_customer'] = 'yes';
        $notes['enable_credit_notes'] = 'remboursements à venir seulement';
        $notes['credit_numbering'] = 'série commune aux factures (décidé)';
        $notes['credit_deliver_to_customer'] = 'remboursements WooCommerce seulement (pas les AV-)';

        // Activation manuelle ensuite. Posés seulement s'ils sont absents, jamais remplacés
        // (NEVER_REPLACED) : une facturation déjà activée n'est jamais coupée.
        $settings['enable_invoicing'] = 'no';
        $settings['invoicing_test_mode'] = 'yes';
        $notes['enable_invoicing'] = 'activation manuelle après la bascule ; jamais remplacé';
        $notes['invoicing_test_mode'] = 'sortie manuelle après la bascule ; jamais remplacé';

        return [$settings, $notes];
    }

    /**
     * Affiche le tableau des écarts.
     *
     * Un réglage absent est à enregistrer (--appliquer). Un réglage déjà enregistré et
     * différent est conservé : --appliquer --forcer le remplace, sauf ceux de
     * NEVER_REPLACED, qui ne le sont jamais.
     *
     * @return array{0: string[], 1: string[]} clés absentes à enregistrer, clés enregistrées
     *                                         différentes (remplacées seulement avec --forcer)
     */
    protected function showDiff(array $current, array $recommended, array $notes): array
    {
        $defaults = $this->defaults();
        $labels = $this->labels();
        $rows = [];
        $absent = [];
        $differ = [];
        $long = [];

        foreach ($recommended as $key => $value) {
            // Enregistré au sens du plugin (payment_labels : aussi l'ancien no_gateway_label).
            $isSaved = $this->isSaved($key, $current);
            $effective = array_key_exists($key, $current) ? $current[$key] : ($defaults[$key] ?? null);

            if ($isSaved && $this->matches($key, $current, $value)) {
                $state = 'identique';
            } elseif ($isSaved && in_array($key, self::NEVER_REPLACED, true)) {
                $state = 'différent : conservé (jamais remplacé)';
            } elseif ($isSaved) {
                $differ[] = $key;
                $state = $this->differState($key, $current);
            } else {
                $absent[] = $key;
                $state = $this->same($effective, $value) ? 'à enregistrer (= défaut)' : 'À ENREGISTRER (change le défaut)';
            }

            if (! $isSaved) {
                $savedText = '(absent) défaut : '.$this->display($effective);
            } elseif (array_key_exists($key, $current)) {
                $savedText = $this->display($current[$key]);
            } else {
                // payment_labels absent, ancien réglage no_gateway_label relu en repli.
                $savedText = 'ancien no_gateway_label : '.$this->legacyNoneLabel($current);
            }

            $recommendedText = $this->display($value);
            if (mb_strlen($recommendedText) > self::CELL) {
                $long[$key] = $value;
            }

            $rows[] = [
                ($labels[$key] ?? $key)."\n".$key,
                $this->cut($savedText),
                $this->cut($recommendedText),
                $state.(isset($notes[$key]) ? "\n".$notes[$key] : ''),
            ];
        }

        $this->info('Configuration recommandée d\'apc-billing pour navivoile');
        $this->table(['Réglage (clé)', 'Enregistré', 'Recommandé', 'Écart'], $rows);

        if ($long) {
            $this->line('Valeurs recommandées complètes :');
            foreach ($long as $key => $value) {
                if (is_array($value) && ! array_is_list($value)) {
                    // Tableau clé => texte : une entrée par ligne.
                    $lines = [];
                    foreach ($value as $sub => $text) {
                        $lines[] = $this->entryLabel((string) $sub).' : '.$text;
                    }
                } else {
                    $lines = preg_split('/\R/u', is_array($value) ? implode(', ', $value) : (string) $value);
                }
                $this->line('  '.$key.' :');
                foreach ($lines as $line) {
                    $this->line('    '.$line);
                }
            }
        }

        if (! $current) {
            $this->warn('Aucun réglage apc-billing n\'est encore enregistré : le plugin applique ses valeurs par défaut.');
        }

        return [$absent, $differ];
    }

    /**
     * Enregistre les réglages demandés (option --appliquer) : les réglages absents, et
     * avec --forcer ceux qui diffèrent (jamais ceux de NEVER_REPLACED, déjà écartés par
     * showDiff()).
     *
     * @param  string[]  $changes  clés à enregistrer
     * @return array réglages enregistrés
     */
    protected function apply(array $current, array $recommended, array $changes): array
    {
        $this->newLine();

        // Garde-fou : une clé de NEVER_REPLACED déjà enregistrée n'est jamais écrite.
        $changes = array_values(array_filter(
            $changes,
            fn ($key) => ! (in_array($key, self::NEVER_REPLACED, true) && array_key_exists($key, $current))
        ));

        if (! $changes) {
            $this->info('Aucun réglage absent à enregistrer : rien à écrire.');

            return $current;
        }

        // Réglage partiel (PARTIAL) déjà enregistré : seules les entrées recommandées sont
        // écrites, les autres entrées enregistrées sont gardées.
        $new = $current;
        foreach ($changes as $key) {
            $new[$key] = $this->merged($key, $current, $recommended[$key]);
        }

        // La clé du compteur change avec la remise annuelle (part_AAAA ou part_all) :
        // on reporte le compteur courant pour que la série ne reparte pas à 1. Si le
        // report échoue, la remise annuelle n'est pas modifiée.
        $oldReset = 'yes' === ($current['invoice_reset_part'] ?? 'yes');
        $newReset = 'yes' === ($new['invoice_reset_part'] ?? 'yes');
        if ($oldReset !== $newReset && ! $this->carryCounter($oldReset, $newReset)) {
            if (array_key_exists('invoice_reset_part', $current)) {
                $new['invoice_reset_part'] = $current['invoice_reset_part'];
            } else {
                unset($new['invoice_reset_part']);
            }
            $changes = array_values(array_diff($changes, ['invoice_reset_part']));
            $this->error('Report du compteur impossible : « Remise à zéro annuelle » (invoice_reset_part) n\'est pas modifiée, sinon la série repartirait à 1. Réessayez, ou réglez le prochain numéro dans l\'onglet Facturation.');

            if (! $changes) {
                return $current;
            }
        }

        // Écriture directe : le nettoyage du formulaire des réglages attend le formulaire
        // complet (une case absente y vaut « non ») et désactiverait des réglages qui ne
        // sont pas traités ici. Les valeurs écrites ici sont nettoyées par recommended().
        $hook = 'sanitize_option_'.self::OPTION;
        // Copie : remove_all_filters() vide l'objet WP_Hook lui-même.
        $backup = isset($GLOBALS['wp_filter'][$hook]) ? clone $GLOBALS['wp_filter'][$hook] : null;
        remove_all_filters($hook);

        try {
            update_option(self::OPTION, $new);
        } finally {
            if ($backup !== null) {
                $GLOBALS['wp_filter'][$hook] = $backup;
            }
        }

        // La méta de l'ancien plugin détectée est mise en cache : on l'oublie.
        delete_transient('apc_billing_legacy_key');

        wp_cache_delete(self::OPTION, 'options');
        $check = get_option(self::OPTION, []);
        $written = is_array($check) ? array_filter($changes, fn ($key) => array_key_exists($key, $check) && $this->matches($key, $check, $recommended[$key])) : [];

        if (count($written) === count($changes)) {
            $this->info(sprintf('%d réglage(s) enregistré(s) : %s.', count($changes), implode(', ', $changes)));
        } else {
            $this->error('Les réglages n\'ont pas tous été enregistrés : vérifiez l\'onglet Facturation du plugin.');
        }

        return is_array($check) ? $check : $new;
    }

    /**
     * État de la numérotation : dernier numéro WP Overnight, compteurs du plugin.
     *
     * @return int|null dernier numéro WPO, null s'il est introuvable
     */
    protected function numberingStatus(array $settings): ?int
    {
        $this->newLine();
        $this->info('Numérotation');

        $wpo = $this->wpoLastNumber();
        foreach ($wpo['details'] as $detail) {
            $this->line('  '.$detail);
        }

        $last = $wpo['last'];
        if ($last === null) {
            $this->warn('  Dernier numéro WP Overnight introuvable.');
        } else {
            $this->line(sprintf(
                '  Dernier numéro WP Overnight retenu : %d (prochaine facture : %s, au format %s sur %d chiffres)',
                $last,
                $this->format(self::FORMAT, $last + 1, self::DIGITS),
                self::FORMAT,
                self::DIGITS
            ));
        }

        $counters = $this->counters();
        if ($counters) {
            $list = [];
            foreach ($counters as $key => $value) {
                $list[] = $key.' = '.(int) $value;
            }
            $this->line('  Compteurs apc-billing : '.implode(', ', $list));
        } else {
            $this->line('  Compteurs apc-billing : aucun (la première facture prendrait le numéro 1).');
        }

        $reset = 'yes' === ($settings['invoice_reset_part'] ?? 'yes');
        $key = 'part'.($reset ? '_'.(int) current_time('Y') : '_all');
        $this->line('  Clé de compteur utilisée avec les réglages enregistrés : '.$key);

        return $last;
    }

    /**
     * Règle le compteur part_all sur le dernier numéro WPO (option --compteur), seulement
     * si aucun compteur du plugin n'existe.
     *
     * Refusé tant que WP Overnight est actif (sauf --forcer) : chaque facture qu'il
     * émettrait ensuite aurait un numéro que le plugin redonnerait. L'écriture passe par
     * Invoice_Numbering::set_next( 'part', dernier + 1 ) (verrou, relecture hors cache et
     * écriture du plugin, autres compteurs conservés). La commande prend d'abord le même
     * verrou pour que la vérification « aucun compteur » et l'écriture ne fassent qu'un :
     * aucune facture ni aucun avoir ne peut être numéroté entre les deux.
     */
    protected function setCounter(?int $last, array $settings): void
    {
        global $wpdb;

        $this->newLine();
        $this->info('Compteur (--compteur)');

        if ($last === null || $last < 1) {
            $this->error('Dernier numéro WP Overnight introuvable : compteur non modifié.');

            return;
        }

        if ($this->wpoActive) {
            if (! $this->option('forcer')) {
                $this->error(sprintf(
                    'WP Overnight est actif%s : il peut encore attribuer des numéros NAVI, que le plugin redonnerait (doublons). Compteur non modifié.',
                    $this->wpoAttached ? ' et joint sa facture aux e-mails '.implode(', ', $this->wpoAttached) : ''
                ));
                $this->line('  '.self::SWITCH_ORDER);
                $this->line('  Pour garder WP Overnight actif (consultation des anciens PDF) : videz « Joindre à », puis relancez avec --compteur --forcer.');

                return;
            }

            if ($this->wpoAttached) {
                $this->warn('--forcer : WP Overnight joint encore sa facture aux e-mails ('.implode(', ', $this->wpoAttached).'). Chaque facture qu\'il émettra après ce réglage aura un numéro que le plugin redonnera : videz « Joindre à » sans attendre.');
            } else {
                $this->warn('--forcer : WP Overnight reste actif. Ne créez plus de facture WPO à la main (bouton « Facture » de la commande) : son numéro serait redonné par le plugin.');
            }
        }

        if (! $this->loadPlugin()) {
            $this->error('Classes du plugin apc-billing introuvables : compteur non modifié.');

            return;
        }

        $numbering = '\APC\Billing\Invoice_Numbering';

        // set_next() écrit dans la clé en vigueur : avec la remise annuelle, ce serait
        // part_AAAA et la série NAVI repartirait à 1 en janvier.
        $key = (string) $numbering::counter_key('part');
        if ($key !== 'part_all') {
            $this->error(sprintf(
                'La remise annuelle est active dans les réglages enregistrés : le compteur serait réglé sur %s au lieu de part_all. Enregistrez d\'abord « Remise à zéro annuelle » = no (--appliquer, ou --appliquer --forcer si une autre valeur est enregistrée), puis relancez --compteur. Compteur non modifié.',
                $key
            ));

            return;
        }

        $lockName = $this->lockName();
        $lock = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 10)', $lockName));
        if ((string) $lock !== '1') {
            $this->error('Verrou de numérotation du plugin indisponible : compteur non modifié, réessayez.');

            return;
        }

        $saved = false;

        try {
            // Relecture sous verrou, hors cache (comme le plugin).
            $counters = $this->freshCounters();
            if ($counters) {
                $list = [];
                foreach ($counters as $name => $value) {
                    $list[] = $name.' = '.(int) $value;
                }
                $this->error('Un compteur apc-billing existe déjà ('.implode(', ', $list).') : rien n\'est modifié. Réglez le prochain numéro dans l\'onglet Facturation du plugin si besoin.');

                return;
            }

            // Une facture WPO a pu être émise depuis le début de la commande : relecture.
            $fresh = $this->wpoLastNumber()['last'];
            if ($fresh !== null && $fresh > $last) {
                $last = $fresh;
            }
            $this->line(sprintf('  Dernier numéro WP Overnight relu juste avant l\'écriture : %d', $last));

            // set_next() reprend le même verrou sur la même connexion : MySQL 5.7.5+ et
            // MariaDB 10.0.2+ l'accordent aussitôt (verrou récursif, libéré autant de fois
            // qu'il a été pris), la commande le garde donc jusqu'au RELEASE_LOCK ci-dessous.
            $saved = (bool) $numbering::set_next('part', $last + 1);
        } finally {
            $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lockName));
        }

        $check = $this->freshCounters();
        if ($saved && (int) ($check['part_all'] ?? 0) === $last) {
            $this->info(sprintf(
                'Compteur part_all réglé sur %d : la prochaine facture portera le numéro %s.',
                $last,
                $this->format(self::FORMAT, $last + 1, self::DIGITS)
            ));
        } else {
            // Motif du refus de set_next() (2.75 et suivantes) : 'lower', 'lock' ou 'write'.
            $reason = property_exists($numbering, 'last_error') ? (string) $numbering::$last_error : '';
            $why = match ($reason) {
                'lower' => 'le plugin a déjà attribué un numéro égal ou supérieur, et un compteur ne recule jamais hors mode test facturation',
                'lock' => 'verrou de numérotation indisponible, réessayez',
                'write' => 'écriture de l\'option '.self::COUNTERS.' refusée',
                default => 'voir le journal WooCommerce, source apc-billing',
            };
            $this->error('Le compteur n\'a pas été enregistré : '.$why.'.');
        }
    }

    /**
     * Charge en mémoire les classes de numérotation du plugin (Settings et
     * Invoice_Numbering) quand il n'est pas actif. Les classes ne sont pas instanciées :
     * aucun hook n'est posé.
     */
    protected function loadPlugin(): bool
    {
        $numbering = '\APC\Billing\Invoice_Numbering';
        $settings = '\APC\Billing\Settings';

        if (! class_exists($settings) || ! class_exists($numbering)) {
            $dir = WP_PLUGIN_DIR.'/'.dirname(self::APC_PLUGIN).'/includes/';
            foreach (['class-settings.php' => $settings, 'class-invoice-numbering.php' => $numbering] as $file => $class) {
                if (class_exists($class)) {
                    continue;
                }
                if (! is_file($dir.$file)) {
                    return false;
                }
                require_once $dir.$file;
            }
        }

        return class_exists($numbering)
            && method_exists($numbering, 'set_next')
            && method_exists($numbering, 'counter_key');
    }

    /**
     * Nom du verrou MySQL que prend réellement le plugin : Invoice_Numbering::lock_name()
     * depuis la 2.75 (préfixe, puis 12 caractères de md5(base|préfixe des tables)), le
     * préfixe seul avant. La méthode du plugin est appelée quand elle existe (même
     * privée), le calcul n'est refait qu'en repli.
     */
    protected function lockName(): string
    {
        global $wpdb;

        $numbering = '\APC\Billing\Invoice_Numbering';
        if (! class_exists($numbering) || ! method_exists($numbering, 'lock_name')) {
            // apc-billing 2.74 et antérieures.
            return self::LOCK;
        }

        try {
            $name = (string) (new \ReflectionMethod($numbering, 'lock_name'))->invoke(null);
            if ($name !== '') {
                return $name;
            }
        } catch (\Throwable $e) {
            // Repli ci-dessous.
        }

        $db = defined('DB_NAME') ? (string) DB_NAME : '';

        return self::LOCK.'_'.substr(md5($db.'|'.$wpdb->prefix), 0, 12);
    }

    /**
     * Compteurs relus depuis la base, hors cache (même méthode que
     * Invoice_Numbering::read_counters()).
     *
     * @return array<string, int>
     */
    protected function freshCounters(): array
    {
        wp_cache_delete(self::COUNTERS, 'options');
        wp_cache_delete('notoptions', 'options');
        $all = wp_cache_get('alloptions', 'options');
        if (is_array($all) && array_key_exists(self::COUNTERS, $all)) {
            wp_cache_delete('alloptions', 'options');
        }

        return $this->counters();
    }

    /**
     * Reporte le compteur de la série « part » quand la remise annuelle change
     * (Invoice_Numbering::carry_counter(), classes chargées en mémoire si besoin).
     *
     * @return bool faux si un compteur existe et n'a pas pu être reporté
     */
    protected function carryCounter(bool $oldReset, bool $newReset): bool
    {
        $year = (int) current_time('Y');
        $oldKey = 'part'.($oldReset ? '_'.$year : '_all');
        $newKey = 'part'.($newReset ? '_'.$year : '_all');

        $before = (int) ($this->freshCounters()[$oldKey] ?? 0);
        if ($before < 1) {
            // Aucun compteur à reporter.
            return true;
        }

        if (! $this->loadPlugin() || ! method_exists('\APC\Billing\Invoice_Numbering', 'carry_counter')) {
            $this->warn('Classes du plugin apc-billing introuvables : le compteur '.$oldKey.' ne peut pas être reporté.');

            return false;
        }

        // Depuis la 2.75, carry_counter() rend faux si le verrou ou l'écriture a échoué ;
        // avant, elle ne rendait rien (null) : le test strict garde la compatibilité.
        $carried = \APC\Billing\Invoice_Numbering::carry_counter('part', $oldReset, $newReset);
        if ($carried === false) {
            return false;
        }

        // Contrôle par relecture, dans tous les cas.
        $after = (int) ($this->freshCounters()[$newKey] ?? 0);
        if ($after < $before) {
            return false;
        }

        $this->line(sprintf('Remise annuelle modifiée : compteur %s (%d) reporté sur %s.', $oldKey, $before, $newKey));

        return true;
    }

    /**
     * Points à trancher et ordre de la bascule.
     */
    protected function reminders(array $settings): void
    {
        $this->newLine();
        $this->info('À vérifier avant la bascule');

        $labels = $this->labels();

        // Cartes cadeaux : classe de taxe et catégorie, puis réglage enregistré.
        $gift = $this->giftCards();
        if ($gift['class_ok']) {
            $classText = sprintf('classe « %s » (%s) présente, %s', $gift['class_name'], self::GIFT_TAX_CLASS, $gift['rates'] ? 'taux tous à 0 %' : 'aucun taux');
        } elseif ($gift['class_exists']) {
            $classText = sprintf('classe « %s » (%s) AVEC UN TAUX NON NUL (%s)', $gift['class_name'], self::GIFT_TAX_CLASS, implode(', ', $gift['non_zero']));
        } else {
            $classText = sprintf('CLASSE « %s » ABSENTE (classes : %s)', self::GIFT_TAX_CLASS, $gift['slugs'] ? implode(', ', $gift['slugs']) : 'aucune');
        }
        $termText = $gift['term']
            ? sprintf('catégorie « %s » (%s, #%d) : %d produit(s)', $gift['term']->name, self::GIFT_CATEGORY, $gift['term']->term_id, $gift['products'])
            : sprintf('CATÉGORIE « %s » ABSENTE (créée par le thème à la première vente)', self::GIFT_CATEGORY);
        $this->line('  - Cartes cadeaux : '.$classText.' ; '.$termText.'.');
        if ($gift['outside'] > 0) {
            $this->line(sprintf(
                '    %d produit(s) « Carte Cadeau » hors de cette catégorie (anciens produits : le thème crée un produit par vente, dans la catégorie) : ils restent dans leur classe de taxe ; à ranger dans la catégorie s\'ils peuvent encore être vendus.',
                $gift['outside']
            ));
        }

        $giftEnabled = 'yes' === ($settings['gift_tax_enabled'] ?? 'no');
        // Classe non enregistrée : le plugin applique sa classe par défaut. Enregistrée :
        // « standard » vaut '' (classe standard), comme dans le plugin.
        $giftSaved = array_key_exists('gift_tax_class', $settings);
        $giftClass = $giftSaved ? $this->normalizeTaxClass($settings['gift_tax_class']) : $this->giftDefaultClass();
        $giftTerms = array_map('intval', (array) ($settings['gift_tax_categories'] ?? []));
        $giftOk = $giftEnabled && $giftClass === self::GIFT_TAX_CLASS && $gift['class_ok']
            && $gift['term'] && in_array((int) $gift['term']->term_id, $giftTerms, true);
        if (! $giftSaved) {
            $giftClassText = sprintf('classe non enregistrée (défaut du plugin : « %s »)', $giftClass !== '' ? $giftClass : 'Standard');
        } elseif ($giftClass === '') {
            $giftClassText = 'classe « Standard » (enregistrée)';
        } else {
            $giftClassText = 'classe « '.$giftClass.' »';
        }
        if ($giftOk) {
            $giftState = $giftSaved
                ? 'conforme (cartes cadeaux vendues sans TVA)'
                : 'conforme (cartes cadeaux vendues sans TVA), '.$giftClassText.' ; à enregistrer (--appliquer) pour la figer';
        } else {
            $giftState = sprintf(
                '%s, %s, catégories %s : NON CONFORME, %s',
                $giftEnabled ? 'activé' : 'non activé',
                $giftClassText,
                $giftTerms ? implode(', ', $giftTerms) : '(aucune)',
                $this->giftAdvice($settings, $gift, $giftClass)
            );
        }
        $this->line(sprintf(
            '    Réglage du plugin (Facturation > Cartes cadeaux) : %s. Ventes futures seulement : les cartes déjà vendues gardent leur TVA (données des commandes).',
            $giftState
        ));

        $mention = trim((string) ($settings['gift_tax_mention'] ?? ''));
        $this->line(sprintf(
            '  - Mention sous les cartes cadeaux (gift_tax_mention, Facturation > Cartes cadeaux) : %s. Facultative, rien n\'est imposé : à décider avec le comptable (ex. « Bon à usages multiples : TVA due lors de l\'utilisation »).',
            $mention !== '' ? '« '.$mention.' »' : 'vide'
        ));

        $this->paymentReminder($settings);

        // Commandes de mai jamais facturées par WP Overnight.
        $from = (string) ($settings['invoice_from_date'] ?? '');
        if ($from !== '') {
            $this->warn(sprintf(
                '  - « %s » (invoice_from_date) est enregistré au %s : les commandes créées avant ne recevraient pas de facture. Videz ce réglage (onglet Facturation) avant de facturer les commandes de mai.',
                $labels['invoice_from_date'],
                $this->frDate($from)
            ));
        }

        $may = $this->unbilledMayOrders();
        $toBill = $may['processing'] ?? 0;
        // Libellés français fixes : WP-CLI tourne souvent en anglais.
        $names = [
            'pending' => 'En attente de paiement',
            'on-hold' => 'En attente',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            'refunded' => 'Remboursée',
            'failed' => 'Échouée',
        ];
        $others = [];
        foreach ($may as $status => $count) {
            if ($status !== 'processing') {
                $others[] = $count.' « '.($names[$status] ?? wc_get_order_status_name($status)).' »';
            }
        }
        $this->line(sprintf(
            '  - Commandes créées du %s au %s, jamais facturées (ni WP Overnight ni le plugin) : %s%s.',
            $this->frDate(self::MAY_FROM),
            $this->frDate(self::MAY_TO),
            $toBill > 0 ? $toBill.' « En cours » à facturer' : 'aucune « En cours » à facturer',
            $others ? ' ; autres statuts : '.implode(', ', $others) : ''
        ));
        if ($toBill > 0) {
            $this->line(sprintf(
                '    Après l\'activation : Facturation APC > Anciennes commandes > « Je veux le faire selon mes conditions », statut « En cours », « D\'une date de commande à une autre » du %s au %s, « Utiliser la nouvelle numérotation » (numéros de la série NAVI, datés du jour).',
                $this->frDate(self::MAY_FROM),
                $this->frDate(self::MAY_TO)
            ));
        }
        if (! empty($may['refunded'])) {
            $this->line(sprintf(
                '    Les %d commande(s) « Remboursée » de la période ne sont pas facturées (décision du client : ventes annulées ; les facturer exigerait un avoir pour chacune) : laissez ce statut décoché dans l\'onglet Anciennes commandes.',
                $may['refunded']
            ));
        }

        // Avoirs : série commune, rien pour les remboursements passés.
        $credit = 'yes' === ($settings['enable_credit_notes'] ?? 'no');
        $separate = ($settings['credit_numbering'] ?? 'same') === 'separate';
        $this->line(sprintf(
            '  - Factures d\'avoir : %s, série %s. Pas d\'avoirs pour les remboursements passés : ne pas utiliser « Générer les avoirs (remboursements) en masse » (onglet Anciennes commandes). Les avoirs AV- de la liste d\'embarquement créent un remboursement sans remboursement bancaire ; sans factures d\'avoir, ils ne produisent aucun document.',
            $credit ? 'activées' : 'désactivées',
            $separate ? 'SÉPARÉE (décision : série commune aux factures, à corriger)' : 'commune aux factures'
        ));

        $this->line('  - Avoirs AV- sur une commande réglée par carte cadeau ou avoir : WooCommerce ne rembourse pas au-delà de ce que la passerelle a encaissé. La part réglée par crédit n\'a donc pas de facture d\'avoir (sa TVA reste sur la facture d\'origine) : note « ⚠️ AVOIR » sur la commande, méta _navi_voucher_refund_missing, journal navi-voucher-refund. À signaler au comptable.');

        $this->line('  - Factures d\'avoir des AV- : jamais envoyées au client (l\'e-mail WooCommerce « Commande remboursée » est coupé), elles partent au comptable avec l\'envoi mensuel quand les avoirs y sont cochés et se téléchargent depuis la commande (bouton « Avoir PDF »).');

        // Mises à jour du plugin depuis GitHub : dépôt, jeton du dépôt privé (état
        // seulement, valeur jamais affichée) et DISALLOW_FILE_MODS.
        $this->loadUpdater();
        $this->line(sprintf(
            '  - Mises à jour d\'apc-billing depuis GitHub (%s, apc-billing 2.75 et suivantes) : ajoutez %s au .env de production (jeton fine-grained en lecture seule, « Contents: read » sur ce seul dépôt ; constante du même nom dans wp-config.php pour un site classique ; rien à ajouter dans bedrock/application.php : le plugin lit $_ENV). Sans jeton valable, aucune mise à jour n\'est proposée tant que le dépôt est privé. Sur ce site : %s. La valeur n\'est jamais affichée par cette commande ; état détaillé dans l\'onglet Aide du plugin.',
            $this->updateRepo(),
            self::UPDATE_TOKEN,
            $this->tokenPresence()
        ));
        $this->line(sprintf(
            '    Vérifiez aussi DISALLOW_FILE_MODS=false dans le .env de production (lu par bedrock/application.php) : à true, WordPress n\'affiche ni n\'installe aucune mise à jour d\'extension. Bedrock désactive les mises à jour automatiques (AUTOMATIC_UPDATER_DISABLED) : la mise à jour se fait à la main (Tableau de bord > Mises à jour). Sur ce site : DISALLOW_FILE_MODS %s.',
            defined('DISALLOW_FILE_MODS') ? (constant('DISALLOW_FILE_MODS') ? 'vaut true (AUCUNE MISE À JOUR POSSIBLE depuis l\'administration)' : 'vaut false') : 'non défini (équivaut à false)'
        ));

        $this->line('  - '.self::SWITCH_ORDER);
        $this->line('  - --appliquer n\'écrit que les réglages absents : un choix déjà enregistré n\'est remplacé qu\'avec --forcer, et « Facturation activée » ou « Mode test facturation » ne le sont jamais.');
    }

    /**
     * Rappel du mode de règlement imprimé : ligne « Aucun moyen de paiement » (commandes
     * d'agence), lue comme le plugin (Settings::payment_labels() : textes vides ignorés,
     * repli sur l'ancien réglage no_gateway_label).
     * - conforme : texte validé par le client ;
     * - réglage absent : --appliquer enregistre le texte validé ;
     * - réglage déjà enregistré sans cette ligne, ou avec un autre texte : signalé ;
     *   --appliquer ne le complète pas, --appliquer --forcer n'écrit que cette ligne (les
     *   autres textes sont gardés).
     */
    protected function paymentReminder(array $settings): void
    {
        $where = 'Mode de règlement imprimé (Modèle de facture > Mode de règlement imprimé), ligne « Aucun moyen de paiement » (commandes sans moyen de paiement avec un montant dû : agences)';
        $wanted = sanitize_text_field(self::AGENCY_LABEL);
        $labels = $this->paymentLabels($settings);
        $none = $labels['__none__'] ?? '';
        $others = count($labels) - ($none !== '' ? 1 : 0);
        $othersText = $others > 0 ? sprintf(' ; %d autre(s) texte(s) personnalisé(s), gardé(s)', $others) : '';
        $this->noGatewayCounts($labels);

        if ($none !== '' && $this->sameText($none, $wanted)) {
            $this->line(sprintf('  - %s : conforme (« %s », texte validé par le client)%s.', $where, $none, $othersText));

            return;
        }

        if (! $this->isSaved('payment_labels', $settings)) {
            $this->line(sprintf(
                '  - %s : VIDE, le cadre « Mode de règlement » n\'est pas imprimé sur leurs factures. --appliquer enregistre le texte validé par le client : « %s ».',
                $where,
                $wanted
            ));

            return;
        }

        $alsoForced = array_values(array_filter($this->differKeys, fn ($key) => $key !== 'payment_labels'));
        $forcer = sprintf(
            'ou --appliquer --forcer (%s cette seule ligne, garde les autres textes%s)',
            $none === '' ? 'ajoute' : 'remplace',
            $alsoForced ? ' ; remplace aussi : '.implode(', ', $alsoForced) : ''
        );

        if ($none === '') {
            $this->warn(sprintf(
                '  - %s : réglage payment_labels déjà enregistré SANS cette ligne (%s) : le cadre « Mode de règlement » n\'est pas imprimé sur leurs factures. --appliquer ne complète pas un réglage enregistré : saisissez « %s » dans le plugin, %s.',
                $where,
                $others > 0 ? $others.' autre(s) texte(s) personnalisé(s)' : 'aucun texte',
                $wanted,
                $forcer
            ));

            return;
        }

        $this->warn(sprintf(
            '  - %s : « %s » enregistré%s, différent du texte validé par le client (« %s ») : conservé par --appliquer. À corriger dans le plugin, %s.',
            $where,
            $none,
            array_key_exists('payment_labels', $settings) && isset($this->savedPaymentLabels($settings)['__none__']) ? '' : ' (ancien réglage no_gateway_label)',
            $wanted,
            $forcer
        ));
    }

    /**
     * Réglage partiel (PARTIAL) dans la liste des écarts conservés : précise ce que
     * --forcer écrirait.
     *
     * @param  string[]  $differ
     */
    protected function partialNote(array $differ): void
    {
        if (in_array('payment_labels', $differ, true)) {
            $this->line('payment_labels : --forcer n\'écrit que la ligne « Aucun moyen de paiement » (__none__) ; les textes des autres moyens de paiement sont gardés.');
        }
    }

    /**
     * Commandes sans moyen de paiement (« En cours » ou « Terminée ») : avec un montant dû
     * (ligne « Aucun moyen de paiement », agences : texte validé) et à 0 € (ligne
     * « Commande à 0 € » du plugin, sans cadre). Affiche le décompte pour que le texte
     * soit validé en connaissance de cause. Une commande réglée ENTIÈREMENT par crédit
     * (carte cadeau Smart Coupons, avoir) a son propre libellé et n'est pas comptée ; réglée
     * EN PARTIE par crédit, le reste dû hors du site, elle reçoit le texte de l'agence.
     *
     * @param  array<string,string>  $labels  Textes enregistrés (paymentLabels()).
     */
    protected function noGatewayCounts(array $labels): void
    {
        global $wpdb;

        $orders = $wpdb->prefix.'wc_orders';
        $meta = $wpdb->prefix.'wc_orders_meta';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $orders)) !== $orders) {
            return;
        }
        // Crédit utilisé comme moyen de paiement : méta smart_coupons_contribution non vide.
        $rows = $wpdb->get_results(
            "SELECT o.total_amount AS total,
                EXISTS(SELECT 1 FROM {$meta} m WHERE m.order_id = o.id AND m.meta_key = 'smart_coupons_contribution' AND m.meta_value NOT IN ('', 'a:0:{}')) AS credit,
                EXISTS(SELECT 1 FROM {$meta} a WHERE a.order_id = o.id AND a.meta_key = '_agency_creator_id' AND a.meta_value <> '') AS agency
            FROM {$orders} o
            WHERE o.type = 'shop_order' AND o.status IN ('wc-processing', 'wc-completed') AND (o.payment_method IS NULL OR o.payment_method = '')",
            ARRAY_A
        );
        $due = $dueAgency = $free = $freeAgency = 0;
        foreach ((array) $rows as $row) {
            $zero = (float) $row['total'] < 0.005;
            if ($zero && (int) $row['credit'] === 1) {
                continue; // Réglée entièrement par carte cadeau ou avoir.
            }
            if ($zero) {
                $free++;
                $freeAgency += (int) $row['agency'];
            } else {
                $due++;
                $dueAgency += (int) $row['agency'];
            }
        }
        $freeText = $labels['__free__'] ?? '';
        $this->line(sprintf(
            '  - Commandes sans moyen de paiement (« En cours » ou « Terminée », hors commandes réglées entièrement par carte cadeau ou avoir) : %d avec un montant dû (dont %d d\'agence), qui recevront le texte « Aucun moyen de paiement » ; %d à 0 € (dont %d d\'agence : jeux radio, codes de remise de 100 %%, cartes cadeaux traitées comme remise), ligne « Commande à 0 € sans moyen de paiement » du plugin : %s.',
            $due,
            $dueAgency,
            $free,
            $freeAgency,
            $freeText !== '' ? '« '.$freeText.' »' : 'pas de cadre (rien n\'a été réglé ; aucun texte recommandé)'
        ));
    }

    /**
     * Charge en mémoire la classe Updater du plugin (lecture de l'état du dépôt et du
     * jeton) quand le plugin n'est pas actif. init() n'est pas appelé : aucun hook, aucune
     * requête vers GitHub.
     */
    protected function loadUpdater(): bool
    {
        $class = '\APC\Billing\Updater';
        if (! class_exists($class)) {
            $file = WP_PLUGIN_DIR.'/'.dirname(self::APC_PLUGIN).'/includes/class-updater.php';
            if (is_file($file)) {
                require_once $file;
            }
        }

        return is_callable([$class, 'token_source']) && is_callable([$class, 'repo_state']) && is_callable([$class, 'repo']);
    }

    /**
     * Dépôt GitHub des mises à jour, lu comme le plugin (Updater::repo_state() et repo()
     * quand la classe existe ; même règle sinon) : dépôt, mises à jour désactivées
     * (APC_BILLING_UPDATE_REPO vide) ou dépôt mal configuré. Ce n'est pas un secret.
     */
    protected function updateRepo(): string
    {
        $raw = defined('APC_BILLING_UPDATE_REPO') ? (string) constant('APC_BILLING_UPDATE_REPO') : self::UPDATE_REPO;
        if ($this->loadUpdater()) {
            $state = \APC\Billing\Updater::repo_state();
            $repo = \APC\Billing\Updater::repo();
        } else {
            $repo = trim($raw, " \t\n\r\0\x0B/");
            $valid = preg_match('#^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})/([A-Za-z0-9._-]{1,100})$#', $repo, $m) && $m[1] !== '.' && $m[1] !== '..';
            $state = defined('APC_BILLING_UPDATE_REPO') && trim($raw) === '' ? 'disabled' : ($valid ? 'ok' : 'invalid');
        }

        if ($state === 'disabled') {
            return 'mises à jour DÉSACTIVÉES sur ce site (APC_BILLING_UPDATE_REPO vide ; dépôt par défaut '.self::UPDATE_REPO.' en production)';
        }
        if ($state !== 'ok') {
            return 'DÉPÔT MAL CONFIGURÉ (APC_BILLING_UPDATE_REPO = « '.$this->cut(sanitize_text_field($raw)).' », attendu propriétaire/nom)';
        }

        return 'dépôt '.$repo;
    }

    /**
     * État du jeton GitHub des mises à jour, lu comme le plugin : Updater::token_source()
     * quand la classe existe ('constant', 'env', 'invalid' ou ''), sinon la même règle
     * (constante non vide, puis $_ENV, $_SERVER, getenv() ; valeur non vide après trim et
     * de la forme [A-Za-z0-9_]{20,255}). La valeur n'est testée qu'en mémoire : jamais
     * affichée ni journalisée.
     */
    protected function tokenPresence(): string
    {
        if ($this->loadUpdater()) {
            $source = \APC\Billing\Updater::token_source();
        } else {
            $value = '';
            $source = '';
            if (defined(self::UPDATE_TOKEN) && trim((string) constant(self::UPDATE_TOKEN)) !== '') {
                $value = trim((string) constant(self::UPDATE_TOKEN));
                $source = 'constant';
            } else {
                if (isset($_ENV[self::UPDATE_TOKEN]) && is_string($_ENV[self::UPDATE_TOKEN])) {
                    $value = $_ENV[self::UPDATE_TOKEN];
                } elseif (isset($_SERVER[self::UPDATE_TOKEN]) && is_string($_SERVER[self::UPDATE_TOKEN])) {
                    $value = $_SERVER[self::UPDATE_TOKEN];
                } else {
                    $get = getenv(self::UPDATE_TOKEN);
                    $value = is_string($get) ? $get : '';
                }
                $value = trim($value);
                $source = 'env';
            }
            if ($value === '') {
                $source = '';
            } elseif (! preg_match('/^[A-Za-z0-9_]{20,255}$/', $value)) {
                $source = 'invalid';
            }
            unset($value);
        }

        return match ($source) {
            'constant' => 'jeton défini (constante '.self::UPDATE_TOKEN.')',
            'env' => 'jeton présent dans l\'environnement (.env)',
            'invalid' => 'valeur présente mais MAL FORMÉE : ignorée par le plugin, aucune mise à jour d\'un dépôt privé',
            default => 'jeton absent ou vide (normal hors production si les mises à jour ne s\'y font pas)',
        };
    }

    /**
     * Le réglage est-il enregistré, au sens du plugin ? Présent dans l'option ; pour
     * payment_labels, aussi l'ancien réglage no_gateway_label non vide (relu par le plugin
     * en repli de la ligne « Aucun moyen de paiement »).
     */
    protected function isSaved(string $key, array $settings): bool
    {
        if (array_key_exists($key, $settings)) {
            return true;
        }

        return $key === 'payment_labels' && $this->legacyNoneLabel($settings) !== '';
    }

    /**
     * Le réglage enregistré correspond-il à la recommandation ? Réglage partiel (PARTIAL) :
     * seules les entrées recommandées sont comparées, avec la valeur que le plugin lit
     * (payment_labels : Settings::payment_labels(), repli no_gateway_label compris).
     */
    protected function matches(string $key, array $settings, $recommended): bool
    {
        if (! $this->isSaved($key, $settings)) {
            return false;
        }

        if (! in_array($key, self::PARTIAL, true)) {
            return $this->same($settings[$key], $recommended);
        }

        $saved = $key === 'payment_labels'
            ? $this->paymentLabels($settings)
            : (is_array($settings[$key]) ? $settings[$key] : []);
        foreach ((array) $recommended as $sub => $value) {
            if (! array_key_exists($sub, $saved) || ! $this->sameText((string) $saved[$sub], (string) $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * État d'un réglage enregistré différent de la recommandation (colonne « Écart »).
     */
    protected function differState(string $key, array $settings): string
    {
        if ($key !== 'payment_labels') {
            return 'différent : conservé (--forcer pour remplacer)';
        }

        // Textes courts (colonne « Écart ») : partialNote() et le rappel détaillent.
        if (isset($this->savedPaymentLabels($settings)['__none__'])) {
            return 'différent : conservé (--forcer remplace __none__ seul)';
        }

        if ($this->legacyNoneLabel($settings) !== '') {
            return 'ancien no_gateway_label : conservé (--forcer écrit __none__)';
        }

        return 'SANS __none__ : conservé (--forcer l\'ajoute seul)';
    }

    /**
     * Valeur à écrire pour un réglage : la recommandation ; pour un réglage partiel
     * (PARTIAL) déjà enregistré, les entrées enregistrées complétées ou corrigées par les
     * entrées recommandées (aucune entrée retirée, clés conservées telles quelles).
     */
    protected function merged(string $key, array $current, $recommended)
    {
        if (! in_array($key, self::PARTIAL, true) || ! is_array($current[$key] ?? null) || ! is_array($recommended)) {
            return $recommended;
        }

        $merged = $current[$key];
        foreach ($recommended as $sub => $value) {
            $merged[$sub] = $value;
        }

        return $merged;
    }

    /**
     * Textes de payment_labels enregistrés dans l'option, nettoyés comme le plugin
     * (balises retirées, espaces de bord, textes vides ignorés), sans le repli.
     *
     * @return array<string, string>
     */
    protected function savedPaymentLabels(array $settings): array
    {
        $out = [];
        foreach ((is_array($settings['payment_labels'] ?? null) ? $settings['payment_labels'] : []) as $key => $text) {
            $key = trim((string) $key);
            $text = is_scalar($text) ? trim(wp_strip_all_tags((string) $text)) : '';
            if ($key !== '' && $text !== '') {
                $out[$key] = $text;
            }
        }

        return $out;
    }

    /**
     * Textes du mode de règlement imprimé tels que le plugin les lit
     * (Settings::payment_labels()) : textes enregistrés, et pour « __none__ » absent,
     * l'ancien réglage no_gateway_label.
     *
     * @return array<string, string>
     */
    protected function paymentLabels(array $settings): array
    {
        $out = $this->savedPaymentLabels($settings);
        if (! isset($out['__none__'])) {
            $legacy = $this->legacyNoneLabel($settings);
            if ($legacy !== '') {
                $out['__none__'] = $legacy;
            }
        }

        return $out;
    }

    /**
     * Ancien réglage no_gateway_label (2.75 avant payment_labels), lu comme le plugin.
     */
    protected function legacyNoneLabel(array $settings): string
    {
        $value = $settings['no_gateway_label'] ?? '';

        return is_scalar($value) ? trim(wp_strip_all_tags((string) $value)) : '';
    }

    /**
     * Deux textes imprimés sont-ils équivalents ? Casse respectée ; espaces multiples et
     * apostrophe typographique (’) ou droite (') indifférents.
     */
    protected function sameText(string $a, string $b): bool
    {
        $norm = fn (string $v) => trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{2019}", "'", $v)));

        return $norm($a) === $norm($b);
    }

    /**
     * Libellé lisible d'une entrée de payment_labels.
     */
    protected function entryLabel(string $key): string
    {
        return match ($key) {
            '__none__' => 'Aucun moyen de paiement',
            '__free__' => 'Commande à 0 € sans moyen de paiement',
            '__credit__' => 'Réglée par carte cadeau ou avoir',
            default => $key,
        };
    }

    /**
     * Conseil pour un réglage « Cartes cadeaux » non conforme, selon son état :
     * - classe de taxe à corriger ou catégorie absente : rien n'est recommandé, --appliquer
     *   n'écrirait rien ;
     * - réglages absents : --appliquer les enregistre ;
     * - réglages déjà enregistrés et différents (une seule sauvegarde du formulaire du plugin
     *   enregistre la case décochée et aucune catégorie) : --appliquer ne les remplace pas.
     *   Le plus sûr est de les régler dans le plugin ; --appliquer --forcer remplacerait
     *   aussi les autres réglages différents, listés.
     */
    protected function giftAdvice(array $settings, array $gift, string $giftClass): string
    {
        if (! $gift['class_ok'] || ! $gift['term']) {
            return 'corrigez d\'abord la classe de taxe ou attendez la catégorie (voir l\'avertissement ci-dessus), puis relancez la commande';
        }

        $termId = (int) $gift['term']->term_id;
        $conform = [
            'gift_tax_enabled' => 'yes' === ($settings['gift_tax_enabled'] ?? 'no'),
            'gift_tax_class' => $giftClass === self::GIFT_TAX_CLASS,
            'gift_tax_categories' => in_array($termId, array_map('intval', (array) ($settings['gift_tax_categories'] ?? [])), true),
        ];
        $toWrite = [];
        $toFix = [];
        foreach ($conform as $key => $ok) {
            if ($ok) {
                continue;
            }
            if (array_key_exists($key, $settings)) {
                $toFix[] = $key;
            } else {
                $toWrite[] = $key;
            }
        }

        $advice = [];
        if ($toFix) {
            $others = array_values(array_filter($this->differKeys, fn ($key) => ! str_starts_with($key, 'gift_tax_')));
            $advice[] = sprintf(
                'déjà enregistré (%s, formulaire du plugin) et non remplacé par --appliquer : à régler dans le plugin (Facturation > Cartes cadeaux : case cochée, classe « %s », catégorie #%d), ou --appliquer --forcer%s',
                implode(', ', $toFix),
                self::GIFT_TAX_CLASS,
                $termId,
                $others ? ' (qui remplace aussi : '.implode(', ', $others).')' : ''
            );
        }
        if ($toWrite) {
            $advice[] = 'à enregistrer (--appliquer : '.implode(', ', $toWrite).')';
        }

        return $advice ? implode(' ; ', $advice) : 'à régler dans le plugin (Facturation > Cartes cadeaux)';
    }

    /**
     * Classe que le plugin applique aux cartes cadeaux quand gift_tax_class n'est pas
     * enregistré : Gift_Card_Tax::default_class() si la classe est chargée (sans autoload),
     * sinon le même calcul : zero-rate, puis taux-zero, si la classe existe dans WooCommerce ;
     * sinon la classe standard ('').
     */
    protected function giftDefaultClass(): string
    {
        $giftTax = '\APC\Billing\Gift_Card_Tax';
        if (class_exists($giftTax, false) && method_exists($giftTax, 'default_class')) {
            return (string) $giftTax::default_class();
        }

        $slugs = class_exists('\WC_Tax') ? array_map('strval', (array) \WC_Tax::get_tax_class_slugs()) : [];
        foreach (['zero-rate', 'taux-zero'] as $candidate) {
            if (in_array($candidate, $slugs, true)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * Classe de taxe enregistrée, lue comme le plugin : « standard » ou vide = '' (classe
     * standard).
     */
    protected function normalizeTaxClass($value): string
    {
        $class = sanitize_title(is_scalar($value) ? (string) $value : '');

        return $class === 'standard' ? '' : $class;
    }

    /**
     * Classe de taxe et catégorie des cartes cadeaux (lues une fois, lecture seule).
     *
     * class_ok : la classe existe et n'a aucun taux non nul. outside : produits « Carte
     * Cadeau – … » (créés à la volée par le thème) qui ne sont pas dans la catégorie.
     *
     * @return array{slugs: string[], class_exists: bool, class_ok: bool, class_name: string, rates: string[], non_zero: string[], term: \WP_Term|null, products: int, outside: int}
     */
    protected function giftCards(): array
    {
        if ($this->giftCards !== null) {
            return $this->giftCards;
        }

        global $wpdb;

        $slugs = class_exists('\WC_Tax') ? array_values(array_map('strval', (array) \WC_Tax::get_tax_class_slugs())) : [];
        $exists = in_array(self::GIFT_TAX_CLASS, $slugs, true);
        $name = self::GIFT_TAX_CLASS;
        $rates = [];
        $nonZero = [];
        if ($exists) {
            foreach ((array) \WC_Tax::get_tax_rate_classes() as $class) {
                if (isset($class->slug, $class->name) && $class->slug === self::GIFT_TAX_CLASS) {
                    $name = (string) $class->name;
                }
            }
            foreach ((array) \WC_Tax::get_rates_for_tax_class(self::GIFT_TAX_CLASS) as $rate) {
                $text = trim(((string) ($rate->tax_rate_country ?? '') ?: '*').' '.((float) ($rate->tax_rate ?? 0)).' %');
                $rates[] = $text;
                if ((float) ($rate->tax_rate ?? 0) > 0) {
                    $nonZero[] = $text;
                }
            }
        }

        $term = get_term_by('slug', self::GIFT_CATEGORY, 'product_cat');
        $term = $term instanceof \WP_Term ? $term : null;
        $inTerm = [];
        if ($term) {
            $objects = get_objects_in_term($term->term_id, 'product_cat');
            $inTerm = is_array($objects) ? array_map('intval', $objects) : [];
        }

        $titled = array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status NOT IN ('trash', 'auto-draft') AND post_title LIKE %s",
            $wpdb->esc_like('Carte Cadeau').'%'
        )));

        return $this->giftCards = [
            'slugs' => $slugs,
            'class_exists' => $exists,
            'class_ok' => $exists && ! $nonZero,
            'class_name' => $name,
            'rates' => $rates,
            'non_zero' => $nonZero,
            'term' => $term,
            // Produits publiés de la catégorie (compte WordPress du terme).
            'products' => $term ? (int) $term->count : 0,
            'outside' => count(array_diff($titled, $inTerm)),
        ];
    }

    /**
     * Commandes créées du MAY_FROM au MAY_TO (heure du site) qui n'ont ni facture WP
     * Overnight (_wcpdf_invoice_number) ni facture du plugin (_apc_invoice_number,
     * Invoice_Numbering::NUMBER_KEY), par statut. Lecture seule, tables HPOS ou articles
     * selon le stockage des commandes.
     *
     * @return array<string, int> statut sans « wc- » => nombre
     */
    protected function unbilledMayOrders(): array
    {
        global $wpdb;

        $from = get_gmt_from_date(self::MAY_FROM.' 00:00:00');
        $to = get_gmt_from_date(self::MAY_TO.' 23:59:59');
        $keys = "'_wcpdf_invoice_number', '_apc_invoice_number'";

        $util = '\Automattic\WooCommerce\Utilities\OrderUtil';
        if (class_exists($util) && $util::custom_orders_table_usage_is_enabled()) {
            $orders = $wpdb->prefix.'wc_orders';
            $meta = $wpdb->prefix.'wc_orders_meta';
            $sql = "SELECT o.status, COUNT(*) FROM `{$orders}` o
                WHERE o.type = 'shop_order' AND o.date_created_gmt BETWEEN %s AND %s
                AND NOT EXISTS (SELECT 1 FROM `{$meta}` m WHERE m.order_id = o.id AND m.meta_key IN ({$keys}) AND m.meta_value <> '')
                GROUP BY o.status";
        } else {
            $sql = "SELECT p.post_status, COUNT(*) FROM {$wpdb->posts} p
                WHERE p.post_type = 'shop_order' AND p.post_date_gmt BETWEEN %s AND %s
                AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key IN ({$keys}) AND m.meta_value <> '')
                GROUP BY p.post_status";
        }

        $counts = [];
        foreach ((array) $wpdb->get_results($wpdb->prepare($sql, $from, $to), ARRAY_N) as [$status, $count]) {
            $status = (string) $status;
            $status = str_starts_with($status, 'wc-') ? substr($status, 3) : $status;
            if (! in_array($status, ['trash', 'checkout-draft', 'auto-draft', 'draft'], true)) {
                $counts[$status] = (int) $count;
            }
        }

        return $counts;
    }

    /**
     * Date AAAA-MM-JJ affichée JJ/MM/AAAA.
     */
    protected function frDate(string $ymd): string
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m) ? $m[3].'/'.$m[2].'/'.$m[1] : $ymd;
    }

    /**
     * Dernier numéro de facture WP Overnight : AUTO_INCREMENT - 1 de la table des
     * numéros, contrôlé par le plus grand id de la table et le plus grand « number »
     * des métas _wcpdf_invoice_number_data. Le plus grand l'emporte (un numéro trop
     * bas créerait des doublons).
     *
     * @return array{last: int|null, details: string[]}
     */
    protected function wpoLastNumber(): array
    {
        global $wpdb;

        $details = [];
        $candidates = [];

        $table = $wpdb->prefix.'wcpdf_invoice_number';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table) {
            $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS WHERE Name = %s', $table), ARRAY_A);
            $autoIncrement = (int) ($status['Auto_increment'] ?? 0);
            $maxId = (int) $wpdb->get_var("SELECT MAX(id) FROM `{$table}`");
            $details[] = sprintf('Table %s : AUTO_INCREMENT %d, plus grand id %d', $table, $autoIncrement, $maxId);
            if ($autoIncrement > 1) {
                $candidates[] = $autoIncrement - 1;
            }
            if ($maxId > 0) {
                $candidates[] = $maxId;
            }
        } else {
            $details[] = sprintf('Table %s absente', $table);
        }

        $values = [];
        $metaTable = $wpdb->prefix.'wc_orders_meta';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($metaTable))) === $metaTable) {
            $values = $wpdb->get_col("SELECT meta_value FROM `{$metaTable}` WHERE meta_key = '_wcpdf_invoice_number_data'");
        }
        $values = array_merge($values, $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wcpdf_invoice_number_data'"));

        $maxMeta = 0;
        foreach ($values as $raw) {
            $maxMeta = max($maxMeta, $this->wpoMetaNumber((string) $raw));
        }
        $details[] = sprintf('Métas _wcpdf_invoice_number_data : %d, plus grand numéro %d', count($values), $maxMeta);
        if ($maxMeta > 0) {
            $candidates[] = $maxMeta;
        }

        if (count(array_unique($candidates)) > 1) {
            $details[] = 'Sources en désaccord : le plus grand numéro est retenu.';
        }

        return ['last' => $candidates ? max($candidates) : null, 'details' => $details];
    }

    /**
     * Numéro séquentiel d'une méta _wcpdf_invoice_number_data (tableau sérialisé),
     * lu sans instancier d'objet.
     */
    protected function wpoMetaNumber(string $raw): int
    {
        if (preg_match('/s:6:"number";(?:i:(\d+)|s:\d+:"(\d+)")/', $raw, $m)) {
            return (int) ($m[1] !== '' ? $m[1] : $m[2]);
        }

        if (is_serialized($raw)) {
            $data = @unserialize($raw, ['allowed_classes' => false]);
            if (is_array($data) && isset($data['number']) && is_numeric($data['number'])) {
                return (int) $data['number'];
            }
        }

        return 0;
    }

    /**
     * Compteurs du plugin (option apc_billing_counters).
     *
     * @return array<string, int>
     */
    protected function counters(): array
    {
        $counters = get_option(self::COUNTERS, []);

        return is_array($counters) ? $counters : [];
    }

    /**
     * Valeur d'un réglage WP Overnight (traduisible : tableau par langue).
     */
    protected function wpoValue(array $wpo, string $key): string
    {
        $value = $wpo[$key] ?? '';
        if (is_array($value)) {
            $value = $value['default'] ?? reset($value);
        }

        return trim((string) $value);
    }

    /**
     * Adresse de l'émetteur, une ligne par élément (pays seulement s'il diffère de
     * celui de la boutique).
     */
    protected function wpoAddress(array $wpo): string
    {
        $lines = [
            $this->wpoValue($wpo, 'shop_address_line_1'),
            $this->wpoValue($wpo, 'shop_address_line_2'),
            trim($this->wpoValue($wpo, 'shop_address_postcode').' '.$this->wpoValue($wpo, 'shop_address_city')),
        ];

        $country = strtoupper($this->wpoValue($wpo, 'shop_address_country'));
        $base = function_exists('WC') && WC()->countries ? WC()->countries->get_base_country() : 'FR';
        if ($country !== '' && $country !== $base) {
            $names = function_exists('WC') && WC()->countries ? WC()->countries->get_countries() : [];
            $lines[] = $names[$country] ?? $country;
        }

        return sanitize_textarea_field(implode("\n", array_filter($lines, fn ($line) => $line !== '')));
    }

    /**
     * SIREN (ou SIRET) et n° de TVA tirés du champ libre « informations
     * complémentaires » de WP Overnight (ex. « SIREN : 837574573 / TVA : FR35… »).
     *
     * @return array{0: string, 1: string}
     */
    protected function parseIdentity(string $text): array
    {
        $siren = '';
        $vat = '';

        foreach (preg_split('/\R/u', wp_strip_all_tags($text)) as $line) {
            $value = trim(str_contains($line, ':') ? substr($line, strrpos($line, ':') + 1) : $line);

            if ($siren === '' && preg_match('/\bSIRE[NT]\b/iu', $line)) {
                $digits = (string) preg_replace('/\D+/', '', $value);
                if (strlen($digits) === 9 || strlen($digits) === 14) {
                    $siren = $digits;
                }
            } elseif ($vat === '' && preg_match('/\b(TVA|VAT)\b/iu', $line)) {
                $compact = strtoupper((string) preg_replace('/\s+/', '', $value));
                if (preg_match('/^[A-Z]{2}[0-9A-Z]{2,13}$/', $compact)) {
                    $vat = $compact;
                }
            }
        }

        return [$siren, $vat];
    }

    /**
     * Numéro formaté (mêmes jetons que le plugin).
     */
    protected function format(string $format, int $number, int $digits): string
    {
        if (is_callable(['\APC\Billing\Invoice_Numbering', 'render_format'])) {
            return (string) \APC\Billing\Invoice_Numbering::render_format($format, $number, $digits);
        }

        return strtr($format, [
            '{NUM}' => str_pad((string) $number, $digits, '0', STR_PAD_LEFT),
            '{AAAA}' => current_time('Y'),
            '{AA}' => current_time('y'),
            '{MM}' => current_time('m'),
            '{JJ}' => current_time('d'),
        ]);
    }

    /**
     * Deux valeurs de réglage sont-elles équivalentes ? (listes sans ordre, nombres
     * et textes, couleurs sans casse, fins de ligne)
     */
    protected function same($a, $b): bool
    {
        if (is_array($a) || is_array($b)) {
            $a = array_map('strval', (array) $a);
            $b = array_map('strval', (array) $b);
            sort($a);
            sort($b);

            return $a === $b;
        }

        $norm = fn ($v) => strtolower(trim((string) preg_replace('/\R/u', "\n", (string) $v)));

        return $norm($a) === $norm($b);
    }

    /**
     * Valeur lisible sur une ligne.
     */
    protected function display($value): string
    {
        if ($value === null) {
            return '(vide)';
        }
        if (is_array($value) && $value && ! array_is_list($value)) {
            // Tableau clé => texte (payment_labels).
            $pairs = [];
            foreach ($value as $key => $text) {
                $pairs[] = $this->entryLabel((string) $key).' : '.(is_scalar($text) ? (string) $text : '');
            }

            return implode(' ; ', $pairs);
        }
        if (is_array($value)) {
            return $value ? implode(', ', array_map('strval', $value)) : '(aucun)';
        }
        $text = trim((string) preg_replace('/\s*\R\s*/u', ' / ', (string) $value));

        return $text === '' ? '(vide)' : $text;
    }

    protected function cut(string $text): string
    {
        return mb_strlen($text) > self::CELL ? mb_substr($text, 0, self::CELL - 1).'…' : $text;
    }

    /**
     * Valeurs par défaut du plugin (affichage seulement).
     */
    protected function defaults(): array
    {
        $storeAddress = implode("\n", array_filter([
            (string) get_option('woocommerce_store_address'),
            (string) get_option('woocommerce_store_address_2'),
            trim(get_option('woocommerce_store_postcode').' '.get_option('woocommerce_store_city')),
        ]));

        return [
            'emitter_name' => get_bloginfo('name'),
            'emitter_address' => $storeAddress,
            'emitter_siret' => '',
            'emitter_vat' => '',
            'emitter_phone' => '',
            'emitter_email' => (string) get_option('admin_email'),
            'emitter_footer' => '',
            'emitter_logo_url' => '',
            'invoice_accent_color' => '#b5254b',
            'invoice_format_part' => 'FAC-{AAAA}-{NUM}',
            'invoice_digits_part' => 5,
            'invoice_reset_part' => 'yes',
            'invoice_number_separate' => 'no',
            'invoice_statuses' => ['wc-processing', 'wc-completed'],
            'enable_fields' => 'yes',
            'inv_show_note' => 'yes',
            'hide_name_notes' => 'no',
            'hidden_item_meta' => '',
            'view_capability' => 'manage_woocommerce',
            'legacy_meta_key' => 'auto',
            'attach_admin_new_order' => 'no',
            'search_invoice_number' => 'no',
            'invoice_from_date' => '',
            'gift_tax_enabled' => 'no',
            // Classe « taux zéro » de WooCommerce si elle existe (comme le plugin).
            'gift_tax_class' => $this->giftDefaultClass(),
            'gift_tax_categories' => [],
            'gift_tax_products' => [],
            'gift_tax_mention' => '',
            'payment_labels' => [],
            'deliver_to_customer' => 'no',
            'deliver_emails' => [],
            'enable_credit_notes' => 'no',
            'credit_numbering' => 'same',
            'credit_deliver_to_customer' => 'no',
            'enable_invoicing' => 'no',
            'invoicing_test_mode' => 'yes',
        ];
    }

    protected function labels(): array
    {
        return [
            'emitter_name' => 'Raison sociale',
            'emitter_address' => 'Adresse',
            'emitter_siret' => 'SIREN / SIRET',
            'emitter_vat' => 'N° de TVA',
            'emitter_phone' => 'Téléphone',
            'emitter_email' => 'E-mail',
            'emitter_footer' => 'Pied de page',
            'emitter_logo_url' => 'Logo',
            'invoice_accent_color' => 'Couleur principale',
            'invoice_format_part' => 'Format du numéro',
            'invoice_digits_part' => 'Chiffres du compteur',
            'invoice_reset_part' => 'Remise à zéro annuelle',
            'invoice_number_separate' => 'Séries pro séparées',
            'invoice_statuses' => 'Statuts déclencheurs',
            'enable_fields' => 'Champs pro du plugin',
            'inv_show_note' => 'Note client imprimée',
            'hide_name_notes' => 'Notes du nom masquées',
            'hidden_item_meta' => 'Infos d\'articles masquées',
            'view_capability' => 'Accès aux factures',
            'legacy_meta_key' => 'Ancien plugin de factures',
            'attach_admin_new_order' => 'Facture sur « Nouvelle commande »',
            'search_invoice_number' => 'Recherche par n° de facture',
            // Plus recommandé : libellé gardé pour signaler un réglage déjà enregistré.
            'invoice_from_date' => 'Facturer à partir du',
            'gift_tax_enabled' => 'Cartes cadeaux sans TVA',
            'gift_tax_class' => 'Classe de taxe des cartes',
            'gift_tax_categories' => 'Catégories des cartes',
            'gift_tax_products' => 'Produits cartes cadeaux',
            'gift_tax_mention' => 'Mention sous les cartes',
            'payment_labels' => 'Mode de règlement imprimé',
            'deliver_to_customer' => 'Envoi de la facture au client',
            'deliver_emails' => 'E-mails client avec facture',
            'enable_credit_notes' => 'Factures d\'avoir',
            'credit_numbering' => 'Série des avoirs',
            'credit_deliver_to_customer' => 'Envoi de l\'avoir au client',
            'enable_invoicing' => 'Facturation activée',
            'invoicing_test_mode' => 'Mode test facturation',
        ];
    }
}
