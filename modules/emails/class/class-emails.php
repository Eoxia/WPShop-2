<?php
/**
 * Classe principale des emails
 *
 * @package   WPshop\Classes
 *
 * @author    Eoxia <dev@eoxia.com>
 * @copyright (c) 2011-2020 Eoxia
 *
 * @license   AGPLv3 <https://spdx.org/licenses/AGPL-3.0-or-later.html>
 *
 * @since     2.0.0
 */

namespace wpshop;

defined( 'ABSPATH' ) || exit;

/**
 * Emails Class.
 */
class Emails extends \eoxia\Singleton_Util {

	/**
	 * Tableau contenant les mails par défaut.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $emails;

	/**
	 * Le chemin vers le répertoire uploads/wpshop/logs.
	 *
	 * @var string
	 */
	public $log_emails_directory;

	/**
	 * Constructeur.
	 *
	 * @since 2.0.0
	 */
	protected function construct() {
		$this->emails['customer_new_account'] = array(
			'title'             => __( 'New account', 'wpshop' ),
			'content' => __( 'Welcome <br> This email confirms that your account has been created. <br> Thank you for your trust and see you soon on our shop.', 'wpshop' ),
		);

		$this->emails['customer_current_order'] = array(
			'title'             => __( 'Pending order', 'wpshop' ),
			'content' => __( 'Hello <br> We have just recorded your order, thank you to send us your payment. <br> We thank you for your confidence and see you soon on our shop.', 'wpshop' ),
		);

		$this->emails['customer_paid_order'] = array(
			'title'             => __( 'New order', 'wpshop' ),
			'content' => __( 'Hello <br> This email confirms that your payment for your recent order has just been validated. <br> See you soon on our shop.', 'wpshop' ),
		);
/*
		$this->emails['customer_completed_order'] = array(
			'title'             => __( 'Completed order', 'wpshop' ),
		);

		$this->emails['customer_delivered_order'] = array(
			'title'             => __( 'Delivered order', 'wpshop' ),
		);
*/
		$this->emails['customer_invoice'] = array(
			'title'             => __( 'Send invoice', 'wpshop' ),
			'content' => __( 'Hello <br> You can access your invoices by logging in to your account.', 'wpshop' ),
		);


		$wp_upload_dir              = wp_upload_dir();

		//@todo Permettre le réglages des dossiers et du nom de fichier pour les logs
		//@todo afficher la taille du fichier
		$this->log_emails_directory = $wp_upload_dir['basedir'] . '/wpshop/logs/';

		wp_mkdir_p( $this->log_emails_directory );
		//@todo erreur création répertoire ?
	}

	/**
	 * Envoie un mail.
	 *
	 * @since 2.0.0
	 *
	 * @use wp_mail.
	 *
	 * @param  string $to   Mail du destinataire.
	 * @param  string $type Le template à utiliser.
	 * @param  array  $data Les données utilisées par le template.
	 */
	public function send_mail( $to, $type, $data = array() ) {
		$shop_options = get_option( 'wps_dolibarr', Settings::g()->default_settings );

		if ( empty( $shop_options['shop_email'] ) ) {
			//@todo récupération erreurs
			return;
		}

		$to          = empty( $to ) ? $shop_options['shop_email'] : $to;
		$blog_name   = get_bloginfo();
		$mail        = Emails::g()->emails[ $type ];
		$attachments = null;

		if ( ! empty( $data['attachments'] ) ) {
			$attachments = $data['attachments'];
		}

		$content = $mail['content'];
		$user = wp_get_current_user();
		$data_email = array(
			'title' => $mail['title'],
			'user_id' => $user->ID,
			'user_email' => $user->user_email,
		);

		if ( $user->ID == 0 ) {
			$data_email['user_email'] = $to;
			$user = get_user_by('email', $to );
			$data_email['user_id'] = $user->data->ID;
		}

		$headers     = array();
		$headers[]   = 'From: ' . $blog_name . ' <' . $shop_options['shop_email'] . '>';
		$headers[]   = 'Content-Type: text/html; charset=UTF-8';
		wp_mail( $to, $mail['title'], $content, $headers, $attachments );

		$this->log_emails( $data_email );

	}

	/**
	 * Création du log des mails envoyés
	 *
	 * @since 2.0.0
	 *
	 * @param  string $data_email Les données du mails envoyé.
	 */
	public function log_emails( $data_email ) {

		$filename = 'log_emails.txt';
		$filepath = $this->log_emails_directory . $filename;
		$current_time = current_time( 'Y-m-d|H:i:s' );

		$log_email_file = fopen( $filepath,'a' );
		$data = $current_time . '|' . $data_email['user_id']. '|' . $data_email['user_email'] . '|' . $data_email['title'] . '|' . "\n";
		fwrite( $log_email_file, $data );
		fclose( $log_email_file );
	}
}

Emails::g();
