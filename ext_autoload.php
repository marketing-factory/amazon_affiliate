<?php
$extensionPath = t3lib_extMgm::extPath('amazon_affiliate');


return array(
	'tx_amazonaffiliate_asineval' => $extensionPath . 'hooks/class.tx_amazonaffiliate_asineval.php',
	'tx_amazonaffiliate_dmhooks' => $extensionPath . 'hooks/class.tx_amazonaffiliate_dmhooks.php',
	'tx_amazonaffiliate_linkhandler' => $extensionPath . 'hooks/class.tx_amazonaffiliate_linkhandler.php',
	'tx_amazonaffiliate_amazonecs' => $extensionPath . 'lib/class.tx_amazonaffiliate_amazonecs.php',
	'tx_amazonaffiliate_product' => $extensionPath . 'lib/class.tx_amazonaffiliate_product.php',
	'tx_amazonaffiliate_updatestatus' => $extensionPath . 'scheduler/class.tx_amazonaffiliate_updatestatus.php',
	'tx_amazonaffiliate_renderhooks' => $extensionPath . 'hooks/class.tx_amazonaffiliate_renderhooks.php',
	'amazonecs' => $extensionPath . 'res/AmazonEcs.php',
);

?>