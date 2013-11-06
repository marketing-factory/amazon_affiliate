<!--
##### PRODUCT PLUGIN ############
## all configured TypoScript fields (plugin.tx_amazonaffiliate_piproducts.productListing.fields)
## are mapped to a marker in the template
## The TypoScript fields support stdWrap so you can
## create a marker for everthing what is possible in TypoScript
##
## NOTICE! The markers are uppercase and you have to add "PRODUCT_" in front of the marker name!
#################################
-->
<!-- ###PRODUCTS### begin -->
<div class="amazon_affiliate_products">
	###PRODUCT_ITEMS###
	<div class="amazon_affiliate_product">
		<h2>###PRODUCT_TITLE###</h2>
		<div class="mediumImage">###PRODUCT_MEDIUMIMAGE###</div>
		###PRODUCT_CONTENT###
	</div>
	###PRODUCT_ITEMS###
</div>
<!-- ###PRODUCTS### end -->