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
	<div class="amazon_affiliate_product">
		<h2>###PRODUCT_TITLE###</h2>
		<div class="mediumImage">###PRODUCT_MEDIUMIMAGE###</div>
		###PRODUCT_CONTENT###
	</div>
	###PRODUCT_ITEMS###
</div>
###PRODUCTS###




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