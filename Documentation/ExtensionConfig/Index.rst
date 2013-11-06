.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt
.. include:: Images.txt

Getting started
===============

.. contents::
   :local:
   :depth: 1


Register on amazon partnernet
-----------------------------
Before starting with the amazon affiliate extension, you'll need your own communication credentials for the amazon webservice.
In order to get login credentials for amazon webservice you can create an account under:
https://partnernet.amazon.de/gp/advertising/api/detail/main.html


Install and activate the extension
----------------------------------

Install and activate the amazon_affiliate extension.


Extension Manager Configuration
-------------------------------

|img-3| *Abb. 1: extension configuration*

After your successful registration on amazon partnernet and activation of the extension, you must enter your secret_key, access_key and
associate_tag in the respective fields in the extension manager configuration. If you use amazon.de as an affiliate you don't need
to change anything else. Otherwise you must change the country to fit your needs.

The field minimumAsinlistCount is the minimum number of ASINs for the book and product listing plugin. If fewer than
'minimumAsinlistCount' ASIN products in a plugin are available, you'll see an error in the backend module and the plugin element
can't be changed until the minimum count of available ASIN numbers in the plugin element is reached. The plugin checks the
availability of the products while saving the content element.

The field displayLimit sets the global limit for the Topseller and SearchAndAdd plugin listings. In each plugin you can also
overwrite this limit for that plugin element. If nothing is set, the plugin will return 10 products.


Add static template
--------------------

In order to use the predefined settings, you have to include the static template.


