<?php
/**
 * Shipping Transdirect Calculator
 *
 * @author 		Transdirect
 * @version     4.9
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
global $woocommerce, $wpdb; 
?>

<?php
	$getTitle = getApiDetails();
 	$trans_title = !empty($getTitle->shipping_title) ? $getTitle->shipping_title : 'Get a shipping estimate';
?>

<script>
	jQuery(document).ready(function() {
		jQuery("#to_postcode").hide();
		imageUrl = "<?php echo site_url(); ?>/wp-content/plugins/transdirect-shipping/assets/images/ajax-loader-bg.gif";
		jQuery('body').click(function() {
			jQuery('#autocomplete-div').hide('');
			jQuery('#dynamic_content').hide('');
		});
		jQuery('body').on('change', '#shipping_method input.shipping_method', function() {
			if(jQuery(this).val() != 'woocommerce_transdirect') {
				jQuery('div.tdCalc').hide();
			} else {
				jQuery('div.tdCalc').show();
			}
		});
		jQuery('#to_location').on('change', function() {
		    var countryData = jQuery("#to_location").countrySelect("getSelectedCountryData");
		    if(countryData)
		    {
		        jQuery("#to_location").val(countryData.name);
		        jQuery("#txt_country").val(countryData.iso2);
		    }
		});

		var latestRequestNumber = 0;
		var globalTimeout = null;

		jQuery('body').on('keyup', '#to_location', function() {
			jQuery("#to_postcode").hide();
            var key_val = jQuery("#to_location").val();
			var position = jQuery("#to_location").position();
            var html = '';
            jQuery('#to_location').addClass('loadinggif');
			if (key_val=='') {
                key_val=0;
            }
			jQuery.getJSON("<?php echo plugins_url('includes/locations.php' ,  dirname(__FILE__) ); ?>", {'q':key_val, requestNumber: ++latestRequestNumber }, function(data) {
	            if (data.requestNumber < latestRequestNumber) {
	            	return;
	            }
				if (data.locations != '' && key_val != '0') {
	                jQuery.each(data.locations, function(index, value ) {
	                	if(value.postcode &&  value.locality) {
		                	jQuery('.get_postcode').val(value.postcode);
		                	jQuery('.get_location').val(value.locality);
					        html = html+'<li onclick="get_value(\''+value.postcode+'\',\''+value.locality+'\')">'+value.postcode+', '+value.locality+'</li>';
					    }
			        });
			        var main_content = '<ul id="auto_complete">'+html+'</ul>';
					jQuery("#loading-div").hide();
			        jQuery("#autocomplete-div").show();
			        jQuery("#autocomplete-div").html(main_content);
			        jQuery("#autocomplete-div").css('left', position.left);
			        jQuery("#autocomplete-div").css('top', parseInt(position.top) + 45);
	            } else {
	                html = html+'<li>No Results Found</li>';
	                var main_content = '<ul id="auto_complete">'+html+'</ul>';

	                jQuery("#autocomplete-div").show();
			        jQuery("#autocomplete-div").html(main_content);
			        jQuery("#autocomplete-div").css('left', position.left);
			        jQuery("#autocomplete-div").css('top', parseInt(position.top) + 45);
			        jQuery("#autocomplete-div").css('overflow-y','hidden');

			        jQuery('#to_location').removeClass('loadinggif');
	            }
				jQuery('#to_location').removeClass('loadinggif');
            });
		});
	});
	function get_value(postcode, locality) {
		jQuery("#to_location").countrySelect("setCountry", 'Australia');
	    jQuery("#to_location").val(postcode + ',' + locality);
		jQuery("#autocomplete-div").html('');
	    jQuery( "#autocomplete-div" ).hide();
	}
	var price = <?php echo $_SESSION['price'] ? $_SESSION['price']  : '0'; ?>;
</script>
<?php if((!empty($_SESSION['price']) && !empty($_SESSION['selected_courier']))): ?>
	<script>
	jQuery('.sel-courier').hide();
	</script>
<?php endif; ?>


<?php if ( ((get_option('woocommerce_enable_shipping_calc') === 'no' || get_option('woocommerce_enable_shipping_calc') === 'yes') &&
		 !is_cart()) || (get_option( 'woocommerce_enable_shipping_calc' ) === 'yes' && is_cart()) ): ?>
	<?php
		$shipping_details = $wpdb->get_results("SELECT `option_value` FROM " . $wpdb->prefix . "options WHERE `option_name`='woocommerce_woocommerce_transdirect_settings'");
		$default_values = unserialize($shipping_details[0]->option_value);
	?>
		
	<div class="tdCalc">
	    <?php if((!empty($_SESSION['price']) && !empty($_SESSION['selected_courier'])) || (isset($_SESSION['free_shipping']) && !empty($_SESSION['free_shipping']))): ?>
	    <div class="sel-courier">
	        <input type="hidden" class="session_price" value="<?= $_SESSION['price']?>">
	        <input type="hidden" class="session_selected_courier" value="<?= $_SESSION['selected_courier']?>">
	        <p class="courier-selected">
	            <b>Selected Courier:</b>&nbsp;
	            <i class="courier-data"><?php echo $string = str_replace('_', ' ', $_SESSION['selected_courier']);?></i> - <strong class="price-data"><?php echo get_woocommerce_currency_symbol().' '.number_format($_SESSION['price'], 2); ?></strong>
	        </p>
	        <input type="hidden" class="get_postcode" value="<?= $_SESSION['postcode']?>">
	        <input type="hidden" class="get_location" value="<?= $_SESSION['to_location']?>">
	        <input type="hidden" name="txt_country" id="txt_country" class="get_country">
	        <input type="hidden" id="locationUrl" value="<?php echo plugins_url('includes/locations.php' , dirname(__FILE__) ); ?>">
	        <p><a onclick="showCalc()" class="link-show-calculator">(change)</a></p>
	    </div>
	    <?php else: ?>
	    <div class="sel-courier" style="display:none">
	        <input type="hidden" class="session_price">
	        <input type="hidden" class="session_selected_courier">
	        <p class="courier-selected">
	            <b>Selected Courier:</b>&nbsp;
	            <i class="courier-data"></i> - <strong class="price-data"></strong>
	        </p>
	        <input type="hidden" class="get_postcode">
	        <input type="hidden" class="get_location">
	        <input type="hidden" class="get_country" id="txt_country" name="txt_country">
	        <input type="hidden" id="locationUrl" value="<?php echo plugins_url('includes/locations.php' , dirname(__FILE__) ); ?>">
	        <p><a onclick="showCalc()" class="link-show-calculator">(change)</a></p>
	    </div>
	    <?php $showShippingCalc = true; ?>
	    <?php  endif; ?>
	    <div class="blockUI" style="display:none"></div>
	    <div class="shipping_calculator" id="trans_frm" style="<?= isset($showShippingCalc) ? '' : 'display:none' ?>">
	        <h4><?php _e($trans_title, 'woocommerce'); ?></h4>
	        <br/>
	        <section class="shipping-calculator-form1">
	            <p class="form-row">
	                <input type="text" name="to_location" id="to_location" placeholder="Enter Postcode, Suburb" autocomplete="off" width="100%" />
	                <input type="text" name="to_postcode" id="to_postcode" placeholder="Enter Postcode" autocomplete="off" width="100%" />
	                <input type="hidden" class="get_location">
	                <input type="hidden" class="get_postcode">
	                <input type="hidden" class="get_country" id="txt_country" name="txt_country">
	                <input type="hidden" id="locationUrl" value="<?php echo plugins_url('includes/locations.php' , __FILE__ ); ?>">
	                <span id="loading-div" style="display:none;"></span>
	                <div id="autocomplete-div"></div>
	            </p>
	            <p class="form-row form-row-wide">
	                <input type="radio" name="to_type" id="business" value="business" <?php if($getTitle->street_type == 'business' ):?> checked="checked"
	                <?php endif; ?>/> Commercial
	                <input type="radio" name="to_type" id="residential" value="residential" <?php if ($getTitle->street_type == 'residential' ): ?> checked="checked"
	                <?php endif; ?>/> Residential
	            </p>
	            <input type="hidden" name="td_value" id="td_value">
	            <p id="btn-get-quote">
	                <button type="button" name="calc_shipping" value="1" class="button calculator btn-warning" onclick="javascript:validate();">
	                    <?php _e('Get a quote', 'woocommerce'); ?>
	                </button>
	            </p>
	            <?php wp_nonce_field('woocommerce-cart'); ?>
	        </section>
	        <div id="shipping_type" style="display:none;">
	            <input type="hidden" name="shipping_variation" id="shipping_variation" value="1" />
	        </div>
	    </div>
	</div>
 <?php endif; ?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		
		// check for transdirect is selected as shipping method or not
		var wcShipping = '<?php echo WC()->session->chosen_shipping_methods[0]; ?>';
		var enabled = '<?php echo $default_values["enabled"]?>';
		if(wcShipping && wcShipping == 'woocommerce_transdirect' && enabled){
			jQuery('.tdCalc').show();
		}else{
			jQuery('.tdCalc').hide();
		}
	
		setTimeout(function(){
			if(jQuery('input.shipping_method').is("input[type='radio']")) {
				if(jQuery('li input:radio.shipping_method:checked').val() == 'woocommerce_transdirect') {
					jQuery('.tdCalc').show();
					if(jQuery('.session_price').val()){
						jQuery('.sel-courier').show();	
					}	
				} else {
					jQuery('.tdCalc').hide();
				}
			} else if(jQuery('input.shipping_method').is("input[type='hidden']")) {
				if(jQuery("input:hidden.shipping_method").val() == 'woocommerce_transdirect') {
					jQuery('.tdCalc').show();
					if(jQuery('.session_price').val()){
						jQuery('.sel-courier').show();	
					}
				} else{
					jQuery('.tdCalc').hide();
				}
			} else {
				jQuery('.tdCalc').hide();
			}
		}, 6000);

		jQuery( document ).on( 'change', 'input.shipping_method, input[name^=shipping_method]', function() {
			var context = this;
			var paymentInterval = setInterval(function () {
				if (!jQuery('#payment .blockUI').length) {
					if(jQuery(context).val() == 'woocommerce_transdirect') {
						jQuery('.tdCalc').show();
						if(jQuery('.session_price').val()){
							jQuery('.sel-courier').show();	
						}
					} else{
						jQuery('.tdCalc').hide();
					}
					clearInterval(paymentInterval);
				}
			}, 100);
		});

		jQuery(document).on('click', 'span.country-name,ul.country-list li', function(){
			var countryData = jQuery("#to_location").countrySelect("getSelectedCountryData");
		    if(countryData)
		    {	
		        jQuery("#to_location").val(countryData.name);
		        jQuery("#txt_country").val(countryData.iso2);
		        if(countryData.postcode == 1){
		        	jQuery("#to_postcode").show();
		        } else {
		        	jQuery("#to_postcode").hide();
		        }
		    }
		});

		setTimeout(function(){
			jQuery("#to_postcode").hide();
	 		var locationUrl = "<?php echo plugins_url('includes/locations.php' ,  dirname(__FILE__) ); ?>"; 
			jQuery.getJSON(locationUrl, {'isInternational' : 'yes'},function(data) {
				var data =jQuery.map(data, function(el) { return el });
	            var results =[];
	            for (var i = 0; i < data.length; i++) {
	                if(data[i].name != 'Croatia')
	                {
	                    results.push({ 
	                    	"iso2"     : data[i].code.toLowerCase(),
	                        "name"     : data[i].name,
	                        "id"       : data[i].id,
	                        "postcode" : data[i].postcode_status
	                    });
	                }
	            }
	            jQuery.fn.countrySelect.setCountryData(results);
				jQuery("body #to_location").countrySelect({
		            defaultCountry: 'au',
		            preferredCountries: ['au', 'cn', 'jp', 'nz', 'sg', 'gb', 'us']
		        });
		        var allCountryData = jQuery.fn.countrySelect.getCountryData();
		        jQuery(allCountryData).each(function( index ) {
		          if(jQuery(this)[0].iso2 == 'au') {
		            jQuery('*[data-country-code="au"]').css('pointer-events','none');
		          }
		        });
			});
		}, 7000);

		setTimeout(function(){
			if(jQuery("body #to_location").val() == 'Australia'){
				jQuery("body #to_location").val('');
				jQuery("#to_postcode").hide();
			}
		}, 7000);
	});
</script>