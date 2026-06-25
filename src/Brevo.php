<?php
/**
 * Brevo integration.
 *
 * @package Akwaaba\WordPress\SharePost
 */

namespace Akwaaba\WordPress\SharePost;

/**
 * Brevo class.
 */
class Brevo {
	/**
	 * Endpoint URL.
	 *
	 * @var string
	 */
	private static $endpoint_url = 'https://api.brevo.com/v3';

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'admin_init', [ $this, 'register_settings' ] );

		\add_action( 'category_edit_form_fields', [ $this, 'category_edit_fields' ], 10, 2 );
		\add_action( 'edited_category', [ $this, 'category_fields_save' ], 10, 2 );

		$api_key = get_option( 'akwaaba_share_post_brevo_api_key' );

		if ( '' !== $api_key ) {
			\add_action( 'transition_post_status', [ $this, 'maybe_send_campaign' ], 10, 3 );
		}

      	\add_action( 'init', [ $this, 'register_blocks' ] );
	}

  	public function register_blocks() {
      register_block_type(
        'akwaaba/sibwp-form',
        [
          'title' => 'Brevo formulier reisverslagen',
          'render_callback' => function () {
            if ( ! shortcode_exists( 'sibwp_form' ) ) {
              return '<p>De [sibwp_form] shortcode is niet beschikbaar.</p>';
            }

            return do_shortcode( '[sibwp_form id="5"]' );
          },
        ]
      );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'akwaaba_share_post_settings', 'brevo_api_key' );

		add_settings_section(
			'akwaaba_share_post_brevo',
			__( 'Brevo', 'akwaaba-share-post' ),
			[ $this, 'settings_section_brevo' ],
			'akwaaba_share_post_settings'
		);

		add_settings_field(
			'brevo_api_key',
			__( 'Brevo API Key', 'akwaaba-share-post' ),
			[ $this, 'api_key_field_callback' ],
			'akwaaba_share_post_settings',
			'akwaaba_share_post_brevo'
		);
	}

	public function settings_section_brevo() {
	}

	/**
	 * Brevo API key field.
	 *
	 * @return void
	 */
	public function api_key_field_callback() {
		$api_key = get_option( 'akwaaba_share_post_brevo_api_key' );

		echo '<input type="text" id="brevo_api_key" name="akwaaba_share_post_brevo_api_key" value="' . esc_attr( $api_key ) . '" />';
	}

	/**
	 * Create and send campaign.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function maybe_send_campaign( $new_status, $old_status, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'post' !== $post->post_type ) {
			return;
		}

		if ( 'publish' !== $new_status ) {
			return;
		}

		if ( 'publish' === $old_status ) {
			return;
		}

		$post_id = $post->ID;

		$brevo_campaign_id = get_post_meta( $post_id, '_akwaaba_share_post_brevo_campaign_id', true );

		if ( ! empty( $brevo_campaign_id ) ) {
			return;
		}

		$categories = get_the_category( $post_id );

		foreach ( $categories as $category ) {
			$enabled = get_term_meta( $category->term_id, 'akwaaba_post_share_brevo_enabled', true );

			if ( 'yes' !== $enabled ) {
				continue;
			}

			// Data for POST request to https://api.brevo.com/v3/emailCampaigns
			$title = \get_the_title( $post_id );

			$recipients = [
				'listIds'    => \wp_parse_id_list( \get_term_meta( $category->term_id, 'akwaaba_post_share_brevo_recipients_list_ids', true ) ),
				'segmentIds' => \wp_parse_id_list( \get_term_meta( $category->term_id, 'akwaaba_post_share_brevo_recipients_segment_ids', true ) ),
			];

			$recipients = array_filter( $recipients );

			$params = [
				'post_id'            => $post_id,
				'post_title'         => $title,
				'post_excerpt'       => \get_the_excerpt( $post_id ),
				'permalink'          => \get_permalink( $post_id ),
				'post_thumbnail_url' => \get_the_post_thumbnail_url( $post_id ),
			];

			$html = \strtr(
				'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="format-detection" content="telephone=no"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Nieuwe reisblog</title><style type="text/css" emogrify="no">#outlook a { padding:0; } .ExternalClass { width:100%; } .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; } table td { border-collapse: collapse; mso-line-height-rule: exactly; } .editable.image { font-size: 0 !important; line-height: 0 !important; } .nl2go_preheader { display: none !important; mso-hide:all !important; mso-line-height-rule: exactly; visibility: hidden !important; line-height: 0px !important; font-size: 0px !important; } body { width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0; } img { outline:none; text-decoration:none; -ms-interpolation-mode: bicubic; } a img { border:none; } table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; } th { font-weight: normal; text-align: left; } *[class="gmail-fix"] { display: none !important; } </style><style type="text/css" emogrify="no"> @media (max-width: 600px) { .gmx-killpill { content: \' \03D1\';} } </style><style type="text/css" emogrify="no">@media (max-width: 600px) { .gmx-killpill { content: \' \03D1\';} .r0-o { border-style: solid !important; margin: 0 auto 0 auto !important; width: 100% !important } .r1-i { background-color: transparent !important } .r2-c { box-sizing: border-box !important; text-align: center !important; valign: top !important; width: 320px !important } .r3-o { border-style: solid !important; margin: 0 auto 0 auto !important; width: 320px !important } .r4-i { padding-bottom: 5px !important; padding-top: 5px !important } .r5-c { box-sizing: border-box !important; display: block !important; valign: top !important; width: 100% !important } .r6-o { border-style: solid !important; width: 100% !important } .r7-i { padding-left: 0px !important; padding-right: 0px !important } .r8-c { box-sizing: border-box !important; text-align: center !important; width: 100% !important } .r9-i { padding-left: 10px !important; padding-right: 10px !important; text-align: right !important } .r10-i { background-color: #ffffff !important } .r11-c { box-sizing: border-box !important; text-align: center !important; valign: top !important; width: 100% !important } .r12-i { padding-bottom: 20px !important; padding-left: 10px !important; padding-right: 10px !important; padding-top: 20px !important } .r13-c { box-sizing: border-box !important; text-align: left !important; valign: top !important; width: 100% !important } .r14-o { border-style: solid !important; margin: 0 auto 0 0 !important; width: 100% !important } .r15-i { padding-top: 15px !important; text-align: center !important } .r16-i { padding-bottom: 15px !important; padding-top: 15px !important } .r17-i { padding-top: 15px !important; text-align: left !important } .r18-i { padding-bottom: 15px !important; padding-top: 15px !important; text-align: left !important } .r19-o { border-style: solid !important; margin: 0 auto 0 auto !important; margin-bottom: 15px !important; margin-top: 15px !important; width: 100% !important } .r20-i { text-align: center !important } .r21-r { background-color: #ff5b03 !important; border-color: #5b1150 !important; border-radius: 8px !important; border-width: 0px !important; box-sizing: border-box; height: initial !important; padding-bottom: 12px !important; padding-left: 5px !important; padding-right: 5px !important; padding-top: 12px !important; text-align: center !important; width: 100% !important } .r22-i { background-color: #ffffff !important; padding-bottom: 20px !important; padding-left: 15px !important; padding-right: 15px !important; padding-top: 20px !important } .r23-o { border-style: solid !important; margin: 0 auto 0 auto !important; margin-bottom: 20px !important; width: 100% !important } .r24-i { background-color: #ecf4f8 !important; padding-bottom: 20px !important; padding-left: 15px !important; padding-right: 15px !important; padding-top: 20px !important } .r25-i { color: #3b3f44 !important; padding-bottom: 0px !important; padding-top: 15px !important; text-align: center !important } .r26-i { color: #3b3f44 !important; padding-bottom: 0px !important; padding-left: 0px !important; padding-right: 0px !important; padding-top: 0px !important; text-align: center !important } .r27-i { color: #3b3f44 !important; padding-bottom: 0px !important; padding-top: 0px !important; text-align: center !important } body { -webkit-text-size-adjust: none } .nl2go-responsive-hide { display: none } .nl2go-body-table { min-width: unset !important } .mobshow { height: auto !important; overflow: visible !important; max-height: unset !important; visibility: visible !important; border: none !important } .resp-table { display: inline-table !important } .magic-resp { display: table-cell !important } } </style><!--[if !mso]><!--><style type="text/css" emogrify="no">@import url("https://fonts.googleapis.com/css2?family=Montserrat Alternates&family=Poppins"); </style><!--<![endif]--><style type="text/css">p, h1, h2, h3, h4, ol, ul { margin: 0; } a, a:link { color: #5b1150; text-decoration: underline } .nl2go-default-textstyle { color: #3b3f44; font-family: Poppins, arial; font-size: 16px; line-height: 1.5; word-break: break-word } .default-button { color: #ffffff; font-family: Poppins, arial; font-size: 16px; font-style: normal; font-weight: bold; line-height: 1.15; text-decoration: none; word-break: break-word } .default-heading1 { color: #1F2D3D; font-family: Montserrat Alternates, verdana; font-size: 36px; word-break: break-word } .default-heading2 { color: #1F2D3D; font-family: Montserrat Alternates, verdana; font-size: 32px; word-break: break-word } .default-heading3 { color: #1F2D3D; font-family: Montserrat Alternates, verdana; font-size: 24px; word-break: break-word } .default-heading4 { color: #1F2D3D; font-family: Montserrat Alternates, verdana; font-size: 18px; word-break: break-word } a[x-apple-data-detectors] { color: inherit !important; text-decoration: inherit !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; } .no-show-for-you { border: none; display: none; float: none; font-size: 0; height: 0; line-height: 0; max-height: 0; mso-hide: all; overflow: hidden; table-layout: fixed; visibility: hidden; width: 0; } a[href*="utm_source="] { display: none; }</style><!--[if mso]><xml> <o:OfficeDocumentSettings> <o:AllowPNG/> <o:PixelsPerInch>96</o:PixelsPerInch> </o:OfficeDocumentSettings> </xml><![endif]--><style type="text/css">a:link{color: #5b1150; text-decoration: underline;}</style></head><body bgcolor="#ffffff" text="#3b3f44" link="#5b1150" yahoo="fix" style="background-color: #ffffff;"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" class="nl2go-body-table" width="100%" style="background-color: #ffffff; width: 100%;"><tr><td> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" align="center" class="r0-o" style="table-layout: fixed; width: 100%;"><tr><td valign="top" class="r1-i" style="background-color: transparent;"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="600" align="center" class="r3-o" style="table-layout: fixed;"><tr><td class="r4-i" style="padding-bottom: 5px; padding-top: 5px;"> <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><th width="100%" valign="top" class="r5-c" style="font-weight: normal;"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r6-o" style="table-layout: fixed; width: 100%;"><tr><td valign="top" class="r7-i"> <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><td class="r8-c" align="center"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r0-o" style="table-layout: fixed; width: 100%;"><tr><td align="right" class="r9-i nl2go-default-textstyle" style="color: #3b3f44; font-family: Poppins,arial; font-size: 16px; word-break: break-word; line-height: 16px; padding-left: 30px; padding-right: 30px; text-align: right;"> <div><p style="margin: 0;"><a href="{{ mirror }}" style="color: #5b1150; text-decoration: underline;"><span style="color: #858588; font-family: arial,helvetica,sans-serif; font-size: 12px;"><u>Bekijk webversie</u></span></a></p></div> </td> </tr></table></td> </tr></table></td> </tr></table></th> </tr></table></td> </tr></table></td> </tr></table><table cellspacing="0" cellpadding="0" border="0" role="presentation" width="600" align="center" class="r3-o" style="table-layout: fixed; width: 600px;"><tr><td valign="top" class="r10-i" style="background-color: #ffffff;"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" align="center" class="r0-o" style="table-layout: fixed; width: 100%;"><tr><td class="r12-i" style="padding-bottom: 20px; padding-top: 20px;"> <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><th width="100%" valign="top" class="r5-c" style="font-weight: normal;"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r6-o" style="table-layout: fixed; width: 100%;"><tr><td valign="top" class="r7-i"> <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><td class="r13-c" align="left"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r14-o" style="table-layout: fixed; width: 100%;"><tr><td align="center" valign="top" class="r15-i nl2go-default-textstyle" style="color: #3b3f44; font-family: Poppins,arial; font-size: 16px; line-height: 1.5; word-break: break-word; padding-top: 15px; text-align: center;"> <div><h1 class="default-heading1" style="margin: 0; color: #1f2d3d; font-family: Montserrat Alternates,verdana; font-size: 36px; word-break: break-word;">Een nieuwe reisblog! 📝</h1><p style="margin: 0;">Reis met ons mee, lees snel verder.</p><p style="margin: 0;"> </p></div> </td> </tr></table></td> </tr><tr><td class="r11-c" align="center"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="600" class="r0-o" style="table-layout: fixed; width: 600px;"><tr><td class="r16-i" style="padding-bottom: 15px; padding-top: 15px;"> <a href="{{ params.permalink }}" target="_blank" style="color: #5b1150; text-decoration: underline;"> <img src="{{ params.post_thumbnail_url }}" width="600" alt="{{ params.post_title }}" title="{{ params.post_title }}" border="0" style="display: block; width: 100%;"></a> </td> </tr></table></td> </tr><tr><td class="r13-c" align="left"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r14-o" style="table-layout: fixed; width: 100%;"><tr><td align="left" valign="top" class="r17-i nl2go-default-textstyle" style="color: #3b3f44; font-family: Poppins,arial; font-size: 16px; line-height: 1.5; word-break: break-word; padding-top: 15px; text-align: left;"> <div><h3 class="default-heading3" style="margin: 0; color: #1f2d3d; font-family: Montserrat Alternates,verdana; font-size: 24px; word-break: break-word;">{{ params.post_title }}</h3><p style="margin: 0;"> </p></div> </td> </tr></table></td> </tr><tr><td class="r13-c" align="left"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r14-o" style="table-layout: fixed; width: 100%;"><tr><td align="left" valign="top" class="r18-i nl2go-default-textstyle" style="color: #3b3f44; font-family: Poppins,arial; font-size: 16px; line-height: 1.5; word-break: break-word; padding-bottom: 15px; padding-top: 15px; text-align: left;"> <div><p style="margin: 0;">{{ params.post_excerpt }}</p></div> </td> </tr></table></td> </tr><tr><td class="r11-c" align="center"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="300" class="r19-o" style="table-layout: fixed; width: 300px;"><tr class="nl2go-responsive-hide"><td height="15" style="font-size: 15px; line-height: 15px;">­</td> </tr><tr><td height="18" align="center" valign="top" class="r20-i nl2go-default-textstyle" style="color: #3b3f44; font-family: Poppins,arial; font-size: 16px; line-height: 1.5; word-break: break-word;">  <!--[if mso]> <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ params.permalink }}" style="v-text-anchor:middle; height: 41px; width: 299px;" arcsize="20%" fillcolor="#ff5b03" strokecolor="#ff5b03" strokeweight="1px" data-btn="1"> <w:anchorlock> </w:anchorlock> <v:textbox inset="0,0,0,0"> <div style="display:none;"> <center class="default-button"><span>Lees verder</span></center> </div> </v:textbox> </v:roundrect> <![endif]-->  <!--[if !mso]><!-- --> <a href="{{ params.permalink }}" class="r21-r default-button" target="_blank" title=\'Lees "{{ params.post_title }}"\' data-btn="1" style="font-style: normal; font-weight: bold; line-height: 1.15; text-decoration: none; word-break: break-word; border-style: solid; word-wrap: break-word; display: block; -webkit-text-size-adjust: none; background-color: #ff5b03; border-color: #5b1150; border-radius: 8px; border-width: 0px; color: #ffffff; font-family: Poppins, arial; font-size: 16px; height: 18px; mso-hide: all; padding-bottom: 12px; padding-left: 5px; padding-right: 5px; padding-top: 12px; width: 290px;"> <span>Lees verder</span></a> <!--<![endif]--> </td> </tr><tr class="nl2go-responsive-hide"><td height="15" style="font-size: 15px; line-height: 15px;">­</td> </tr></table></td> </tr></table></td> </tr></table></th> </tr></table></td> </tr></table><table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" align="center" class="r0-o" style="table-layout: fixed; width: 100%;"><tr><td class="r22-i" style="background-color: #ffffff; padding-bottom: 20px; padding-top: 20px;"> <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><th width="100%" valign="top" class="r5-c" style="font-weight: normal;"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r6-o" style="table-layout: fixed; width: 100%;"><tr><td valign="top" class="r7-i" style="padding-left: 15px; padding-right: 15px;"> <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><td class="r11-c" align="center"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="200" class="r0-o" style="table-layout: fixed; width: 200px;"><tr><td style="font-size: 0px; line-height: 0px;"> <img src="https://img.mailinblue.com/6064674/images/content_library/original/65273a5a6008341a461fe474.png" width="200" border="0" style="display: block; width: 100%;"></td> </tr></table></td> </tr></table></td> </tr></table></th> </tr></table></td> </tr></table><table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" align="center" class="r23-o" style="table-layout: fixed; width: 100%;"><tr><td class="r24-i" style="background-color: #ecf4f8; padding-bottom: 20px; padding-top: 20px;"> <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><th width="100%" valign="top" class="r5-c" style="font-weight: normal;"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r6-o" style="table-layout: fixed; width: 100%;"><tr><td valign="top" class="r7-i" style="padding-left: 15px; padding-right: 15px;"> <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><td class="r13-c" align="left"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r14-o" style="table-layout: fixed; width: 100%;"><tr><td align="center" valign="top" class="r25-i nl2go-default-textstyle" style="font-family: Poppins,arial; word-break: break-word; color: #3b3f44; font-size: 18px; line-height: 1.5; padding-top: 15px; text-align: center;"> <div><p style="margin: 0; font-size: 14px;">Dit bericht is verstuurd naar {{ contact.EMAIL }}, omdat je je hebt aangemeld om op de hoogte te blijven van de reisverslagen voor deze reis. Ontvang je teveel e-mails van ons? Je kunt je voorkeuren wijzigen of <a href="{{ unsubscribe }}" target="_blank" style="color: #5b1150; text-decoration: underline;">uitschrijven.</a></p></div> </td> </tr></table></td> </tr><tr><td class="r8-c" align="center"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="570" class="r0-o" style="table-layout: fixed; width: 570px;"><tr><td height="30" class="r1-i" style="font-size: 30px; line-height: 30px; background-color: transparent;"> ­ </td> </tr></table></td> </tr><tr><td class="r13-c" align="left"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r14-o" style="table-layout: fixed; width: 100%;"><tr><td align="center" valign="top" class="r26-i nl2go-default-textstyle" style="font-family: Poppins,arial; word-break: break-word; color: #3b3f44; font-size: 18px; line-height: 1.5; text-align: center;"> <div><p style="margin: 0;"><span style="color: #0099c0;"><strong>Samenwerkvakanties</strong></span></p></div> </td> </tr></table></td> </tr><tr><td class="r11-c" align="center"> <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r0-o" style="table-layout: fixed; width: 100%;"><tr><td align="center" valign="top" class="r27-i nl2go-default-textstyle" style="font-family: Poppins,arial; word-break: break-word; color: #3b3f44; font-size: 18px; line-height: 1.5; text-align: center;"> <div><p style="margin: 0; font-size: 14px;"><span style="color: #0099c0;">Hooizolder 412, 9205 CW, Drachten</span></p></div> </td> </tr></table></td> </tr></table></td> </tr></table></th> </tr></table></td> </tr><tr class="nl2go-responsive-hide"><td height="20" style="font-size: 20px; line-height: 20px;">­</td> </tr></table></td> </tr></table></td> </tr></table></body></html>',
				\array_combine(
					\array_map(
						function ( $key ) {
							return sprintf( '{{ params.%s }}', $key );
						},
						\array_keys( $params )
					),
					$params
				)
			);

			$data = [
				'name'        => $title,
				'subject'     => sprintf( 'Nieuwe reisblog: "%s"', $title ),
				'recipients'  => $recipients,
				'sender'      => [
					'name'  => 'Samenwerkvakanties',
					'email' => 'info@samenwerkvakanties.nl',
				],
				// 'templateId' => (int) \get_term_meta( $category->term_id, 'akwaaba_post_share_brevo_template_id', true ),
				'params'      => $params,
				'htmlContent' => $html,
			];

			$campaign_id = $this->create_campaign( $data );

			if ( empty( $campaign_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_akwaaba_share_post_brevo_campaign_id', $campaign_id );

			$this->send_campaign( $campaign_id );
		}
	}

	/**
	 * Send request to endpoint URL.
	 *
	 * @return array|WP_Error
	 * @throws \Exception Throws exception if no API key is set.
	 */
	private function send_request( $endpoint, $data = null ) {
		$api_key = \get_option( 'akwaaba_share_post_brevo_api_key' );

		if ( '' === $api_key ) {
			throw new \Exception( 'No Brevo API key provided.' );
		}

		$args = [
			'headers' => [
				'api-key'      => $api_key,
				'content-type' => 'application/json',
			],
			'timeout' => -1,
		];

		if ( null !== $data ) {
			$args['body'] = json_encode( $data );
		}

		$response = \wp_remote_post( self::$endpoint_url . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			throw new \Exception(
				sprintf(
					'An error occurred with request to Brevo: %s',
					\esc_html( $response->get_error_message() )
				)
			);
		}

		return $response;
	}

	/**
	 * Create a campaign.
	 *
	 * @param array $data Campaign data.
	 * @return int Campaign ID.
	 * @throws \Exception Throws exception on error in the request.
	 */
	private function create_campaign( $data ) {
		if ( array_key_exists( 'post_id', $data['params'] ) ) {
			$post_id = $data['params']['post_id'];

			$brevo_campaign_id = get_post_meta( $post_id, '_akwaaba_share_post_brevo_campaign_id', true );

			if ( ! empty( $brevo_campaign_id ) ) {
				return;
			}
		}

		$response = $this->send_request( '/emailCampaigns', $data );

		$body = \wp_remote_retrieve_body( $response );

		$result = \json_decode( $body );

		// Return campaign ID.
		return $result->id;
	}

	/**
	 * Send a campaign.
	 *
	 * @param int $campaign_id The ID of a campaign.
	 * @return bool
	 */
	private function send_campaign( $campaign_id ) {
		return;
		$response = $this->send_request( '/emailCampaigns/' . $campaign_id . '/sendNow' );

		$response_code = \wp_remote_retrieve_response_code( $response );

		if ( 204 === $response_code ) {
			return true;
		}

		if ( in_array( $response_code, [ 400, 402, 404 ], true ) ) {
			$body = \wp_remote_retrieve_body( $response );

			$result = \json_decode( $body );

			throw new \Exception(
				sprintf(
					'An error occurred with request to Brevo: %s - %s',
					\esc_html( $result->code ),
					\esc_html( $result->message )
				)
			);
		}

		return false;
	}

	/**
	 * Get category settings fields.
	 *
	 * @return array
	 */
	private function get_category_fields() {
		return [
			[
				'type'        => 'checkbox',
				'label'       => \__( 'Share with Brevo', 'akwaaba-share-post' ),
				'description' => \__( 'Create a campaign with Brevo to share published posts with this category.', 'akwaaba-share-post' ),
				'meta_key'    => 'akwaaba_post_share_brevo_enabled',
			],
			[
				'type'        => 'text',
				'label'       => \__( 'Brevo template', 'akwaaba-share-post' ),
				'description' => \__( 'The ID of the template.', 'akwaaba-share-post' ),
				'meta_key'    => 'akwaaba_post_share_brevo_template_id',
			],
			[
				'type'        => 'text',
				'label'       => \__( 'Brevo list', 'akwaaba-share-post' ),
				'description' => \__( 'IDs of the recipient list(s).', 'akwaaba-share-post' ),
				'meta_key'    => 'akwaaba_post_share_brevo_recipients_list_ids',
			],
			[
				'type'        => 'text',
				'label'       => \__( 'Brevo segment', 'akwaaba-share-post' ),
				'description' => \__( 'IDs of the recipient segment(s).', 'akwaaba-share-post' ),
				'meta_key'    => 'akwaaba_post_share_brevo_recipients_segment_ids',
			],
		];
	}

	/**
	 * Category edit form fields.
	 *
	 * @param object $tag  Category object.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function category_edit_fields( $tag, $taxonomy ) {
		foreach ( $this->get_category_fields() as $field ) {
			$value = \get_term_meta( $tag->term_id, $field['meta_key'], true );

			?>

			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="<?php echo esc_attr( $field['meta_key'] ); ?>">
						<?php echo esc_html( $field['label'] ); ?>
					</label>
				</th>
				<td>
					<?php

					if ( 'checkbox' === $field['type'] ) {
						printf(
							'<input type="checkbox" id="%1$s" name="%1$s" value="yes" %2$s/> %3$s',
							\esc_attr( $field['meta_key'] ),
							\checked( $value, 'yes', false ),
							\esc_html( $field['description'] )
						);
					}

					if ( 'text' === $field['type'] ) {
						printf(
							'<input type="text" id="%1$s" name="%1$s" value="%2$s"/>',
							\esc_attr( $field['meta_key'] ),
							\esc_html( $value )
						);

						if ( array_key_exists( 'description', $field ) ) {
							printf(
								'<p class="description">%s</p>',
								\esc_html( $field['description'] )
							);
						}
					}

					?>
				</td>
			</tr>

			<?php
		}
	}

	/**
	 * Save category checkbox.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function category_fields_save( $term_id, $taxonomy ) {
		foreach ( $this->get_category_fields() as $field ) {
			if ( \array_key_exists( $field['meta_key'], $_POST ) ) {
				$value = sanitize_text_field( $_POST[ $field['meta_key'] ] );

				\update_term_meta( $term_id, $field['meta_key'], $value );

				continue;
			}

			if ( 'checkbox' === $field['type'] ) {
				\delete_term_meta( $term_id, 'akwaaba_post_share_brevo_enabled' );
			}
		}
	}
}
