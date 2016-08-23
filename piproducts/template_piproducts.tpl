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
###PRODUCTS###
<div class="amazon_affiliate_products">
	###PRODUCT_ITEMS###
	<div class="amazon_affiliate_product affiliate_product_box_contrast">
		<div class="amazonProduct">
			<div class="amazonPix ratioBox">

				<div class="ratioBoxImageWrap">
					###PRODUCT_MEDIUMIMAGE###
				</div>
			</div>
			<div class="amazonText">
				<p class="bodytext">
					###PRODUCT_TITLE###
				</p>
				###PRODUCT_SAVEDPERCENT###
				<p class="bodytext">###PRODUCT_CONTENT###</p>
				<p class="bodytext">
					###PRODUCT_PRICE### ###PRODUCT_PRICEOLD### ###PRODUCT_PRICENEW###
				</p>
			</div>
			<br/>

			<a class="productlink" target="_blank" href="###PRODUCT_BUTTONURL###" title="Jetzt kaufen bei amazon.de">
				Jetzt kaufen bei amazon.de
			</a>

		</div>
	</div>
	###PRODUCT_ITEMS###
</div>
###PRODUCTS###


###BOOKS###
<div class="amazon_affiliate_products">
	###PRODUCT_ITEMS###
	<div class="amazon_affiliate_product affiliate_product_box_contrast">
		<div class="amazonProduct ">
			<div class="amazonPix">
				<div class="ratioBoxImageWrap">
					###PRODUCT_MEDIUMIMAGE###
				</div>
			</div>
			<div class="amazonText">
				<p class="bodytext">
					###PRODUCT_TITLE###
				</p>
				###PRODUCT_SAVEDPERCENT###
				<p class="bodytext">
					###PRODUCT_AUTHOR###
					###PRODUCT_PRICE### ###PRODUCT_PRICEOLD### ###PRODUCT_PRICENEW###
					###PRODUCT_DATE###
					###PRODUCT_ISBN###
				</p>
				<p class="bodytext">###PRODUCT_CONTENT###</p>
			</div>
			<br/>

			<a class="booklink" target="_blank" href="###PRODUCT_BUTTONURL###">
				Jetzt kaufen bei amazon.de
			</a>
		</div>
	</div>
	###PRODUCT_ITEMS###
</div>
###BOOKS###




<!--
##### WIDGET PLUGIN #############
## Use "_SUB" after the markername for a suppart wich will be empty if no value is given
## Available markers:
## ASSOCIATE_TAG,WIDGET_TYPE,SEARCH_INDEX,BROWSE_NODE,TITLE,WIDTH,HEIGHT,MARKET_PLACE,ASINLIST,KEYWORDS
#################################
-->
###WIDGET###
<script type='text/javascript'>
	var amzn_wdgt={widget:'Carousel'};
	amzn_wdgt.tag='###ASSOCIATE_TAG###';
	amzn_wdgt.widgetType='###WIDGET_TYPE###';
	###SEARCH_INDEX_SUB###amzn_wdgt.searchIndex='###SEARCH_INDEX###';###SEARCH_INDEX_SUB###
	###BROWSE_NODE_SUB###amzn_wdgt.browseNode='###BROWSE_NODE###';###BROWSE_NODE_SUB###
	amzn_wdgt.title='###TITLE###';
	amzn_wdgt.width='###WIDTH###';
	amzn_wdgt.height='###HEIGHT###';
	amzn_wdgt.marketPlace='###MARKET_PLACE###';
	###ASINLIST_SUB###amzn_wdgt.ASIN='###ASINLIST###';###ASINLIST_SUB###
	###KEYWORDS_SUB###amzn_wdgt.keywords='###KEYWORDS###';###KEYWORDS_SUB###
	amzn_wdgt.maxResults='20';

</script>
<script type='text/javascript' src='http://wms.assoc-amazon.de/20070822/DE/js/swfobject_1_5.js'></script>
###WIDGET###
