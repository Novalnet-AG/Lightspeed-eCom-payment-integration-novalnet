<?php
/**
 * Novalnet payment module
 *
 * This module is used for real time processing of Novalnet transaction of customers.
 *
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: de_DE.php
 */

return array
(
    // back-end configuration form translation
    'api_key_title' => 'Produktaktivierungsschlüssel',
    'tariff_title' => 'Auswahl der Tarif-ID',
    'test_mode_title' => 'Testmodus aktivieren',
    'test_mode_title_tooltip' => 'Aktivieren Sie diese Option, um das Bezahlen auf Ihrer Checkout-Seite zu testen. Im Testmodus werden Zahlungen nicht von Novalnet ausgeführt. Vergessen Sie nicht, den Testmodus nach dem Testen wieder zu deaktivieren, um sicherzustellen, dass die echten Bestellungen ordnungsgemäß abgerechnet werden.',
    'payment_action_title' => 'Zahlungsbestätigung',
    'payment_action_title_tooltip' => 'Wählen Sie, ob die Zahlung sofort belastet werden soll oder nicht. Zahlung einziehen: Betrag sofort belasten. Zahlung autorisieren: Die Zahlung wird überprüft und autorisiert, aber erst zu einem späteren Zeitpunkt belastet. So haben Sie Zeit, über die Bestellung zu entscheiden.',
    'manual_check_limit' => 'Mindesttransaktionsbetrag für die Autorisierung',
    'manual_check_limit_desc' => 'Übersteigt der Bestellbetrag das genannte Limit, wird die Zahlung erst dann ausgeführt, wenn Sie die Transaktion bestätigen. Wenn Sie das Feld leer lassen, werden automatisch alle Transaktionen sofort abgerechnet.',
    'gateway_timeout_title' => 'Zeitlimit der Schnittstelle (in Sekunden)',
    'referrer_id_title' => 'Partner-ID',
    'onhold_order_status_title' => 'On-hold-Bestellstatus',
    'cancellation_order_status' => 'Status für stornierte Bestellungen',
    'deactivate_ip_check_title' => 'Händlerskript-Prozess manuell testen zulassen',
    'callback_mail_notification_title' => 'E-Mail-Benachrichtigungen einschalten',
    'callback_mail_to_title' => 'E-Mails senden an',
    'callback_mail_bcc_title' => 'E-Mails in Blindkopie (BCC) senden an',
    'payment_confirmation_order_status' => 'Status für erfolgreichen Auftragsabschluss',
    'callback_order_status_title' => 'Callback-Bestellstatus',
    'invoice_due_date_title' => 'Fälligkeit der Rechnung (in Tagen)',
    'sepa_due_date_title' => 'Fälligkeit der Rechnung (in Tagen)',
    'slip_due_date_title' => 'Ablauffrist des Zahlscheins (in Tagen)',
    'payment_pending_order_status_title' => 'Status für Bestellungen mit ausstehender Zahlung',
    'api_key_tooltip' => 'Der Produktaktivierungsschlüssel verbindet Ihre Lightspeed Payment App mit Ihrem Novalnet-Händleraccount. Den Produktaktivierungsschlüssel finden Sie Novalnet-Händleradminportal (Link s. oben auf dieser Seite). ',
    'tariff_tooltip' => 'Wählen Sie eine Tarif-ID, die dem bevorzugten Tarifplan entspricht, den Sie im Novalnet-Händleradminportal für dieses Projekt erstellt haben. In der Installationsbeschreibung der Novalnet Payment-App finden Sie hierzu weitere Hinweise.',
    'gateway_tooltip' => 'Legen Sie das Zeitlimit für eine Bestätigung der Transaktion durch das Novalnet-System fest. Wenn Ihr Server keine rechtzeitige Antwort erhält, wird die Bestellung nicht freigegeben. Das empfohlene Zeitlimit beträgt 240 Sekunden.',
    'referrer_tooltip' => 'Partner-ID der Person/Firma, die Ihnen Novalnet empfohlen hat.',
    'callback_mail_to_tooltip' => 'An diese E-Mail-Adresse werden die Bestätigungen über die erfolgreiche Ausführung des Händlerskripts gesendet.',
    'callback_mail_bcc_tooltip' => 'Wenn Sie die Händlerskript-Bestätigungen in Blindkopie (BCC) an weitere Adressen senden möchten, geben Sie diese hier ein.',
    'callback_url' => 'Benachrichtigungs-URL',
    'callback_url_tooltip' => 'Die Benachrichtigungs-URL ist erforderlich, um Ihre Datenbank/Ihr System aktuell zu halten und stets mit dem Novalnet-Transaktionsstatus zu synchronisieren.',
    'invoice_due_date_tooltip' => 'Geben Sie die Anzahl der Tage ein, binnen derer die Zahlung bei Novalnet eingehen soll (muss größer als 7 Tage sein). Falls dieses Feld leer ist, werden 14 Tage als Standard-Zahlungsfrist gesetzt',
    'barzahlen_due_date_tooltip' => 'Geben Sie die Anzahl der Tage ein, binnen derer der Betrag in einer Barzahlen-Partnerfiliale bezahlt werden muss. Wenn das Feld leer ist, werden standardmäßig 14 Tage als Fälligkeitsdatum gesetzt, danach verfällt der Zahlschein.',
    'sepa_due_date_tooltip' => 'Geben Sie die Anzahl der Tage ein, nach denen der Zahlungsbetrag eingezogen werden soll (muss zwischen 2 und 14 Tagen liegen).',
    'success_message' =>'Erfolgreich aktualisiert',
    'session_timeout' => 'Sitzungs-Timeout',
    'error_message' => 'Ungültige Angaben',
    'status_config' => 'Bestellstatus-Management',
    'not_paid' => 'WARTEN AUF ZAHLUNGSEINGANG',
    'updates' => 'Novalnet-Updates',
    'paid' => 'FERTIG FÜR DEN VERSAND / ABHOLUNG IM GESCHÄFT VORBEREITEN',
    'cancelled' => 'STORNIERT',
    'on_hold' => 'ZURÜCKGESTELLT',
    'updates' => 'Novalnet-Updates',
    'module_version' => 'Novalnet-Payment-App V2.0.1',
    'new'=>'Um mit der Novalnet Payment App zu starten, lesen Sie bitte die Installationsbeschreibung',
    'product_key'=>"Aktivierungsschlüssel des Produkts",
    'vendor_script' => 'Aktualisierung der Händlerskript-URL / Notifikation & Webhook -URL',
    'product_key_desc1' => 'Verwenden Sie den Produktaktivierungsschlüssel von Novalnet, um in den Allgemeinen Einstellungen automatisch Ihre vollständigen Händlerdaten einzutragen.',
    'product_key_desc2' => 'Ihren Produktaktivierungsschlüssel finden Sie im <a href="https://admin.novalnet.de" target="_new">Novalnet-Händleradminportal</a> unter <strong>PROJEKT - </strong>Projekt anklicken - <strong>Parameter Ihres Shops</strong> - <strong>API Signature (Aktivierungsschlüssel des Produkts)</strong>.',
    'vendor_script_desc1' => "Die Händlerskript-URL wird benötigt, um die Datenbank / das System des Händlers auf dem neuesten Stand zu halten und mit Novalnet zu synchronisieren (z.B. Lieferung des aktuellen Transaktionsstatus). ",
    'vendor_script_desc2' => "Das System von Novalnet überträgt (über asynchronen Aufruf) Informationen über den gesamten Transaktionsstatus an das System des Händlers",
    'vendor_script_desc3' => "Konfigurieren Sie die Händlerskript-URL im <a href=https://admin.novalnet.de target=_new>Novalnet-Händleradminportal</a> unter <strong>PROJEKT - </strong>Projekt auswählen - <strong>Projektübersicht</strong> - <strong>Händlerskript-URL / Notifikation & Webhook -URL</strong>",
    'paypal_desc1' =>"Um PayPal-Transaktionen zu akzeptieren, müssen Sie die PayPal-API-Informationen im <a href=https://admin.novalnet.de target=_new>Novalnet-Händleradminportal</a> unter <strong>PROJEKT </strong>- Projekt auswählen - <strong>Zahlungsmethoden</strong> - <strong>PayPal</strong> - <strong>Konfigurieren eingeben</strong>.",
    'config_error'=>'Wert darf nicht leer sein',
    'valid_error' => 'Bitte geben Sie gültige Details ein',
    'admin_portal_desc'              => 'Willkommen zur Novalnet Payment App für Lightspeed eCom! Zur Integration in Ihr Lightspeed eCom System und für zusätzliche Einstellungen müssen Sie sich in das <span class = "nn_admin_link">Novalnet-Händleradminportal</span> einloggen. Für den Login benötigen Sie einen Händleraccount bei Novalnet. Wenn Sie noch kein Kunde sind, kontaktieren Sie uns bitte per E-Mail an <a class ="mail_link"  href="mailto:&#115;&#097;&#108;&#101;&#115;&#064;&#110;&#111;&#118;&#097;&#108;&#110;&#101;&#116;&#046;&#100;&#101;">&#115;&#097;&#108;&#101;&#115;&#064;&#110;&#111;&#118;&#097;&#108;&#110;&#101;&#116;&#046;&#100;&#101; </a>oder per Tel.: <a class ="mail_link" href="tel:+49 89 9230683-20">+49 89 9230683-20</a>. <br/><br/>
                    Bitte lesen Sie die <a class ="mail_link" href="https://www.novalnet.de/installationsbeschreibung/lightspeedeCom_feature_2020-10-21_novalnet_payment_app_2.0.1.pdf" target=_new>Installationsbeschreibung</a>, um mit der Novalnet Payment App zu starten.',
    'global_configuration' => 'Allgemeine Novalnet-Einstellungen',
    'payment_configuration' => 'Novalnet-Zahlungseinstellungen',
    'general' => 'Allgemein',
    'onhold_config' => 'Verwaltung des Bestellstatus für ausgesetzte Zahlungen',
    'merchant_config' => 'Verwaltung des Händlerskripts',
    'save' => 'Speichern',
    'yes' => 'ja',
    'no' => 'keine',
    'authorise' => 'Zahlung autorisieren',
    'capture' => 'Zahlung einziehen',
    // payment transaction translation
    'check_hash' => 'Während der Umleitung wurden einige Daten geändert. Die Überprüfung des  Hashes schlug fehl',
    'transaction_detail' => 'Novalnet-Transaktionsdetails',
    'guarantee_payment' => 'Diese Transaktion wird mit Zahlungsgarantie verarbeitet',
    'test' => 'Testbestellung',
    'transaction_id' => 'Novalnet-Transaktions-ID:',
    'invoice_gurantee_text' => 'Ihre Bestellung ist in Bearbeitung. Sobald diese bestätigt wurde, erhalten Sie alle notwendigen Zahlungsinformationen, um den Betrag zu überweisen. Dies kann bis zu 24 Stunden dauern.',
    'sepa_gurantee_text' => 'Ihre Bestellung wird derzeit überprüft. Wir werden Sie in Kürze über den Bestellstatus informieren. Bitte beachten Sie, dass dies bis zu 24 Stunden dauern kann.',
    'transfer_amount' => 'Bitte überweisen Sie den Betrag auf das unten stehende Konto.',
    'due_date' =>  'Fälligkeitsdatum: ',
    'sepa_due_date' => 'Tage bis zum Einzug des Betrags per SEPA-Lastschrift',
    'account_holder' =>'Kontoinhaber: ',
    'slip_date' => 'Verfallsdatum des Zahlscheins',
    'stores' => 'Barzahlen-Partnerfilialen in Ihrer Nähe',
    'msg' => 'Ihre Bestellung wurde bestätigt!',
    'order_confirmnation' => 'Bestellbestätigung – Ihre Bestellung %1$s über %2$s wurde bestätigt!',
    'reference_desc' => 'Bitte verwenden Sie einen der unten angegebenen Verwendungszwecke für die Überweisung. Nur so kann Ihr Geldeingang Ihrer Bestellung zugeordnet werden:',
    'reference1' => 'Verwendungszweck 1: ',
    'reference2' => 'Verwendungszweck 2: ',
    'credit_comment' => 'Novalnet-Callback-Skript erfolgreich ausgeführt für: TID  %1$s mit Betrag %2$s am , um %3$s Uhr. Nach bezahlter Transaktion finden Sie diese in unserer Novalnet-Händleradministration unter folgender TID: %4$s.',
    'trans_details'             => 'Novalnet-Transaktionsdetails',
    'test_order'                => 'Testbestellung',
    'guarantee_payment_on_hold' => 'Der Status der Transaktion mit der TID %1$s wurde am %2$s um %3$s Uhr von ausstehende Zahlung auf on hold geändert',
    'guarantee_payment_confirm' => 'Die Transaktion wurde am %1$s um %2$sUhr bestätigt.',
    'guarantee_payment_cancel'  => 'Die Transaktion wurde am %1$s um  %2$s Uhr storniert.',
    'guarantee_payment_text'    => 'Diese Transaktion wird mit Zahlungsgarantie verarbeitet',
    'cancel_comment'            => 'Die Transaktion wurde storniert. Grund:',
    'payment_method_comment' => 'Zahlungsart: ',
    'credit_card' => 'Kreditkarte',
    'sepa' => 'Lastschrift SEPA',
    'invoice' => 'Rechnung',
    'prepayment' => 'Vorkasse',
    'sofort' => 'Sofortüberweisung',    
    'chargeback_comment' => 'Novalnet-Callback-Nachricht erhalten: Rückbuchung erfolgreich ausgeführt für TID: %1$s mit Betrag %2$s am %3$s um Uhr. TID der Folgebuchung: %4$s.',
    'refund_comment' => 'Rückerstattung / Bookback erfolgreich ausgeführt. TID: %1$s  mit Betrag %2$s vom %3$s. TID der Folgebuchung: %4$s',
    'minimum_amount' => 'Mindestbestellbetrag für Zahlungsgarantie',
    'minimum_amount_tooltip' => 'Geben Sie den Mindestbetrag (in Cent) für die zu bearbeitende Transaktion mit Zahlungsgarantie ein. Geben Sie z.B. 100 ein, was 1,00 entspricht. Der Standbetrag ist 9,99 EUR. ',
    'force_guarantee' => 'Zahlung ohne Zahlungsgarantie erzwingen',
    'force_guarantee_tooltip' => 'Falls die Zahlungsgarantie zwar aktiviert ist, jedoch die Voraussetzungen für Zahlungsgarantie nicht erfüllt sind, wird die Zahlung ohne Zahlungsgarantie verarbeitet. Die Voraussetzungen finden Sie in der Installationsanleitung unter "Zahlungsgarantie aktivieren".',
    'payment_gateways' => 'Zahlungsarten',
    'enable_payment_guarantee' => 'Zahlungsgarantie aktivieren',
    'novalnet_cc' => ' Kreditkarte',
    'novalnet_sepa' => ' Lastschrift SEPA',
    'novalnet_invoice' => ' Rechnung',
    'novalnet_prepayment' => ' Vorkasse',
    'novalnet_sofort' => ' Sofortüberweisung',
    'novalnet_payment_gateways_lable' => "Wählen Sie die Zahlungsarten, die auf Ihrer Lightspeed ecom-Checkoutseite angezeigt werden sollen. Sie können diese im Novalnet-Händleradminportal aktivieren. Weitere Einzelheiten finden Sie in der <a href='https://www.novalnet.de/installationsbeschreibung/lightspeedeCom_feature_2020-10-21_novalnet_payment_app_2.0.1.pdf' class='payment_guide'><strong>Installationsanleitung der Novalnet Payment App</strong></a>.",
    'payment_pending_order_status_title_tooltip' => 'Wählen Sie, welcher Status für Bestellungen mit ausstehender Zahlung verwendet wird.',
    'payment_confirmation_order_status_tooltip' => 'Wählen Sie, welcher Status für erfolgreich abgeschlossene Bestellungen verwendet wird.',
    'callback_order_status_title_tooltip' => 'Wählen Sie, welcher Status nach der erfolgreichen Ausführung des Novalnet-Callback-Skripts (ausgelöst bei erfolgreicher Zahlung) verwendet wird.',
    'onhold_order_status_title_tooltip' => 'Wählen Sie, welcher Status für On-hold-Bestellungen verwendet wird, solange diese nicht bestätigt oder storniert worden sind.',
    'cancellation_order_status_title_tooltip' => 'Wählen Sie, welcher Status für stornierte oder voll erstattete Bestellungen verwendet wird.',
    'callback_mail_notification_title_tooltip' => 'Aktivieren Sie diese Option, um eine Benachrichtigung an die angegebene E-Mail-Adresse zu senden, sobald das Händlerskript erfolgreich ausgeführt wurde. In der Installationsbeschreibung der Novalnet Payment-App finden Sie hierzu weitere Hinweise.',
    'deactivate_ip_check_title_tooltip' => 'Aktivieren Sie diese Option, um das Novalnet-Händlerskript manuell zu testen. Deaktivieren Sie diese Option, bevor Sie Ihren Shop in den LIVE-Modus schalten, um unberechtigte Aufrufe von extern (außer von Novalnet) zu vermeiden. In der Installationsbeschreibung der Novalnet Payment-App finden Sie hierzu weitere Hinweise.',
    'enable_payment_guarantee_tooltip' => "Voraussetzungen für die Zahlungsgarantie: 1) Zugelassene Länder: AT, DE, CH 2) Zugelassene Währung: EUR 3) Mindestbetrag der Bestellung: 9,99 EUR 4) Mindestalter: 18 Jahre 5) Rechnungsadresse und Lieferadresse müssen übereinstimmen 6) Geschenkgutscheine / Coupons sind nicht erlaubt",
    'guarntee_minimum_amount_error' => 'Der Mindestbetrag sollte bei mindestens 9,99 EUR',
    'sepa_due_date_error' => 'SEPA Fälligkeitsdatum Ungültiger',
    'invoice_due_date_error' => 'Geben Sie bitte ein gültiges Fälligkeitsdatum ein',
    'mail_error' => 'Geben Sie eine gültige E-Mail-Adresse ein',
    'novalnet_url' => 'https://www.novalnet.de/',
    'novalnet_cc_enforce_3d_title' => '3D-Secure-Zahlungen außerhalb der EU erzwingen',
    'novalnet_cc_enforce_3d_tooltip' => 'Wenn Sie diese Option aktivieren, werden alle Zahlungen mit Karten, die außerhalb der EU ausgegeben wurden, mit der starken Kundenauthentifizierung (Strong Customer Authentication, SCA) von 3D-Secure 2.0 authentifiziert.',
);
