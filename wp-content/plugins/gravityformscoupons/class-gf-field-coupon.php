<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Coupon extends GF_Field {

	public $type = 'coupon';

	public function get_form_editor_field_title() {
		return __( 'Coupon', 'gravityformscoupon' );
	}

	public function get_form_editor_button() {
		return array(
			'group' => 'pricing_fields',
			'text'  => $this->get_form_editor_field_title()
		);
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'label_setting',
			'admin_label_setting',
			'css_class_setting',
			'description_setting',
			'placeholder_setting',
			'visibility_setting',
			'rules_setting',
			'error_message_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	public function get_first_input_id( $form ) {
		return sprintf( 'gf_coupon_code_%s', $form['id'] );
	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$id              = (int) $this->id;

		if ( $is_entry_detail ) {
			$input = "<input type='hidden' id='input_{$id}' name='input_{$id}' value='{$value}' />";

			return $input . '<br/>' . __( 'Coupon fields are not editable', 'gravityformscoupons' );
		}

		$disabled_text         = $this->is_form_editor() ? 'disabled="disabled"' : '';
		$logic_event           = $this->get_conditional_logic_event( 'change' );
		$placeholder_attribute = $this->get_field_placeholder_attribute();
		$coupons_detail        = rgpost( "gf_coupons_{$form_id}" );
		$coupon_codes          = empty( $coupons_detail ) ? '' : rgpost( "input_{$id}" );

		$input = "<div class='ginput_container' id='gf_coupons_container_{$form_id}'>" .
		         "<input id='gf_coupon_code_{$form_id}' class='gf_coupon_code' onkeyup='DisableApplyButton({$form_id});' onchange='DisableApplyButton({$form_id});' onpaste='setTimeout(function(){DisableApplyButton({$form_id});}, 50);' type='text'  {$disabled_text} {$placeholder_attribute} " . $this->get_tabindex() . '/>' .
		         "<input type='button' disabled='disabled' onclick='ApplyCouponCode({$form_id});' value='" . __( 'Apply', 'gravityformscoupons' ) . "' id='gf_coupon_button' class='button' {$disabled_text} " . $this->get_tabindex() . '/> ' .
		         "<img style='display:none;' id='gf_coupon_spinner' src='" . gf_coupons()->get_base_url() . "/images/spinner.gif' alt='" . __( 'please wait', 'gravityformscoupons' ) . "'/>" .
		         "<div id='gf_coupon_info'></div>" .
		         "<input type='hidden' id='gf_coupon_codes_{$form_id}' name='input_{$id}' value='" . esc_attr( $coupon_codes ) . "' {$logic_event} />" .
		         "<input type='hidden' id='gf_total_no_discount_{$form_id}'/>" .
		         "<input type='hidden' id='gf_coupons_{$form_id}' name='gf_coupons_{$form_id}' value='" . esc_attr( $coupons_detail ) . "' />" .
		         "</div>";

		return $input;
	}

	public function validate( $value, $form ) {

		//if there are no coupon codes to validate, abort
		$coupon_codes = gf_coupons()->get_submitted_coupon_codes( $form );
		if ( ! $coupon_codes ) {
			return;
		}

		$existing_coupon_codes = '';
		$message               = '';

		foreach ( $coupon_codes as $coupon_code ) {

			$config = gf_coupons()->get_config( $form, $coupon_code );
			if ( ! $config ) {
				$message = __( 'Coupon code: ' . $coupon_code . ' is invalid.', 'gravityformscoupons' );
				break;
			}

			$can_apply = gf_coupons()->can_apply_coupon( $coupon_code, $existing_coupon_codes, $config, $message, $form );
			if ( $can_apply ) {
				$existing_coupon_codes .= empty( $existing_coupon_codes ) ? $coupon_code : $coupon_code . ',' . $existing_coupon_codes;
			}
			else {
				break;
			}
		}
		if ( ! empty( $message ) ) {
			$this->failed_validation  = true;
			$this->validation_message = $message;
		}
	}

	public function get_form_editor_inline_script_on_page_render() {
		return "
		gform.addFilter('gform_form_editor_can_field_be_added', function (canFieldBeAdded, type) {
			if (type == 'coupon') {
				if (GetFieldsByType(['product']).length <= 0) {
					alert('" . __( 'You must add a Product field to the form first', 'gravityformscoupons' ) . "');
					return false;
				} else if (GetFieldsByType(['coupon']).length) {
					alert('" . __( 'Only one Coupon field can be added to the form', 'gravityformscoupons' ) . "');
					return false;
				}
			}
			return canFieldBeAdded;
		});";
	}

}

GF_Fields::register( new GF_Field_Coupon() );