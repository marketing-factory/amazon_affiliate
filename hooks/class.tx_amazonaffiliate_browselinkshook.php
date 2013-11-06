<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2011 Sascha Egerer <info@sascha-egerer.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class tx_amazonaffiliate_browselinkshook implements t3lib_browseLinksHook {

	/**
	 * @var object $pObj
	 */
	protected $pObj;

	/**
	 * @var bool $amazonLinkHover
	 */
	protected $amazonLinkHover;

	/**
	* initializes the hook object
	*
	* @param browse_links $parentObject parent browse_links object
	* @param array $additionalParameters additional parameters
	* @return void
	*/
	public function init($parentObject, $additionalParameters) {

		$this->pObj = $parentObject;

		if (strstr('amazonLinkHover', $this->pObj->P['currentValue'])) {
			$this->pObj->P['currentValue'] = str_replace('amazonLinkHover', '', $this->pObj->P['currentValue']);

			$this->amazonLinkHover = TRUE;
		}
	}

	/**
	* adds new items to the currently allowed ones and returns them
	*
	* @param array $currentlyAllowedItems currently allowed items
	* @return array currently allowed items plus added items
	*/
	public function addAllowedItems($currentlyAllowedItems) {
			// we need only the amazonaffiliate linkhandler if it is the "tx_amazonaffiliate_amazon_asin" field
		if ($this->pObj->P['field'] == 'tx_amazonaffiliate_amazon_asin') {
			$currentlyAllowedItems = array();
		}
		$currentlyAllowedItems[] = 'amazon_affiliate';

		return $currentlyAllowedItems;
	}

	/**
	* modifies the menu definition and returns it
	*
	* @param array $menuDefinition menu definition
	* @return array $menuDefinition modified menu definition
	*/
	public function modifyMenuDefinition($menuDefinition) {
		$key = 'amazon_affiliate';

		$menuDefinition[$key]['isActive'] = $this->pObj->act == $key;
		$menuDefinition[$key]['label'] = 'Amazon Affiliate';
		$menuDefinition[$key]['url'] = '#';
		$menuDefinition[$key]['addParams'] = 'onclick="jumpToUrl(\'?act=' . $key . '&editorNo=' . $this->pObj->editorNo . '&contentTypo3Language=' . $this->pObj->contentTypo3Language . '&contentTypo3Charset=' . $this->pObj->contentTypo3Charset . '\');return false;"';

		return $menuDefinition;
	}

	/**
	* returns a new tab for the browse links wizard
	* @param string $linkSelectorAction current link selector action
	* @return string $content a tab for the selected link action
	*/
	public function getTab($linkSelectorAction) {
		$content = '';
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		if ($linkSelectorAction == 'amazon_affiliate') {
				// strip http://amazonaffiliate: in front of url
			if (stripos($this->pObj->curUrlInfo['value'], 'amazonaffiliate:') !== FALSE) {
				$value = substr(
					$this->pObj->curUrlInfo['value'],
					stripos($this->pObj->curUrlInfo['value'], 'amazonaffiliate:') + strlen('amazonaffiliate:')
				);
			} elseif (stripos($this->pObj->curUrlInfo['value'], 'http://') !== FALSE) {
				$value = substr(
					$this->pObj->curUrlInfo['value'], stripos($this->pObj->curUrlInfo['value'], 'http://') + strlen('http://')
				);
			} else {
				$value = $this->pObj->curUrlInfo['value'];
			}
			$valueArray = explode('|', $value);

			$url = $valueArray[0];
			$amazonLinkHover = $valueArray[1];

			if ($this->pObj->P['field'] == 'tx_amazonaffiliate_amazon_asin') {
					// if it is the 'tx_amazonaffiliate_amazon_asin' field do not add the prefix
				$prefix = '';
			} else {
				$prefix = '"amazonaffiliate:"+';
			}

			$content = '
						<script type="text/javascript">
							function link_asin(asin, hover) {
								var urlValue = asin;

								if(hover) {
									urlValue = urlValue + "|1";
								}
							' . ($this->isRTE() ?
					'browse_links_setHref(urlValue); browse_links_setAdditionalValue(\'external\', \'\'); return link_current();' :
					'return link_folder(urlValue);') . '

							}

						</script>
								<form action="" name="lurlform" id="lurlform">
									<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
										<tr>
											<td style="width: 96px;">ASIN:</td>
											<td><input type="text" name="asin"' . $this->pObj->doc->formWidth(30) .
												' value="' . htmlspecialchars($url) . '" /><input type="submit" value="' .
												$language->getLL('setLink', 1) . '" onclick="return link_asin(' . $prefix .
												'document.lurlform.asin.value, document.lurlform.amazonLinkHover.checked);" /></td>
										</tr>
										<tr>
											<td style="width: 96px;">Hover:</td>
											<td><input type="checkbox" name="amazonLinkHover" value="1" ' .
												($amazonLinkHover == 1 ? 'checked="checked"' : '') . ' /></td>
										</tr>
									</table>
								</form>';
			if ($this->isRTE()) {
				$content .= $this->pObj->addAttributesForm();
			}
		}

		return $content;
	}

	/**
	* Returns true if the field is a RTE
	* @return bool
	*/
	private function isRTE() {
		if ($this->pObj->mode == 'rte') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	* checks the current URL and determines what to do
	*
	* @param string $href
	* @param string $siteUrl
	* @param array $info
	* @return array
	*/
	public function parseCurrentUrl($href, $siteUrl, $info) {
			// depending on link and setup the href string can contain complete absolute link
		if (substr($href, 0, 7) == 'http://') {
			if ($_href = strstr($href, '?id=')) {
				$href = substr($_href, 4);
			} else {
				$href = substr(strrchr($href, '/'), 1);
			}
		}

		if (strtolower(substr($href, 0, 15)) == 'amazonaffiliate') {
			t3lib_div::_GETset('amazon_affiliate', 'act');
			$info['act'] = 'amazon_affiliate';
		}

		return $info;
	}
}

?>