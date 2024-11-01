<script>
var tak_ali_cre_pr = "<?php _e('Creating Base Product : ', 'taxnalogy-aliexpress-product-importer'); ?>",
	tak_ali_cre_im = "<?php _e('Creating Images : ', 'taxnalogy-aliexpress-product-importer'); ?>",
	tak_ali_cre_att = "<?php _e('Creating Attributes : ', 'taxnalogy-aliexpress-product-importer'); ?>",
	tak_ali_cre_var_d = "<?php _e('Creating Variation Data : ', 'taxnalogy-aliexpress-product-importer'); ?>",
	tak_ali_cre_var = "<?php _e('Creating Variations : ', 'taxnalogy-aliexpress-product-importer'); ?>";
</script>
<?php
echo '<script>
		var takaliajaxurl = \''.site_url('wp-admin/admin-ajax.php').'\',
	    _nonce = \''.wp_create_nonce('wp_rest').'\';
 		</script>';
?>

<div class="w3-panel">
	<h1> <?php _e('Taknalogy Aliexpress Product Importer', 'taxnalogy-aliexpress-product-importer'); ?> </h1>

	<div class="container">

		<!-- Nav tabs -->
		<ul class="nav nav-pills mb-3 nav-fill" id="myTab" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home"
					aria-selected="true">Home</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab"
					aria-controls="settings" aria-selected="false">Chrome Extension Settings</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="keys-tab" data-toggle="tab" href="#keys" role="tab" aria-controls="keys"
					aria-selected="false">Keys</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="help-tab" data-toggle="tab" href="#help" role="tab" aria-controls="profile"
					aria-selected="false">Help</a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
				<div class="container-fluid">
					<div class="container">
						<div class="notice notice-info" id="home_notice">
							<p>Please use <a
									href="https://chrome.google.com/webstore/detail/taknalogy-aliexpress-drop/gbbfmfcdncigpkaefnocojojbncliafb"
									target="_blank">google chrome extension</a> for easy product import.</p>
						</div>
						<div class="form-container">
							<form action="#" method="post" id="get_product_form">
								<p>
									<label for="regular_multiplier"><strong>
											<?php _e('Regular Price Multiplier :', 'taxnalogy-aliexpress-product-importer'); ?>
										</strong></label>
									<input type="number" name="regular_multiplier" id="regular_multiplier" value="5"
										step="0.01" />
								</p>
								<p>
									<label for="product_onsale"><strong>
											<?php _e('On Sale :', 'taxnalogy-aliexpress-product-importer'); ?>
										</strong></label>
									<input type="checkbox" name="product_onsale" id="product_onsale" checked
										onchange="taknalogyproductonsale(event)" />
								</p>
								<p id="p_sale_multiplier">
									<label for="sale_multiplier"><strong>
											<?php _e('Sale Price Multiplier :', 'taxnalogy-aliexpress-product-importer'); ?>
										</strong></label>
									<input type="number" name="sale_multiplier" id="sale_multiplier" value="2.5"
										step="0.01" />
								</p>
								<p>
									<label for="full_import"><strong>
											<?php _e('Full Import :', 'taxnalogy-aliexpress-product-importer'); ?>
										</strong></label>
									<input type="checkbox" name="full_import" id="full_import" unchecked
										onchange="taknalogyfullimport(event)" />
								</p>
								<p>
									<label for="delivery_charge"><strong>
											<?php _e('Delivery Charge :', 'taxnalogy-aliexpress-product-importer'); ?>
										</strong></label>
									<input type="number" name="delivery_charge" id="delivery_charge" value="0"
										step="0.01" />
								</p>
								<p>
									<label for="new_product_url"><strong>
											<?php _e('Aliexpress Product URL:', 'taxnalogy-aliexpress-product-importer'); ?>
										</strong></label>
									<input type="url" name="url" size="125" id='new_product_url' required />
								</p>
								<?php wp_nonce_field('get_product_form'); ?>
								<div class="caption">
									<span id="server-results">
										<!-- For server results --></span>
									<?php submit_button(__('Get Base Product', 'taxnalogy-aliexpress-product-importer'), 'primary get_base_product', 'get_base_product', true); ?>
								</div>
							</form>
						</div>
						<div class="form-container-grid">
							<form action="#" method="post" id="get_product_images">
								<p>
									<label><strong>
											<?php _e('Active Product ID :', 'taxnalogy-aliexpress-product-importer'); ?>
										</strong></label>
									<input type="text" name="post_id" size="13" readonly="readonly"
										id="created_product" />
									<input type="hidden" name="created_product_url" id="created_product_url" />
									<input type="hidden" name="product_onsale_hidden" id="product_onsale_hidden" />
									<input type="hidden" name="regular_multiplier_hidden"
										id="regular_multiplier_hidden" />
									<input type="hidden" name="sale_multiplier_hidden" id="sale_multiplier_hidden" />
									<input type="hidden" name="delivery_charge_hidden" id="delivery_charge_hidden" />
									<input type="hidden" name="variation_list" id="variation_list" />
									<input type="hidden" name="product_type_hidden" id="product_type_hidden" />
								</p>
								<?php wp_nonce_field('get_product_images'); ?>
								<div class="controls" id="controls">
									<?php submit_button(__('Get Images', 'taxnalogy-aliexpress-product-importer'), 'primary submit_images', 'get_images', true); ?>
									<?php submit_button(__('Create Attributes', 'taxnalogy-aliexpress-product-importer'), 'primary submit_attributes', 'create_attributes', true); ?>
									<?php submit_button(__('Create Variation Data', 'taxnalogy-aliexpress-product-importer'), 'primary submit_var_data', 'create_variation_data', true); ?>
									<?php submit_button(__('Create Variations', 'taxnalogy-aliexpress-product-importer'), 'primary', 'submit_save_var', true); ?>
								</div>
							</form>
						</div>
						<div class="notice notice-success" id="get_base_product_status"> </div>
						<div class="notice notice-success" id="submit_images_status"> </div>
						<div class="notice notice-success" id="submit_attributes_status"> </div>
						<div class="notice notice-success" id="submit_var_data_status"> </div>
						<div class="notice notice-success" id="submit_save_var_status"> </div>
					</div>
				</div>
			</div>
			<div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
				<div class="container">
					<div class="notice notice-info is-dismissible" id="settings_tab_update_notice">
					</div>

					<form id='settings'>
						<div class="form-group row">
							<label for="price_multi" class="col-sm-6 col-form-label">Price Multiplier</label>
							<div class="col-sm-2">
								<input type="number" step="0.1" class="form-control" name="price_multi" id="price_multi"
									value="1.2">
							</div>
							<div class="col-sm-4"> </div>
							<small id="price_multiHelp" class="form-text text-muted">Base price of the item will be
								multiplied by
								this.</small>
						</div>
						<div class="form-group row">
							<label for="sale_multi" class="col-sm-6 col-form-label">Sale Price Multiplier</label>
							<div class="col-sm-2">
								<input type="number" step="0.1" class="form-control" name="sale_multi" id="sale_multi">
							</div>
							<div class="col-sm-4"> </div>
							<small id="sale_multiHelp" class="form-text text-muted">Sale price of the item will be
								multiplied by
								this.</small>
						</div>
						<div class="form-group row">
							<label for="category" class="col-sm-6 col-form-label">Dafault Product Category</label>
							<div class="col-sm-6">
								<select class="form-control form-control-sm" id="category" name="category"></select>
							</div>
							<small id="categoryHelp" class="form-text text-muted">Imported products will be assigned
								this category by default.</small>
						</div>
						<div class="form-group row">
							<label for="download_images" class="col-sm-6 col-form-label">Download Images</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="download_images" id="download_images">
							</div>
							<small id="download_imagesHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="import_product_images_limit" class="col-sm-6 col-form-label">Product Images
								Import Limit</label>
							<div class="col-sm-2">
								<input type="number" step="1" class="form-control" name="import_product_images_limit"
									id="import_product_images_limit">
							</div>
							<div class="col-sm-4"> </div>
							<small id="import_product_images_limitHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="import_short_description" class="col-sm-6 col-form-label">Import Short
								Description</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="import_short_description"
									id="import_short_description">
							</div>
							<small id="import_short_descriptionHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="import_long_description" class="col-sm-6 col-form-label">Import Long
								Description</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="import_long_description"
									id="import_long_description">
							</div>
							<small id="import_long_descriptionHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="import_attributes" class="col-sm-6 col-form-label">Import Attributes</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="import_attributes"
									id="import_attributes">
							</div>
							<small id="import_attributesHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="default_product_status" class="col-sm-6 col-form-label">Default Product
								Status</label>
							<div class="col-sm-6">
								<select class="form-control form-control-sm" id="default_product_status"
									name="default_product_status"></select>
							</div>
							<small id="default_product_statusHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="manage_stock" class="col-sm-6 col-form-label">Manage Stock</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="manage_stock" id="manage_stock">
							</div>
							<small id="manage_stockHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="use_random_stock" class="col-sm-6 col-form-label">Use Random Stock</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="use_random_stock"
									id="use_random_stock">
							</div>
							<small id="use_random_stockHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="random_stock_min" class="col-sm-6 col-form-label">Random Stock Lower
								Limit</label>
							<div class="col-sm-2">
								<input type="number" step="1" class="form-control" name="random_stock_min"
									id="random_stock_min">
							</div>
							<div class="col-sm-4"> </div>
							<small id="random_stock_minHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="random_stock_max" class="col-sm-6 col-form-label">Random Stock Upper
								Limit</label>
							<div class="col-sm-2">
								<input type="number" step="1" class="form-control" name="random_stock_max"
									id="random_stock_max">
							</div>
							<div class="col-sm-4"> </div>
							<small id="random_stock_maxHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="use_sku_number" class="col-sm-6 col-form-label">Use Aliexpress SKU</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="use_sku_number" id="use_sku_number">
							</div>
							<small id="use_sku_numberHelp" class="form-text text-muted"></small>
						</div>
						<div class="form-group row">
							<label for="taknalogy_reviews" class="col-sm-6 col-form-label">Taknalogy Reviews</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="taknalogy_reviews"
									id="taknalogy_reviews">
							</div>
							<small id="taknalogy_reviewsHelp" class="form-text text-muted">Use <a
									href="https://wordpress.org/plugins/taknalogy-reviews/" target="_blank">Taknalogy
									Reviews Plugin</a> and <a
									href="https://chrome.google.com/webstore/detail/taknalogy-aliexpress-revi/hkpbllhlbaaldojhfpobclakfbmopgbm"
									target="_blank">Chrome Extension</a> to manage reviews. <a
									href="https://taknalogy.com/blog/2019/11/28/taknalogy-reviews-platform-documentation/"
									target="_blank">Documentation is available here.</a></small>
						</div>
						<div class="form-group row">
							<label for="taknalogy_reviews_widget" class="col-sm-6 col-form-label">Taknalogy Reviews
								Widget</label>
							<div class="col-sm-6">
								<input type="checkbox" class="form-control" name="taknalogy_reviews_widget"
									id="taknalogy_reviews_widget">
							</div>
							<small id="taknalogy_reviews_widgetHelp" class="form-text text-muted"></small>
						</div>
						<div>
							<button id='defaultsettings' type="button" class="col-sm-2 btn btn-warning">Reset</button>
							<button type="submit" class=" col-sm-2 btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
			<div class="tab-pane fade" id="keys" role="tabpanel" aria-labelledby="keys-tab">
				<div class="container">
					<div class="notice notice-info" id="keys_tab_notice">
						<p>Shop URL and Access String is required to connect chrome extension to your shop.</p>
						<a href="https://taknalogy.com/blog/2020/02/16/taknalogy-aliexpress-dropshipping/"
							target="_blank">Please read documentation for further details.</a>
					</div>
					<div class="notice notice-info is-dismissible" id="keys_tab_update_notice">
						<p>Successfully updated.</p>
					</div>
					<form id='taknalogykeys'>
						<div class="container-fluid">
							<div class="form-group row">
								<label for="shop_url" class="col-sm-6 col-form-label">Shop URL</label>
								<div class="col-sm-6">
									<input type="text" class="form-control" name="shop_url" id="shop_url" value="">
								</div>
								<small id="shop_urlHelp" class="form-text text-muted">If your shop URL is not correct
									please
									correct it and press Update button below.</small>
							</div>
							<div class="form-group row">
								<label for="access_string" class="col-sm-6 col-form-label">Access String
									<button id='regenerate' type="button" class="btn btn-labeled btn-warning">Generate
										New</button>
								</label>
								<div class="col-sm-6">
									<input type="text" class="form-control" name="access_string" id="access_string"
										value="">
								</div>
								<small id="access_stringHelp" class="form-text text-muted">You can either update this
									value
									or press Generate button. Hit save to update new value.</small>
							</div>
							<div>
								<button type="submit" class=" col-sm-2 btn btn-primary">Update</button>
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="tab-pane fade" id="help" role="tabpanel" aria-labelledby="help-tab">
				<div class="container">
					<div class="container-fluid">
						<div class="panel panel-default">
							<h1 class="panel-heading">Below are some links to the partner extensions/plugins and
								documentation.</h1>
                                <div class="panel-body">
                                    <div class="list-group">
                                        <a href="https://taknalogy.com/blog/2020/02/16/taknalogy-aliexpress-dropshipping/" target="_blank" class="list-group-item">Click to access detailed documentation about Taknalogy Aliexpress Product Importer.</a>
                                        <a href="https://chrome.google.com/webstore/detail/taknalogy-aliexpress-drop/gbbfmfcdncigpkaefnocojojbncliafb"
                                            target="_blank" class="list-group-item">Download partner google chrome extension to easy import prodcuts from aliexpress.</a>
                                        <a href="https://chrome.google.com/webstore/detail/taknalogy-aliexpress-revi/hkpbllhlbaaldojhfpobclakfbmopgbm"
                                            target="_blank" class="list-group-item">Product reviews google chrome extension downloaded link.</a>
                                        <a href="https://wordpress.org/plugins/taknalogy-reviews/"
                                            target="_blank" class="list-group-item">Taknalogy Reviews WordPress plugin is avaliable here.</a>
                                        <a href="https://taknalogy.com/blog/2019/11/28/taknalogy-reviews-platform-documentation/" target="_blank" class="list-group-item">Taknalogy Reviews documentation is available here.</a>
                                    </div>
                                </div>
							<div class="panel-footer ">
								<a  target="_blank"
									href="https://wordpress.org/support/plugin/taxnalogy-aliexpress-product-importer/reviews/?rate=5#new-post">Show us some love by leaving your review here.
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
