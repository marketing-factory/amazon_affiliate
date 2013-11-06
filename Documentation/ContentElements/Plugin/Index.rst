.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt
.. include:: Images.txt



Use as plugin
=============

There are two possible plugin modes:

.. contents::
   :local:
   :depth: 1


|img-7| *Abb. 1: choose your favorite plugin type in backend*

ASIN based plugins
------------------

Product listing
~~~~~~~~~~~~~~~~
Add your favorite products by entering their ASINs in the ASINlist field. While saving, the ASINs will be verified. If all products are valid,
you'll see the default template with an image, the title, the description and the price of every product in the frontend.

|img-9| *Abb. 2: possible frontend view for product listing*



Book listing
~~~~~~~~~~~~~
Similar to the product view, the input for the book listing also requires ASINs. The only difference between products and books is the frontend
rendering. The default template gives the following values back:

- image

- title

- author (if set)

- description (if set)

- price

- publication date

|img-10| *Abb. 3: possible frontend view for book listing*


Keyword/Category based plugins
------------------------------
|img-8| *Abb. 4: SearchAndAdd or Bestseller plugin*

SearchAndAdd
~~~~~~~~~~~~~~~~~~
The only input necessary for the SearchAndAdd plugin is a search keyword. This is similar to the normal web search for www.amazon.de,
the same products are returned for the keyword.
Optional filters are BrowseNode and Sub BrowseNode for the (sub)category selection. With the display limit, you have the possibility to reduce
the output to a certain number of products. A limit higher than 10 is not possible, because the maximal number of products is 10.


Bestsellers
~~~~~~~~~~~~~~~~~~
The bestseller plugin has no required fields, but it is recommended that you restrict the searching category.
The module delivers the topselling product from the selected category. Similar to the SearchAndAdd module you also have the
possibility to limit the product count for this plugin. The plugin has no keyword field.

|img-11| *Abb. 4: the output of SearchAndAdd or Bestseller plugin is similar to the product listing*