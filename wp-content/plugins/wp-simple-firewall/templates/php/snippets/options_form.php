<form action="<?php echo $form_action; ?>" method="post" class="form-horizontal">
	<?php echo $nonce_field; ?>

	<ul class="nav nav-tabs">
		<?php foreach ( $aAllOptions as $sOptionSection ) : ?>
			<li class="<?php echo $sOptionSection['section_primary'] ? 'active' : '' ?>">
				<a href="#<?php echo $sOptionSection['section_slug'] ?>" data-toggle="tab" ><?php echo $sOptionSection['section_title_short']; ?></a>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="tab-content">
		<?php foreach ( $aAllOptions as $sOptionSection ) : ?>

			<div class="tab-pane fade <?php echo $sOptionSection['section_primary'] ? 'active in primary_section' : 'non_primary_section'; ?>"
				 id="<?php echo $sOptionSection['section_slug'] ?>">
				<div class="row option_section_row <?php echo $sOptionSection['section_primary'] ? 'primary_section' : 'non_primary_section'; ?>"
					 id="row-<?php echo $sOptionSection['section_slug']; ?>">
					<div class="span9">
						<fieldset>
							<legend><?php echo $sOptionSection['section_title']; ?></legend>

							<?php if ( !empty( $sOptionSection['section_summary'] ) ) : ?>
								<div class="row row_section_summary">
									<div class="span9">
										<?php foreach( $sOptionSection['section_summary'] as $sItem ) : ?>
											<p class="noselect"><?php echo $sItem; ?></p>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>

							<?php foreach( $sOptionSection['section_options'] as $nKeyRow => $aOption ) : ?>
								<div class="row row_number_<?php echo $nKeyRow; ?>">
									<?php $sOptionKey = $aOption['key']; $sOptionType = $aOption['type']; ?>

									<?php if ( $sOptionKey == 'spacer' ) : ?>
										<div class="span8"></div>
									<?php else: ?>

										<div class="item_group span8 <?php echo ( $aOption['value'] == 'Y' || $aOption['value'] != $aOption['default'] ) ? 'selected_item_group':''; ?>" id="span_<?php echo $var_prefix.$sOptionKey; ?>">
											<div class="control-group">
												<label class="control-label" for="<?php echo $var_prefix.$sOptionKey; ?>">
													<?php echo $aOption['name']; ?>
													<br />
													[<a href="<?php echo $aOption['info_link']; ?>" target="_blank"><?php echo $strings['more_info']; ?></a>
													<?php if ( !empty( $aOption['blog_link'] ) ) : ?>
														| <a href="<?php echo $aOption['blog_link']; ?>" target="_blank"><?php echo $strings['blog']; ?></a>
													<?php endif; ?>
													]
												</label>
												<div class="controls">
													<div class="option_section <?php echo ( $aOption['value'] == 'Y' ) ? 'selected_item':''; ?>" id="option_section_<?php echo $var_prefix.$sOptionKey; ?>">
														<label>
															<?php if ( $sOptionType == 'checkbox' ) : ?>

																<input type="checkbox" name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																	   value="Y" <?php echo ( $aOption['value'] == 'Y' ) ? 'checked="checked"':''; ?> />
																<?php echo $aOption['summary']; ?>

															<?php elseif ( $sOptionType == 'text' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<input type="text" name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																	   value="<?php echo $aOption['value']; ?>" placeholder="<?php echo $aOption['value']; ?>" class="span5" />

															<?php elseif ( $sOptionType == 'password' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<input type="password" name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																	   value="<?php echo $aOption['value']; ?>" placeholder="<?php echo $aOption['value']; ?>" class="span5" />

															<?php elseif ( $sOptionType == 'email' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<input type="email" name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																	   value="<?php echo $aOption['value']; ?>" placeholder="<?php echo $aOption['value']; ?>" class="span5" />

															<?php elseif ( $sOptionType == 'select' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<select name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>">
																	<?php foreach( $aOption['value_options'] as $sOptionValue => $sOptionValueName ) : ?>
																		<option value="<?php echo $sOptionValue; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>_<?php echo $sOptionValue; ?>"
																			<?php echo ( $sOptionValue == $aOption['value'] ) ? 'selected="selected"' : ''; ?>
																			><?php echo $sOptionValueName; ?></option>
																	<?php endforeach; ?>
																</select>

															<?php elseif ( $sOptionType == 'multiple_select' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<select name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																		multiple="multiple" size="<?php echo count( $aOption['value_options'] ); ?>">
																	<?php foreach( $aOption['value_options'] as $sOptionValue => $sOptionValueName ) : ?>
																		<option value="<?php echo $sOptionValue; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>_<?php echo $sOptionValue; ?>"
																			<?php echo in_array( $sOptionValue, $aOption['value'] ) ? 'selected="selected"' : ''; ?>
																			><?php echo $sOptionValueName; ?></option>
																	<?php endforeach; ?>
																</select>

															<?php elseif ( $sOptionType == 'ip_addresses' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<textarea name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																		  placeholder="<?php echo $aOption['value']; ?>" rows="<?php echo $aOption['rows']; ?>"
																		  class="span5" ><?php echo $aOption['value']; ?></textarea>

															<?php elseif ( $sOptionType == 'array' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<textarea name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																		  placeholder="<?php echo $aOption['value']; ?>" rows="<?php echo $aOption['rows']; ?>"
																		  class="span5" ><?php echo $aOption['value']; ?></textarea>

															<?php elseif ( $sOptionType == 'yubikey_unique_keys' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<textarea name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																		  placeholder="<?php echo $aOption['value']; ?>" rows="<?php echo $aOption['rows']; ?>"
																		  class="span5" ><?php echo $aOption['value']; ?></textarea>

															<?php elseif ( $sOptionType == 'comma_separated_lists' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<textarea name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																		  placeholder="<?php echo $aOption['value']; ?>" rows="<?php echo $aOption['rows']; ?>"
																		  class="span5" ><?php echo $aOption['value']; ?></textarea>

															<?php elseif ( $sOptionType == 'integer' ) : ?>

																<p><?php echo $aOption['summary']; ?></p>
																<input type="text" name="<?php echo $var_prefix.$sOptionKey; ?>" id="<?php echo $var_prefix.$sOptionKey; ?>"
																	   value="<?php echo $aOption['value']; ?>" placeholder="<?php echo $aOption['value']; ?>" class="span5" />

															<?php else : ?>
																ERROR: Should never reach this point.
															<?php endif; ?>

														</label>
														<p class="help-block"><?php echo  $aOption['description']; ?></p>
														<div style="clear:both"></div>
													</div>
												</div><!-- controls -->
											</div><!-- control-group -->
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</fieldset>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="form-actions">
		<input type="hidden" name="<?php echo $var_prefix; ?>all_options_input" value="<?php echo $all_options_input; ?>" />
		<input type="hidden" name="<?php echo $var_prefix; ?>plugin_form_submit" value="Y" />
		<button type="submit" class="btn btn-primary btn-large" name="submit"><?php _wpsf_e( 'Save All Settings' ); ?></button>
	</div>
</form>