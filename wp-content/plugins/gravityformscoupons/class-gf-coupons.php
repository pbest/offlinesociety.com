<?php

GFForms::include_feed_addon_framework();

if ( class_exists( 'GF_Field' ) ) {
	require_once( 'class-gf-field-coupon.php' );
}

class GFCoupons extends GFFeedAddOn {

	protected $_version = GF_COUPONS_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'gravityformscoupons';
	protected $_path = 'gravityformscoupons/coupons.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Coupons Add-On';
	protected $_short_title = 'Coupons';
	protected $_coupon_feed_id = '';

	// Members plugin integration
	protected $_capabilities = array(
		'gravityforms_coupons',
		'gravityforms_coupons_uninstall',
		'gravityforms_coupons_plugin_page',
	);

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_coupons';
	protected $_capabilities_form_settings = 'gravityforms_coupons';
	protected $_capabilities_uninstall = 'gravityforms_coupons_uninstall';
	protected $_capabilities_plugin_page = 'gravityforms_coupons_plugin_page';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFCoupons();
		}

		return self::$_instance;
	}

	public function init_admin() {

		parent::init_admin();
		
		if ( ! $this->is_gravityforms_supported( '1.9.4.16' ) ) {
			// update field button onlick to use StartAddCouponField
			add_filter( 'gform_add_field_buttons', array( $this, 'coupon_add_field' ) );
			add_action( 'gform_editor_js', array( $this, 'coupon_gform_editor_js' ) );
		}

		add_action( 'gform_editor_js_set_default_values', array( $this, 'set_defaults' ) );
		add_filter( 'gform_product_info', array( $this, 'add_discounts' ), 5, 3 );
		add_filter( $this->_slug . '_feed_actions', array( $this, 'set_action_links' ), 10, 3 );

	}

	public function init_frontend() {

		parent::init_frontend();
		add_filter( 'gform_product_info', array( $this, 'add_discounts' ), 5, 3 );

	}

	public function init_ajax() {

		parent::init_ajax();

		add_action( 'wp_ajax_gf_apply_coupon_code', array( $this, 'apply_coupon_code' ) );
		add_action( 'wp_ajax_nopriv_gf_apply_coupon_code', array( $this, 'apply_coupon_code' ) );
		add_filter( 'gform_product_info', array( $this, 'add_discounts' ), 5, 3 );
	}

	public function scripts() {

		$scripts = array(
			array(
				'handle'  => 'gform_coupon_script',
				'src'     => $this->get_base_url() . '/js/coupons.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery', 'gform_json' ),
				'enqueue' => array( array( 'field_types' => array( 'coupon' ) ) ),
				'strings' => array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
			),
			array(
				'handle'  => 'gform_form_admin',
				'enqueue' => array( array( 'admin_page' => array( 'plugin_page' ) ) )
			),
			array(
				'handle'  => 'gform_gravityforms',
				'enqueue' => array( array( 'admin_page' => array( 'plugin_page' ) ) )
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	public function styles() {

		$styles = array(
			array(
				'handle'  => 'gform_coupon_style',
				'src'     => $this->get_base_url() . '/css/gcoupons.css',
				'version' => $this->_version,
				'enqueue' => array( array( 'field_types' => array( 'coupon' ) ) )
			),
			array(
				'handle'  => 'gform_admin',
				'src'     => GFCommon::get_base_url() . '/css/admin.css',
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'plugin_page' ) ) )
			),
		);

		return array_merge( parent::styles(), $styles );
	}

	public function plugin_page() {
		$fid = $this->get_current_feed_id();

		$form_id = rgget( 'id' );
		if ( rgblank( $form_id ) ) {
			$form = array();
		} else {
			$form = GFFormsModel::get_form_meta( $form_id );
		}

		if ( ! empty( $fid ) || $fid == '0' ) {
			$this->coupon_edit_page( $fid, $form_id );
		} else {
			parent::feed_list_page();
		}

	}

	// ------- Plugin settings -------

	public function plugin_settings() {

		if ( $this->maybe_uninstall() ) {
			?>
			<div class="push-alert-gold" style="border-left: 1px solid #E6DB55; border-right: 1px solid #E6DB55;">
				<?php _e( sprintf( '%s has been successfully uninstalled. It can be re-activated from the %splugins page%s.', $this->_title, "<a href='plugins.php'>", '</a>' ), 'gravityformscoupons' ) ?>
			</div>
		<?php
		} else {
			//renders uninstall section
			$this->render_uninstall();
		}
	}

	public function feed_settings_fields() {
		return array(
			array(
				'title'       => __( 'Applies to Which Form?', 'gravityformscoupons' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'gravityForm',
						'label'    => __( 'Gravity Form', 'gravityformscoupons' ),
						'type'     => 'select',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'choices'  => $this->get_gravity_forms(),
						'tooltip'  => '<h6>' . __( 'Gravity Form', 'gravityformscoupons' ) . '</h6>' . __( 'Select the Gravity Form you would like to integrate with Coupons.', 'gravityformscoupons' )
					),
				)
			),
			array(
				'title'       => __( 'Coupon Basics', 'gravityformscoupons' ),
				'description' => '',
				'dependency'  => 'gravityForm',
				'fields'      => array(
					array(
						'name'     => 'couponName',
						'label'    => __( 'Coupon Name', 'gravityformscoupons' ),
						'type'     => 'text',
						'required' => true,
						'tooltip'  => '<h6>' . __( 'Coupon Name', 'gravityformscoupons' ) . '</h6>' . __( 'Enter coupon name.', 'gravityformscoupons' ),
					),
					array(
						'name'                => 'couponCode',
						'label'               => __( 'Coupon Code', 'gravityformscoupons' ),
						'type'                => 'text',
						'required'            => true,
						'validation_callback' => array( $this, 'check_if_duplicate_coupon_code' ),
						'tooltip'             => '<h6>' . __( 'Coupon Code', 'gravityformscoupons' ) . '</h6>' . __( 'Enter the value users should enter to apply this coupon to the form total.', 'gravityformscoupons' )
					),
					array(
						'name'                => 'couponAmountType',
						'label'               => __( 'Coupon Amount', 'gravityformscoupons' ),
						'type'                => 'coupon_amount_type',
						'required'            => true,
						'validation_callback' => array( $this, 'validate_coupon_amount' ),
						'tooltip'             => '<h6>' . __( 'Coupon Amount', 'gravityformscoupons' ) . '</h6>' . __( 'Enter the amount to be deducted from the form total.', 'gravityformscoupons' )
					),
				)
			),
			array(
				'title'       => __( 'Coupon Options', 'gravityformscoupons' ),
				'description' => '',
				'dependency'  => 'gravityForm',
				'fields'      => array(
					array(
						'name'    => 'startDate',
						'label'   => __( 'Start Date', 'gravityformscoupons' ),
						'type'    => 'text',
						'tooltip' => '<h6>' . __( 'Start Date', 'gravityformscoupons' ) . '</h6>' . __( 'Enter the date when the coupon should start.', 'gravityformscoupons' ),
						'class'   => 'datepicker',
					),
					array(
						'name'    => 'endDate',
						'label'   => __( 'End Date', 'gravityformscoupons' ),
						'type'    => 'text',
						'tooltip' => '<h6>' . __( 'End Date', 'gravityformscoupons' ) . '</h6>' . __( 'Enter the date when the coupon should expire.', 'gravityformscoupons' ),
						'class'   => 'datepicker',
					),
					array(
						'name'    => 'usageLimit',
						'label'   => __( 'Usage Limit', 'gravityformscoupons' ),
						'type'    => 'text',
						'tooltip' => '<h6>' . __( 'Usage Limit', 'gravityformscoupons' ) . '</h6>' . __( 'Enter the number of times coupon code can be used.', 'gravityformscoupons' )
					),
					array(
						'name'    => 'isStackable',
						'label'   => __( 'Is Stackable', 'gravityformscoupons' ),
						'type'    => 'checkbox',
						'tooltip' => '<h6>' . __( 'Is Stackable', 'gravityformscoupons' ) . '</h6>' . __( 'When the "Is Stackable" option is selected, this coupon code will be allowed to be used in conjunction with another coupon code.', 'gravityformscoupons' ),
						'choices' => array(
							array(
								'label' => __( 'Is Stackable', 'gravityformscoupons' ),
								'name'  => 'isStackable',
							),
						)
					),
					array(
						'name'  => 'usageCount',
						'label' => __( 'Usage Count', 'gravityformscoupons' ),
						'type'  => 'hidden',
					),
				)
			),
		);
	}

	public function get_gravity_forms() {

		$forms = RGFormsModel::get_forms();

		$forms_dropdown = array(
			array( 'label' => __( 'Select a Form', 'gravityformscoupons' ), 'value' => '' ),
			array( 'label' => __( 'Any Form', 'gravityformscoupons' ), 'value' => '0' ),
		);

		foreach ( $forms as $form ) {
			$forms_dropdown[] = array(
				'label' => $form->title,
				'value' => $form->id,
			);
		}

		return $forms_dropdown;
	}

	public function settings_coupon_amount_type( $field, $echo = true ) {

		require_once( GFCommon::get_base_path() . '/currency.php' );
		$currency        = RGCurrency::get_currency( GFCommon::get_currency() );
		$currency_symbol = ! empty ( $currency['symbol_left'] ) ? $currency['symbol_left'] : $currency['symbol_right'];

		wp_enqueue_script( array( 'jquery-ui-datepicker' ) );

		$styles = '<style type="text/css">
						td img.ui-datepicker-trigger {
						position: relative;
						top: 4px;
						}
					</style>';

		$js_script = '<script type="text/javascript">
							var currency_config = ' . json_encode( RGCurrency::get_currency( GFCommon::get_currency() ) ) . ';
							var form = Array();
								jQuery(document).on(\'change\', \'.gf_format_money\', function(){
									var cur = new Currency(currency_config)
									jQuery(this).val(cur.toMoney(jQuery(this).val()));
								});
								jQuery(document).on(\'change\', \'.gf_format_percentage\', function(event){
									var cur = new Currency(currency_config)
									var value = cur.toNumber(jQuery(this).val()) ? cur.toNumber(jQuery(this).val()) + \'%\' : \'\';
									jQuery(this).val( value );
								});

							function SetCouponType(elem) {
								var type = elem.val();
								var formatClass = type == \'flat\' ? \'gf_format_money\' : \'gf_format_percentage\';
								jQuery(\'#couponAmount\').removeClass(\'gf_format_money gf_format_percentage\').addClass(formatClass).trigger(\'change\');
								var placeholderText = type == \'flat\' ? \'' . html_entity_decode( GFCommon::to_money( 1 ) ) . '\' : \'1%\';
								jQuery(\'#couponAmount\').attr("placeholder",placeholderText);
							}

							jQuery(document).ready(function($){
								//set placeholder text for initial load
								var type = jQuery(\'#couponAmountType\').val();
								var placeholderText = type == \'flat\' ? \'' . html_entity_decode( GFCommon::to_money( 1 ) ) . '\' : \'1%\';
								jQuery(\'#couponAmount\').attr("placeholder",placeholderText);

								//format initial coupon amount value when there is one and it is currency
								var currency_config = ' . json_encode( RGCurrency::get_currency( GFCommon::get_currency() ) ) . ';
								var cur = new Currency(currency_config);
								couponAmount = jQuery(\'#couponAmount\').val();
								if ( couponAmount ){
									if (type == \'flat\'){
										couponAmount = cur.toMoney(couponAmount);
									}
									else{
										couponAmount = cur.toNumber(couponAmount) + \'%\';
									}
									jQuery(\'#couponAmount\').val(couponAmount);
								}

								jQuery(\'.datepicker\').each(
									function (){
										var image = "' . $this->get_base_url() . '/images/calendar.png";
										jQuery(this).datepicker({showOn: "both", buttonImage: image, buttonImageOnly: true, dateFormat: "mm/dd/yy" });
									}
								);

							});

						</script>';

		$field['type']     = 'select';
		$field['choices']  = array(
			array(
				'label' => __( 'Flat', 'gravityformscoupons' ) . '(' . $currency_symbol . ')',
				'name'  => 'flat',
				'value' => 'flat'
			),
			array(
				'label' => __( 'Percentage(%)', 'gravityformscoupons' ),
				'name'  => 'percentage',
				'value' => 'percentage'
			),
		);
		$field['onchange'] = 'SetCouponType(jQuery(this))';
		$html              = $this->settings_select( $field, false );

		$field2             = array();
		$field2['type']     = 'text';
		$field2['name']     = 'couponAmount';
		$field2['required'] = true;
		$field2['class']    = $this->get_setting( 'couponAmountType' ) == 'percentage' ? 'gf_format_percentage' : 'gf_format_money';

		$html2 = $this->settings_text( $field2, false );

		if ( $echo ) {
			echo $styles . $js_script . $html . $html2;
		}

		return $styles . $js_script . $html . $html2;


	}

	public function get_bulk_actions() {
		$bulk_actions                = parent::get_bulk_actions();
		$bulk_actions['reset_count'] = __( 'Reset Usage Count', 'gravityformscoupons' );

		return $bulk_actions;
	}

	public function process_bulk_action( $action ) {
		if ( $action == 'reset_count' ) {
			$feeds = rgpost( 'feed_ids' );
			if ( is_array( $feeds ) ) {
				foreach ( $feeds as $feed_id ) {
					$feed = $this->get_feed( $feed_id );
					if ( isset( $feed['meta']['usageCount'] ) ) {
						$feed['meta']['usageCount'] = 0;
						$this->update_feed_meta( $feed_id, $feed['meta'] );
					}
				}
			}
		} else {
			parent::process_bulk_action( $action );
		}
	}

	public function feed_list_columns() {
		return array(

			'couponTitle'  => __( 'Title', 'gravityformscoupons' ),
			'gravityForm'  => __( 'Form', 'gravityformscoupons' ),
			'couponAmount' => __( 'Amount', 'gravityformscoupons' ),
			'usageLimit'   => __( 'Usage Limit', 'gravityformscoupons' ),
			'usageCount'   => __( 'Usage Count', 'gravityformscoupons' ),
			'endDate'      => __( 'Expires', 'gravityformscoupons' ),
			'isStackable'  => __( 'Is Stackable', 'gravityformscoupons' ),
		);
	}

	public function get_column_value_gravityForm( $feed ) {
		return $this->get_form_name( $feed['meta']['gravityForm'] );
	}

	public function get_column_value_couponTitle( $feed ) {
		return $feed['meta']['couponName'] . ' (' . $feed['meta']['couponCode'] . ')';
	}

	public function get_column_value_endDate( $feed ) {
		return $feed['meta']['endDate'] == '' ? 'Never Expires' : $feed['meta']['endDate'];
	}

	public function get_column_value_usageLimit( $feed ) {
		return $feed['meta']['usageLimit'] == '' ? 'Unlimited' : $feed['meta']['usageLimit'];
	}

	public function get_column_value_usageCount( $feed ) {
		$usage_count = rgar( $feed['meta'], 'usageCount' );

		return $usage_count == '' ? '0' : $usage_count;
	}

	public function get_column_value_couponAmount( $feed ) {
		if ( $feed['meta']['couponAmountType'] == 'flat' ) {
			$couponAmount = GFCommon::to_money( $feed['meta']['couponAmount'] );
		} else {
			$couponAmount = GFCommon::to_number( $feed['meta']['couponAmount'] ) . '%';
		}

		return $couponAmount;
	}

	public function get_column_value_isStackable( $feed ) {
		if ( $feed['meta']['isStackable'] ) {
			return 'Yes';
		}
	}

	public function get_form_name( $formid ) {
		if ( $formid == '0' ) {
			return 'Any Form';
		}

		$form = RGFormsModel::get_form( $formid );
		if ( ! $form ) {
			return 'Invalid Form';
		}

		return $form->title;

	}

	// still required until enough users have GF 1.9.4.16 or greater
	public function coupon_add_field( $field_groups ) {

		foreach ( $field_groups as &$group ) {
			if ( $group['name'] == 'pricing_fields' ) {
				foreach ( $group['fields'] as &$field ) {
					if ( isset( $field['data-type'] ) && $field['data-type'] == 'coupon' ) {
						$field['onclick'] = "StartAddCouponField('coupon');";
						break;
					}
				}
				break;
			}
		}

		return $field_groups;
	}

	// still required until enough users have GF 1.9.4.16 or greater
	public function coupon_gform_editor_js() {
		?>
		<script type='text/javascript'>
			function StartAddCouponField(type) {
				if (GetFieldsByType(['product']).length <= 0) {
					alert("<?php _e( 'You must add a Product field to the form first', 'gravityformscoupons' ) ?>");
				}
				else if (GetFieldsByType(['coupon']).length > 0) {
					alert("<?php _e( 'Only one Coupon field can be added to the form', 'gravityformscoupons' ) ?>");
				}
				else {
					StartAddField(type);
				}
			}
		</script>
	<?php
	}

	public function set_defaults() {
		?>
		case "coupon" :
		field.label = "<?php _e( 'Coupon', 'gravityformscoupons' ); ?>";//setting the default field label
		break;
	<?php
	}

	public function get_coupon_field( $form ) {
		$coupons = GFCommon::get_fields_by_type( $form, array( 'coupon' ) );

		return count( $coupons ) > 0 ? $coupons[0] : false;
	}

	public function get_submitted_coupon_codes( $form ) {
		$coupon_field = $this->get_coupon_field( $form );

		if ( ! $coupon_field || rgempty( "input_{$coupon_field->id}" ) ) {
			return false;
		}

		$coupons = explode( ',', rgpost( "input_{$coupon_field->id}" ) );
		$coupons = array_map( 'trim', $coupons );

		return $coupons;
	}

	public function is_coupon_visible( $form ) {

		$is_visible = true;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'coupon' ) {
				// if conditional is enabled, but the field is hidden, ignore conditional
				$is_visible = ! RGFormsModel::is_field_hidden( $form, $field, array() );
				break;
			}
		}

		return $is_visible;

	}

	public function add_discounts( $product_info, $form, $lead ) {

		//Only add discount once when form is submitted
		$coupon_codes = $this->get_submitted_coupon_codes( $form );
		if ( ! $coupon_codes ) {
			return $product_info;
		}

		$total = GFCommon::get_total( $product_info );

		$coupons   = $this->get_coupons_by_codes( $coupon_codes, $form );
		$discounts = $this->get_discounts( $coupons, $total, $discount_total );

		foreach ( $coupons as $coupon ) {

			$price                                       = GFCommon::to_number( $discounts[ $coupon['code'] ]['discount'] );
			$product_info['products'][ $coupon['code'] ] = array(
				'name'     => $coupon['name'],
				'price'    => - $price,
				'quantity' => 1,
				'options'  => array(
					array(
						'option_name'  => $coupon['name'],
						'option_label' => __( 'Coupon Code:', 'gravityformscoupons' ) . ' ' . $coupon['code'],
						'price'        => 0,
					),
				)
			);
		}

		return $product_info;
	}

	public function maybe_process_feed( $entry, $form ) {

		if ( $entry['status'] == 'spam' ) {
			$this->log_debug( __METHOD__ . '(): Entry #' . $entry['id'] . ' is marked as spam.' );

			return $entry;
		}

		$coupon_codes = $this->get_submitted_coupon_codes( $form );
		if ( ! $coupon_codes ) {
			$this->log_debug( __METHOD__ . "(): No coupons submitted for entry #{$entry['id']}." );

			return $entry;
		}

		$coupons = $this->get_coupons_by_codes( $coupon_codes, $form );
		if ( is_array( $coupons ) ) {
			$processed_feeds = array();
			foreach ( $coupons as $coupon ) {
				$feed = $this->get_config( $form, $coupon['code'] );
				$this->log_debug( __METHOD__ . "(): Starting to process feed (#{$feed['id']} - {$feed['meta']['couponName']}) for entry #{$entry['id']}." );
				$this->process_feed( $feed, $entry, $form );
				$processed_feeds[] = $feed['id'];
			}

			//Saving processed feeds
			if ( ! empty( $processed_feeds ) ) {
				$meta = gform_get_meta( $entry['id'], 'processed_feeds' );
				if ( empty( $meta ) ) {
					$meta = array();
				}

				$meta[ $this->_slug ] = $processed_feeds;

				gform_update_meta( $entry['id'], 'processed_feeds', $meta );
			}
		}

		return $entry;
	}

	public function process_feed( $feed, $entry, $form ) {
		$meta               = $feed['meta'];
		$starting_count     = empty( $meta['usageCount'] ) ? 0 : $meta['usageCount'];
		$meta['usageCount'] = $starting_count + 1;

		$this->update_feed_meta( $feed['id'], $meta );
		$this->log_debug( __METHOD__ . "(): Updating usage count from {$starting_count} to {$meta['usageCount']}." );
	}

	public function apply_coupon_code() {

		$coupon_code    = strtoupper( $_POST['couponCode'] );
		$result         = '';
		$invalid_reason = '';
		if ( empty( $coupon_code ) ) {
			$invalid_reason = __( 'You must enter a value for coupon code.', 'gravityformscoupons' );
			$result         = array( 'is_valid' => false, 'invalid_reason' => $invalid_reason );
			die( GFCommon::json_encode( $result ) );
		}

		$form_id               = intval( $_POST['formId'] );
		$existing_coupon_codes = $_POST['existing_coupons'];
		$total                 = $_POST['total'];

		//fields meta
		$form   = RGFormsModel::get_form_meta( $form_id );
		$config = $this->get_config( $form, $coupon_code );

		if ( ! $config || ! $config['is_active'] ) {
			$invalid_reason = __( 'Invalid coupon.', 'gravityformscoupons' );
			$result         = array( 'is_valid' => false, 'invalid_reason' => $invalid_reason );
			die( GFCommon::json_encode( $result ) );
		}

		$can_apply = $this->can_apply_coupon( $coupon_code, $existing_coupon_codes, $config, $invalid_reason, $form );

		if ( $can_apply ) {
			$coupon_codes = empty( $existing_coupon_codes ) ? $coupon_code : $coupon_code . ',' . $existing_coupon_codes;
			$coupons      = $this->get_coupons_by_codes( explode( ',', $coupon_codes ), $form );

			$coupons = $this->sort_coupons( $coupons );
			foreach ( $coupons as $c ) {
				$couponss[ $c['code'] ] = array(
					'amount'      => $c['amount'],
					'name'        => $c['name'],
					'type'        => $c['type'],
					'code'        => $c['code'],
					'can_stack'   => $c['can_stack'],
					'usage_count' => $c['usage_count'],
				);
			}

			$result = array(
				'is_valid'       => $can_apply,
				'coupons'        => $couponss,
				'invalid_reason' => $invalid_reason,
				'coupon_code'    => $coupon_code,
			);

			die( GFCommon::json_encode( $result ) );
		} else {
			$result = array( 'is_valid' => false, 'invalid_reason' => $invalid_reason );
			die( GFCommon::json_encode( $result ) );
		}

	}

	public function get_coupons_by_codes( $codes, $form ) {

		if ( ! is_array( $codes ) ) {
			$codes = explode( ',', $codes );
		}

		$coupons = array();
		foreach ( $codes as $coupon_code ) {
			$coupon_code = strtoupper( trim( $coupon_code ) );
			$config      = $this->get_config( $form, $coupon_code );
			if ( $config ) {
				$coupons[ $coupon_code ] = array(
					'amount'      => GFCommon::to_number( $config['meta']['couponAmount'] ),
					'name'        => $config['meta']['couponName'],
					'type'        => $config['meta']['couponAmountType'],
					'code'        => $coupon_code,
					'can_stack'   => $config['meta']['isStackable'] == 1 ? true : false,
					'usage_count' => empty( $config['meta']['usageCount'] ) ? 0 : $config['meta']['usageCount']
				);
			}
		}

		if ( empty( $coupons ) ) {
			return false;
		}

		return $coupons;
	}

	public function get_coupon_by_code( $config ) {

		if ( empty( $config ) ) {
			return false;
		}
		$coupon = array(
			'amount'      => GFCommon::to_number( $config['meta']['couponAmount'] ),
			'name'        => $config['meta']['couponName'],
			'type'        => $config['meta']['couponAmountType'],
			'code'        => strtoupper( $config['meta']['couponCode'] ),
			'can_stack'   => $config['meta']['isStackable'] == 1 ? true : false,
			'usage_count' => empty( $config['meta']['usageCount'] ) ? 0 : $config['meta']['usageCount'],
			'limit'       => 10,
		);

		if ( empty( $coupon ) ) {
			return false;
		}

		return $coupon;
	}

	public function get_discounts( $coupons, &$total = 0, &$discount_total ) {

		require_once( GFCommon::get_base_path() . '/currency.php' );
		$currency = RGCurrency::get_currency( GFCommon::get_currency() );

		$coupons = $this->sort_coupons( $coupons );

		$discount_total = 0;

		foreach ( $coupons as $coupon ) {

			$discount = 0;

			$discount = $this->get_discount( $coupon, $total );

			$discount_total += $discount;

			$total -= $discount;

			$discounts[ $coupon['code'] ]['code']     = $coupon['code'];
			$discounts[ $coupon['code'] ]['name']     = $coupon['name'];
			$discounts[ $coupon['code'] ]['discount'] = GFCommon::to_money( $discount );
			$discounts[ $coupon['code'] ]['amount']   = $coupon['amount'];
			$discounts[ $coupon['code'] ]['type']     = $coupon['type'];

		}

		return $discounts;
	}

	public function get_discount( $coupon, $price ) {
		if ( $coupon['type'] == 'flat' ) {
			$currency = new RGCurrency( GFCommon::get_currency() );
			$discount = $currency->to_number( $coupon['amount'] );
		} else {
			$discount = $price * ( $coupon['amount'] / 100 );
		}

		$discount = $price - $discount >= 0 ? $discount : $price;
		$discount = apply_filters( 'gform_coupons_discount_amount', $discount, $coupon, $price );

		return $discount;
	}

	public function sort_coupons( $coupons ) {

		$sorted = array( 'cart_flat' => array(), 'cart_percentage' => array() );

		foreach ( $coupons as $coupon ) {

			$thing = $sorted[ 'cart' . '_' . $coupon['type'] ];

			if ( $coupon['type'] == 'percentage' ) {
				$sorted[ 'cart_' . $coupon['type'] ][ $coupon['code'] ] = $coupon;
			} else if ( $coupon['type'] != 'percentage' ) {
				$sorted[ 'cart_' . $coupon['type'] ][ $coupon['code'] ] = $coupon;
			}
		}

		if ( ! empty( $sorted['cart_percentage'] ) && count( $sorted[ 'cart_' . $coupon['type'] ] ) > 0 ) {
			usort( $sorted['cart_percentage'], array( 'GFCoupons', 'array_cmp' ) );
		}


		return array_merge( $sorted['cart_flat'], $sorted['cart_percentage'] );
	}

	public function array_cmp( $a, $b ) {
		return strcmp( $a['amount'], $b['amount'] );
	}

	public function can_apply_coupon( $coupon_code, $existing_coupon_codes, $config, &$invalid_reason = '', $form ) {

		$coupon = $this->get_coupon_by_code( $config );
		if ( ! $coupon ) {
			$invalid_reason = __( 'Invalid coupon.', 'gravityformscoupons' );

			return false;
		}

		if ( ! $this->is_valid( $config, $invalid_reason ) ) {
			return false;
		}

		//see if coupon code has already been applied, a code can only be applied once
		if ( in_array( $coupon_code, explode( ',', $existing_coupon_codes ) ) ) {
			$invalid_reason = __( "This coupon can't be applied more than once.", 'gravityformscoupons' );

			return false;
		}

		//checking if coupon can be stacked
		if ( ! is_array( $existing_coupon_codes ) ) {
			$existing_coupons = empty( $existing_coupon_codes ) ? array() : $this->get_coupons_by_codes( explode( ',', $existing_coupon_codes ), $form );
		}
		foreach ( $existing_coupons as $existing_coupon ) {
			if ( ! $existing_coupon['can_stack'] || ! $coupon['can_stack'] ) {
				$invalid_reason = __( "This coupon can't be used in conjunction with other coupons you have already entered.", 'gravityformscoupons' );

				return false;
			}
		}

		return true;
	}

	public function is_valid_code( $code, $config, &$invalid_reason = '' ) {
		$code = strtoupper( $code );
		if ( ! $this->is_valid( $config, $invalid_reason ) ) {
			return false;
		}

		$code_exists = false;

		if ( $config['meta']['coupon_code'] == $code ) {
			$code_exists = true;
		}

		if ( ! $code_exists ) {
			$invalid_reason = __( 'Invalid coupon.', 'gravityformscoupons' );

			return false;
		}

		return true;
	}

	public function is_valid( $config, &$invalid_reason = '' ) {

		if ( ! $config['is_active'] ) {
			$invalid_reason = __( 'This coupon is currently inactive.', 'gravityformscoupons' );

			return false;
		}

		$start_date = strtotime( $config['meta']['startDate'] ); //start of the day
		$end_date   = strtotime( $config['meta']['endDate'] . ' 23:59:59' ); //end of the day

		$now = GFCommon::get_local_timestamp();

		//validating start date
		if ( $config['meta']['startDate'] && $now < $start_date ) {
			$invalid_reason = __( 'Invalid coupon.', 'gravityformscoupons' );

			return false;
		}

		//validating end date
		if ( $config['meta']['endDate'] && $now > $end_date ) {
			$invalid_reason = __( 'This coupon has expired.', 'gravityformscoupons' );

			return false;
		}

		//validating usage limit
		$is_under_limit = false;
		$coupon_usage   = empty( $config['meta']['usageCount'] ) ? 0 : intval( $config['meta']['usageCount'] );
		if ( empty( $config['meta']['usageLimit'] ) || $coupon_usage < intval( $config['meta']['usageLimit'] ) ) {
			$is_under_limit = true;
		}
		if ( ! $is_under_limit ) {
			$invalid_reason = __( 'This coupon has reached its usage limit.', 'gravityformscoupons' );

			return false;
		}

		//coupon is valid
		return true;
	}

	public function get_config( $form, $coupon_code ) {
		$coupon_code = trim( $coupon_code );

		$configs = $this->get_feeds();

		if ( ! $configs ) {
			return false;
		}

		foreach ( $configs as $config ) {
			//form must match or be zero for any form
			if ( strtoupper( $config['meta']['couponCode'] ) == $coupon_code && ( $config['form_id'] == '0' || $config['form_id'] == $form['id'] ) ) {
				return $config;
			}
		}

		return false;
	}

	// used to upgrade old feeds into new version
	public function upgrade( $previous_version ) {
		$this->log_debug( __METHOD__ . '(): Checking to see if feeds need to be migrated.' );
		if ( empty( $previous_version ) ) {
			$previous_version = get_option( 'gf_coupons_version' );
		}
		$previous_is_pre_addon_framework = ! empty( $previous_version ) && version_compare( $previous_version, '2.0.dev1', '<' );

		if ( $previous_is_pre_addon_framework ) {
			$this->log_debug( __METHOD__ . '(): Upgrading feeds.' );
			$old_feeds = $this->get_old_feeds();

			if ( ! $old_feeds ) {
				return;
			}

			foreach ( $old_feeds as $old_feed ) {

				$form_id = $old_feed['form_id'];
				if ( rgblank( $form_id ) ) {
					$form_id = 0;
				}

				$is_active = $old_feed['is_active'];

				$couponAmount = rgar( $old_feed['meta'], 'coupon_amount' );
				if ( ! rgblank( $couponAmount ) ) {
					$couponAmount = GFCommon::to_number( $couponAmount );
				}

				$new_meta = array(
					'couponName'       => rgar( $old_feed['meta'], 'coupon_name' ),
					'gravityForm'      => $form_id,
					'couponCode'       => rgar( $old_feed['meta'], 'coupon_code' ),
					'couponAmountType' => rgar( $old_feed['meta'], 'coupon_type' ),
					'couponAmount'     => $couponAmount,
					'startDate'        => rgar( $old_feed['meta'], 'coupon_start' ),
					'endDate'          => rgar( $old_feed['meta'], 'coupon_expiration' ),
					'usageLimit'       => rgar( $old_feed['meta'], 'coupon_limit' ),
					'isStackable'      => rgar( $old_feed['meta'], 'coupon_stackable' ),
					'usageCount'       => rgar( $old_feed['meta'], 'coupon_usage' ),
				);
				$this->log_debug( __METHOD__ . '(): Inserting coupon ' . $new_meta['couponName'] . ' into new table.' );
				$this->insert_feed( $form_id, $is_active, $new_meta );

			}
			update_option( 'gf_coupons_upgrade', 1 );

			$this->log_debug( __METHOD__ . '(): Feed migration completed.' );
		} else {
			$this->log_debug( __METHOD__ . '(): The existing version of coupons is already on the new framework, no need to upgrade old feeds.' );
		}

	}

	public function get_old_feeds() {
		$this->log_debug( __METHOD__ . '(): Getting old feeds to migrate.' );
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_coupons';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = RGFormsModel::get_form_table_name();

		//do not copy over the coupons that are associated with a form in the trash, include is_trash is null to get the coupons not associated with a form
		$sql = "SELECT c.* FROM $table_name c LEFT JOIN $form_table_name f ON c.form_id = f.id
				WHERE is_trash = 0 OR is_trash is null";
		$wpdb->hide_errors(); //in case the user did not have the previous version of coupons and the table does not exist
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$count = sizeof( $results );
		$this->log_debug( __METHOD__ . '(): ' . $count . ' records found.' );
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;
	}

	public function get_feeds( $form_id = null ) {
		global $wpdb;

		$form_filter     = is_numeric( $form_id ) ? $wpdb->prepare( 'AND form_id=%d', absint( $form_id ) ) : '';
		$form_table_name = RGFormsModel::get_form_table_name();

		//only get coupons associated with active forms (is_trash = 0) per discussion with alex/dave
		//use is_trash is null to get the coupons associated with the "Any form" option because form id will be zero and the join will not include the coupon without this
		$sql = $wpdb->prepare(
			"SELECT af.* FROM {$wpdb->prefix}gf_addon_feed af LEFT JOIN {$form_table_name} f ON af.form_id = f.id
                               WHERE addon_slug=%s {$form_filter} AND (is_trash = 0 OR is_trash is null)", $this->_slug
		);

		$results = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $results as &$result ) {
			$result['meta'] = json_decode( $result['meta'], true );
		}

		return $results;
	}

	public function set_action_links( $action_links, $item, $column ) {
		if ( is_array( $action_links ) ) {
			//change array
			$feed_id              = '_id_';
			$form_id              = rgar( $item, 'form_id' );
			$edit_url             = add_query_arg( array( 'id' => $form_id, 'fid' => $feed_id ) );
			$action_links['edit'] = '<a title="' . __( 'Edit this feed', 'gravityformscoupons' ) . '" href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'gravityformscoupons' ) . '</a>';
		}

		return $action_links;
	}

	public function coupon_edit_page( $feed_id, $form_id ) {
		$messages = '';
		// Save feed if appropriate
		$feed_fields = $this->get_feed_settings_fields();

		$feed_id = $this->maybe_save_feed_settings( $feed_id, '' );

		$this->_coupon_feed_id = $feed_id;

		//update the form_id on the feed
		$feed = $this->get_feed( $feed_id );
		if ( is_array( $feed ) ) {
			$this->update_feed_form_id( $feed_id, rgar( $feed['meta'], 'gravityForm' ) );
		}

		?>
		<h3><span><?php echo $this->feed_settings_title() ?></span></h3>
		<input type="hidden" name="gf_feed_id" value="<?php echo $feed_id ?>"/>

		<?php
		$this->set_settings( $feed['meta'] );

		GFCommon::display_admin_message( '', $messages );

		$this->render_settings( $feed_fields );
	}

	public function plugin_page_init() {
		parent::plugin_page_init();

		require_once( GFCommon::get_base_path() . '/tooltips.php' );
	}

	public function update_feed_form_id( $id, $form_id ) {
		global $wpdb;

		$wpdb->update( "{$wpdb->prefix}gf_addon_feed", array( 'form_id' => $form_id ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

		return $wpdb->rows_affected > 0;
	}

	public function get_current_feed_id() {
		if ( $this->_coupon_feed_id ) {
			return $this->_coupon_feed_id;
		} else if ( ! rgempty( 'gf_feed_id' ) ) {
			return rgpost( 'gf_feed_id' );
		} else {
			return rgget( 'fid' );
		}
	}

	public static function get_active_feeds_by_coupon_code( $config, $active_coupons ) {

		foreach ( $active_coupons as $subKey => $subArray ) {
			if ( $subArray['meta']['couponCode'] != $config['meta']['couponCode'] ) {
				unset( $active_coupons[ $subKey ] );
			}
		}

		return $active_coupons;
	}

	public static function is_duplicate_coupon( $config, $active_coupons ) {
		if ( ! $active_coupons ) {
			return false;
		}

		foreach ( $active_coupons as $coupon ) {
			// return true coupon code is for any form and it is already associated with any form
			if ( ( empty( $coupon['form_id'] ) || $coupon['form_id'] == 0 ) && $config['id'] != $coupon['id'] ) {
				return true;
			}
			// return true if coupon code is for any form and is already associated with a specific form
			if ( $config['form_id'] == 0 && $config['id'] != $coupon['id'] ) {
				return true;
			}
			// return true if coupon code is for specific form and is already associated with another specific form
			if ( $config['meta']['couponCode'] == $coupon['meta']['couponCode'] && $coupon['form_id'] == $config['form_id'] && $config['id'] != $coupon['id'] ) {
				return true;
			}
		}

		return false;

	}

	public function validate_coupon_amount( $field ) {
		//make sure a coupon amount is entered
		$settings = $this->get_posted_settings();

		if ( empty( $settings['couponAmount'] ) ) {
			$this->set_field_error( array( 'name' => 'couponAmount' ), __( 'This field is required.', 'gravityformscoupons' ) );
		}
	}

	public function check_if_duplicate_coupon_code( $field ) {
		$settings = $this->get_posted_settings();

		$config['id']                 = $this->get_current_feed_id();
		$config['form_id']            = $settings['gravityForm'];
		$config['meta']['couponCode'] = $settings['couponCode'];

		//check for duplicate coupons
		$active_feeds_by_coupon_code = $this->get_active_feeds_by_coupon_code( $config, $this->get_feeds() );
		$duplicate_coupon_code       = $this->is_duplicate_coupon( $config, $active_feeds_by_coupon_code );

		if ( $duplicate_coupon_code ) {
			$this->set_field_error( $field, __( 'The Coupon Code entered is already in use. Please enter a unique Coupon Code and try again.', 'gravityformscoupons' ) );
		}
	}

	public function get_posted_settings() {
		$post_data = parent::get_posted_settings();

		if ( ! empty( $post_data ) && isset( $post_data['couponAmount'] ) ) {
			//strip currency formatting off of coupon amount before it is saved
			$post_data['couponAmount'] = GFCommon::to_number( rgar( $post_data, 'couponAmount' ) );
		}

		return $post_data;
	}

	public function add_form_settings_menu( $tabs, $form_id ) {
		//prevents coupons from being on the Form Contextual menu
		if ( $this->_slug != 'gravityformscoupons' ) {
			return parent::add_form_settings_menu( $tabs, $form_id );
		} else {
			return $tabs;
		}
	}

}