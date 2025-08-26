<?php

/**
 * Payment Management for Club Dashboard
 * 
 * Handles payment history, invoices, and financial information for clubs.
 * Provides secure access to financial data with proper ownership verification.
 *
 * @package UFSC_Gestion_Club
 * @subpackage Frontend\Club
 * @since 1.0.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render payments section for club dashboard
 *
 * @param object $club Club object
 * @return string HTML output for payments section
 */
function ufsc_club_render_paiements($club)
{
    // Security check: verify club access
    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé : vous ne pouvez accéder qu\'aux données de votre propre club.</div>';
    }

    $output = '<h2 class="ufsc-section-title">Paiements et facturation</h2>';

    // Payment status overview
    $output .= ufsc_render_payment_status($club);

    // Payment history
    $output .= ufsc_render_payment_history($club);

    // Upcoming payments
    $output .= ufsc_render_upcoming_payments($club);

    // Invoice management
    $output .= ufsc_render_invoice_management($club);

    return $output;
}

/**
 * Render payment status overview
 *
 * @param object $club Club object
 * @return string HTML output for payment status
 */
function ufsc_render_payment_status($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-money-alt"></i> État des paiements';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    // Information about UFSC fees - according to requirements
    $output .= '<div class="ufsc-info-box ufsc-mb-20">
                <h4>Tarifs UFSC</h4>
                <p>• <strong>Cotisation d\'affiliation :</strong> 150€/an</p>
                <p>• <strong>Licence individuelle :</strong> 35€</p>
                </div>';

    // Get payment status (simplified to only show required items)
    $payment_status = ufsc_get_club_payment_status($club);

    // Status indicators - only affiliation and licenses as per requirements
    $output .= '<div class="ufsc-payment-status-grid">';

    $status_items = [
        'affiliation' => [
            'label' => 'Cotisation d\'affiliation',
            'status' => $payment_status['affiliation_paid'] ?? false,
            'amount' => '150€',
            'due_date' => $payment_status['affiliation_due_date'] ?? null
        ],
        'licences' => [
            'label' => 'Frais de licences',
            'status' => $payment_status['licences_paid'] ?? false,
            'amount' => '35€ par licence',
            'due_date' => $payment_status['licences_due_date'] ?? null
        ]
    ];

    foreach ($status_items as $key => $item) {
        $status_class = $item['status'] ? 'ufsc-payment-paid' : 'ufsc-payment-pending';
        $status_text = $item['status'] ? 'Payé' : 'En attente';
        $status_icon = $item['status'] ? 'yes-alt' : 'clock';

        $output .= '<div class="ufsc-payment-status-item ' . $status_class . '">';
        $output .= '<div class="ufsc-payment-header">';
        $output .= '<div class="ufsc-payment-icon">';
        $output .= '<i class="dashicons dashicons-' . $status_icon . '"></i>';
        $output .= '</div>';
        $output .= '<div class="ufsc-payment-info">';
        $output .= '<h4>' . esc_html($item['label']) . '</h4>';
        $output .= '<span class="ufsc-payment-status">' . $status_text . '</span>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<div class="ufsc-payment-details">';
        $output .= '<div class="ufsc-payment-amount">' . esc_html($item['amount']) . '</div>';
        if (!$item['status'] && !empty($item['due_date'])) {
            $output .= '<div class="ufsc-payment-due">Échéance: ' . esc_html($item['due_date']) . '</div>';
        }
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';

    // Outstanding balance alert (simplified)
    $outstanding_balance = $payment_status['outstanding_balance'] ?? 0;
    if ($outstanding_balance > 0) {
        $output .= '<div class="ufsc-alert ufsc-alert-warning">';
        $output .= '<h4>Solde en attente</h4>';
        $output .= '<p>Vous avez un solde de <strong>' . number_format($outstanding_balance, 2) . ' €</strong> en attente de paiement.</p>';
        $output .= '<p><a href="#payment-methods" class="ufsc-btn ufsc-btn-primary">Effectuer un paiement</a></p>';
        $output .= '</div>';
    } else {
        $output .= '<div class="ufsc-alert ufsc-alert-success">';
        $output .= '<p><i class="dashicons dashicons-yes-alt"></i> Tous vos paiements sont à jour.</p>';
        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render payment history
 *
 * @param object $club Club object
 * @return string HTML output for payment history
 */
function ufsc_render_payment_history($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-list-view"></i> Historique des paiements';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    // Get payment history
    $payments = ufsc_get_club_payment_history($club->id);

    if (empty($payments)) {
        $output .= '<div class="ufsc-empty-state">';
        $output .= '<div class="ufsc-empty-icon"><i class="dashicons dashicons-money-alt"></i></div>';
        $output .= '<h3>Aucun paiement enregistré</h3>';
        $output .= '<p>L\'historique de vos paiements apparaîtra ici une fois effectués.</p>';
        $output .= '</div>';
    } else {
        $output .= '<div class="ufsc-table-responsive">';
        $output .= '<table class="ufsc-table ufsc-payments-table">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th>Date</th>';
        $output .= '<th>Description</th>';
        $output .= '<th>Montant</th>';
        $output .= '<th>Statut</th>';
        $output .= '<th>Facture</th>';
        $output .= '<th>Actions</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';

        foreach ($payments as $payment) {
            $status_class = ufsc_get_payment_status_class($payment['status']);
            $status_text = ufsc_get_payment_status_text($payment['status']);

            $output .= '<tr>';
            $output .= '<td>' . esc_html(date('d/m/Y', strtotime($payment['date']))) . '</td>';
            $output .= '<td>' . esc_html($payment['description']) . '</td>';
            $output .= '<td>' . number_format($payment['amount'], 2) . ' €</td>';
            $output .= '<td><span class="ufsc-badge ' . $status_class . '">' . $status_text . '</span></td>';
            $output .= '<td>';
            if (!empty($payment['invoice_number'])) {
                $output .= esc_html($payment['invoice_number']);
            } else {
                $output .= '-';
            }
            $output .= '</td>';
            $output .= '<td>';
            $output .= '<div class="ufsc-action-buttons">';
            
            // View receipt/invoice
            if (!empty($payment['receipt_url'])) {
                $output .= '<a href="' . esc_url($payment['receipt_url']) . '" target="_blank" class="ufsc-btn-sm" title="Voir le reçu">';
                $output .= '<i class="dashicons dashicons-visibility"></i>';
                $output .= '</a>';
            }
            
            // Download invoice
            if (!empty($payment['invoice_url'])) {
                $output .= '<a href="' . esc_url($payment['invoice_url']) . '" target="_blank" class="ufsc-btn-sm" title="Télécharger la facture">';
                $output .= '<i class="dashicons dashicons-download"></i>';
                $output .= '</a>';
            }
            
            $output .= '</div>';
            $output .= '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render upcoming payments section
 *
 * @param object $club Club object
 * @return string HTML output for upcoming payments
 */
function ufsc_render_upcoming_payments($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-calendar-alt"></i> Prochaines échéances';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    // Get upcoming payments
    $upcoming_payments = ufsc_get_upcoming_payments($club);

    if (empty($upcoming_payments)) {
        $output .= '<div class="ufsc-alert ufsc-alert-success">';
        $output .= '<p><i class="dashicons dashicons-yes-alt"></i> Aucune échéance de paiement prévue dans les 60 prochains jours.</p>';
        $output .= '</div>';
    } else {
        $output .= '<div class="ufsc-upcoming-payments">';
        
        foreach ($upcoming_payments as $payment) {
            $days_until_due = ufsc_get_days_until_due($payment['due_date']);
            $urgency_class = '';
            
            if ($days_until_due <= 7) {
                $urgency_class = 'ufsc-payment-urgent';
            } elseif ($days_until_due <= 30) {
                $urgency_class = 'ufsc-payment-warning';
            }

            $output .= '<div class="ufsc-upcoming-payment-item ' . $urgency_class . '">';
            $output .= '<div class="ufsc-payment-upcoming-info">';
            $output .= '<h4>' . esc_html($payment['description']) . '</h4>';
            $output .= '<p>Montant: <strong>' . number_format($payment['amount'], 2) . ' €</strong></p>';
            $output .= '<p>Échéance: <strong>' . esc_html(date('d/m/Y', strtotime($payment['due_date']))) . '</strong>';
            if ($days_until_due <= 30) {
                $output .= ' (' . $days_until_due . ' jour' . ($days_until_due > 1 ? 's' : '') . ' restant' . ($days_until_due > 1 ? 's' : '') . ')';
            }
            $output .= '</p>';
            $output .= '</div>';
            $output .= '<div class="ufsc-payment-upcoming-actions">';
            if (isset($payment['payment_url'])) {
                $output .= '<a href="' . esc_url($payment['payment_url']) . '" class="ufsc-btn ufsc-btn-primary">Payer maintenant</a>';
            }
            $output .= '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render invoice management section
 *
 * @param object $club Club object
 * @return string HTML output for invoice management
 */
function ufsc_render_invoice_management($club)
{
    $output = '<div class="ufsc-card" id="payment-methods">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-media-document"></i> Gestion des factures et paiements';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    // Payment methods information
    $output .= '<div class="ufsc-payment-methods">';
    $output .= '<h4>Modes de paiement acceptés</h4>';
    $output .= '<div class="ufsc-payment-methods-grid">';

    $payment_methods = [
        'bank_transfer' => [
            'name' => 'Virement bancaire',
            'icon' => 'bank',
            'description' => 'Paiement par virement SEPA',
            'available' => true
        ],
        'check' => [
            'name' => 'Chèque',
            'icon' => 'money',
            'description' => 'Chèque à l\'ordre de l\'UFSC',
            'available' => true
        ],
        'online' => [
            'name' => 'Paiement en ligne',
            'icon' => 'smartphone',
            'description' => 'Carte bancaire sécurisée',
            'available' => true
        ]
    ];

    foreach ($payment_methods as $key => $method) {
        $output .= '<div class="ufsc-payment-method">';
        $output .= '<div class="ufsc-payment-method-icon">';
        $output .= '<i class="dashicons dashicons-' . $method['icon'] . '"></i>';
        $output .= '</div>';
        $output .= '<div class="ufsc-payment-method-info">';
        $output .= '<h5>' . esc_html($method['name']) . '</h5>';
        $output .= '<p>' . esc_html($method['description']) . '</p>';
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    // Banking information for transfers - Updated with correct UFSC details
    $output .= '<div class="ufsc-banking-info">';
    $output .= '<h4>Coordonnées bancaires pour virement</h4>';
    $output .= '<div class="ufsc-info-box ufsc-info-box-important ufsc-mb-15">';
    $output .= '<h5>⚠️ Nouvelles coordonnées bancaires UFSC</h5>';
    $output .= '<p>Merci d\'utiliser exclusivement ces coordonnées pour vos virements à l\'UFSC.</p>';
    $output .= '</div>';
    $output .= '<div class="ufsc-bank-details">';
    $output .= '<div class="ufsc-bank-item">';
    $output .= '<label>Titulaire :</label>';
    $output .= '<span><strong>UNION FRANÇAISE DES SPORTS DE COMBAT</strong></span>';
    $output .= '</div>';
    $output .= '<div class="ufsc-bank-item">';
    $output .= '<label>IBAN :</label>';
    $output .= '<span class="ufsc-iban"><strong>FR76 1027 8090 6100 0204 6460 166</strong></span>';
    $output .= '</div>';
    $output .= '<div class="ufsc-bank-item">';
    $output .= '<label>BIC :</label>';
    $output .= '<span><strong>CMCIFR2A</strong></span>';
    $output .= '</div>';
    $output .= '<div class="ufsc-bank-item">';
    $output .= '<label>Banque :</label>';
    $output .= '<span>Crédit Mutuel – CCM Saint-Rémy-de-Provence (EUR)</span>';
    $output .= '</div>';
    $output .= '<div class="ufsc-bank-item ufsc-reference-required">';
    $output .= '<label>Référence/Libellé du virement <span class="ufsc-required">(OBLIGATOIRE)</span> :</label>';
    $output .= '<span class="ufsc-reference"><strong>' . strtoupper(esc_html($club->nom)) . ' – [motif du paiement]</strong></span>';
    $output .= '<div class="ufsc-form-hint">Remplacez [motif du paiement] par : affiliation, licences, etc.</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    // Request invoice section
    $output .= '<div class="ufsc-invoice-request">';
    $output .= '<h4>Demander une facture</h4>';
    $output .= '<p>Besoin d\'une facture spécifique ou d\'un duplicata ?</p>';
    $output .= '<form method="post" class="ufsc-invoice-form">';
    $output .= wp_nonce_field('ufsc_request_invoice', 'ufsc_invoice_nonce', true, false);
    $output .= '<input type="hidden" name="club_id" value="' . esc_attr($club->id) . '">';

    $output .= '<div class="ufsc-form-row">';
    $output .= '<label for="invoice_type">Type de facture :</label>';
    $output .= '<div>';
    $output .= '<select name="invoice_type" id="invoice_type" required>';
    $output .= '<option value="">-- Sélectionner --</option>';
    $output .= '<option value="affiliation">Facture d\'affiliation</option>';
    $output .= '<option value="cotisation">Facture de cotisation annuelle</option>';
    $output .= '<option value="licences">Facture de licences</option>';
    $output .= '<option value="duplicate">Duplicata d\'une facture existante</option>';
    $output .= '</select>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="ufsc-form-row">';
    $output .= '<label for="invoice_details">Détails de la demande :</label>';
    $output .= '<div>';
    $output .= '<textarea name="invoice_details" id="invoice_details" rows="3" placeholder="Précisez votre demande..."></textarea>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="ufsc-form-row">';
    $output .= '<div></div>';
    $output .= '<div>';
    $output .= '<button type="submit" name="ufsc_submit_invoice_request" class="ufsc-btn ufsc-btn-outline">';
    $output .= '<i class="dashicons dashicons-email"></i> Demander la facture';
    $output .= '</button>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '</form>';
    $output .= '</div>';

    // Handle invoice request
    if (isset($_POST['ufsc_submit_invoice_request'])) {
        $output .= ufsc_handle_invoice_request($club);
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Get club payment status
 *
 * @param object $club Club object
 * @return array Payment status information
 */
function ufsc_get_club_payment_status($club)
{
    // This would typically query payment records from database
    // For now, return sample data based on club status
    
    $current_year = date('Y');
    // CORRECTION: Use standardized status checking
    $is_affiliated = ufsc_is_club_active($club);
    
    return [
        'affiliation_paid' => $is_affiliated,
        'affiliation_amount' => '150,00 €',
        'affiliation_due_date' => $is_affiliated ? null : date('d/m/Y', strtotime('+30 days')),
        
        'annual_paid' => $is_affiliated,
        'annual_amount' => '100,00 €',
        'annual_due_date' => $is_affiliated ? null : '31/12/' . $current_year,
        
        'licences_paid' => true,
        'licences_amount' => '0,00 €',
        'licences_due_date' => null,
        
        'outstanding_balance' => $is_affiliated ? 0 : 250.00
    ];
}

/**
 * Get club payment history
 *
 * @param int $club_id Club ID
 * @return array Payment history
 */
function ufsc_get_club_payment_history($club_id)
{
    // This would typically query payment records from database
    // For now, return sample data
    
    return [
        [
            'date' => '2024-01-15',
            'description' => 'Cotisation d\'affiliation ' . date('Y'),
            'amount' => 150.00,
            'status' => 'completed',
            'invoice_number' => 'INV-2024-001',
            'receipt_url' => '#',
            'invoice_url' => '#'
        ],
        [
            'date' => '2024-02-01',
            'description' => 'Frais de licence (5 licences)',
            'amount' => 75.00,
            'status' => 'completed',
            'invoice_number' => 'INV-2024-025',
            'receipt_url' => '#',
            'invoice_url' => '#'
        ]
    ];
}

/**
 * Get upcoming payments for club
 *
 * @param object $club Club object
 * @return array Upcoming payments
 */
function ufsc_get_upcoming_payments($club)
{
    // This would typically query scheduled payments
    // For now, return sample data based on club status
    
    $upcoming = [];
    
    // CORRECTION: Use standardized status checking
    if (!ufsc_is_club_active($club)) {
        $upcoming[] = [
            'description' => 'Cotisation d\'affiliation ' . date('Y'),
            'amount' => 150.00,
            'due_date' => date('Y-m-d', strtotime('+15 days')),
            'payment_url' => '#'
        ];
    }
    
    // Annual renewal if near end of year
    if (date('n') >= 11) {
        $upcoming[] = [
            'description' => 'Renouvellement cotisation ' . (date('Y') + 1),
            'amount' => 100.00,
            'due_date' => (date('Y') + 1) . '-01-31',
            'payment_url' => '#'
        ];
    }
    
    return $upcoming;
}

/**
 * Get payment status CSS class
 *
 * @param string $status Payment status
 * @return string CSS class
 */
function ufsc_get_payment_status_class($status)
{
    $classes = [
        'completed' => 'ufsc-badge-active',
        'pending' => 'ufsc-badge-pending',
        'failed' => 'ufsc-badge-error',
        'refunded' => 'ufsc-badge-inactive'
    ];

    return $classes[$status] ?? 'ufsc-badge-inactive';
}

/**
 * Get payment status text
 *
 * @param string $status Payment status
 * @return string Status text
 */
function ufsc_get_payment_status_text($status)
{
    $texts = [
        'completed' => 'Payé',
        'pending' => 'En attente',
        'failed' => 'Échec',
        'refunded' => 'Remboursé'
    ];

    return $texts[$status] ?? 'Inconnu';
}

/**
 * Get days until payment due date
 *
 * @param string $due_date Due date
 * @return int Days until due
 */
function ufsc_get_days_until_due($due_date)
{
    $today = new DateTime();
    $due = new DateTime($due_date);
    return $today->diff($due)->days;
}

/**
 * Handle invoice request
 *
 * @param object $club Club object
 * @return string Success or error message HTML
 */
function ufsc_handle_invoice_request($club)
{
    // Security checks
    if (!wp_verify_nonce($_POST['ufsc_invoice_nonce'], 'ufsc_request_invoice')) {
        return '<div class="ufsc-alert ufsc-alert-error">Erreur de sécurité. Veuillez réessayer.</div>';
    }

    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé.</div>';
    }

    $invoice_type = sanitize_text_field($_POST['invoice_type']);
    $details = sanitize_textarea_field($_POST['invoice_details']);

    if (empty($invoice_type)) {
        return '<div class="ufsc-alert ufsc-alert-error">Veuillez sélectionner un type de facture.</div>';
    }

    // Send email to administration
    $to = 'facturation@ufsc-france.org';
    $subject = 'Demande de facture - Club ' . $club->nom;
    
    $message = "Nouvelle demande de facture\n\n";
    $message .= "Club: " . $club->nom . "\n";
    $message .= "N° d'affiliation: " . ($club->num_affiliation ?? 'En cours') . "\n";
    $message .= "Email: " . $club->email . "\n\n";
    $message .= "Type de facture: " . $invoice_type . "\n";
    if (!empty($details)) {
        $message .= "Détails: " . $details . "\n";
    }
    $message .= "\nDemande soumise le: " . current_time('d/m/Y à H:i') . "\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $club->email
    ];

    if (wp_mail($to, $subject, $message, $headers)) {
        return '<div class="ufsc-alert ufsc-alert-success">Votre demande de facture a été envoyée. Vous recevrez la facture par email sous 3-5 jours ouvrés.</div>';
    } else {
        return '<div class="ufsc-alert ufsc-alert-error">Erreur lors de l\'envoi de la demande. Veuillez réessayer ou nous contacter directement.</div>';
    }
}