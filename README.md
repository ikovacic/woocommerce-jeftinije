# WooCommerce Jeftinije.hr, Ceneje.si, Idealno.rs and Idealno.ba

WooCommerce XML template for Jeftinije.hr, Ceneje.si, Idealno.rs and Idealno.ba according to https://www.jeftinije.hr/xml-specifikacije


## Usage

1. Download template page-jeftinije.php
2. Upload template to your theme folder (wp-content/themes/your-theme/)
3. Create page with URL slug jeftinije
4. Adjust config (delivery price, currency, attributes to skip, etc.)

That's it, your XML feed will be available at yourdomain.com/jeftinije


## Support

Feel free to contact me at hello@applause.hr for:

- **Free** basic support (within 3 days)
- **Paid** customizations (depending on specific task)


## Next steps

- Rewrite and optimization
- Admin interface


## Update 24.3.2021.

- Added support to exclude products by comma separated list of IDs
- Added support to chunk huge XML files, optional (page 1: /jeftinije/?limit=500&offset=0, page 2: /jeftinije/?limit=500&offset=500, ...)


## Update 2.1.2021.

- Added support for variable products
- Added support for EAN (it's possible to use EAN as attribute or custom meta field)
- Added support for Brand (it's possible to use Brand as attribute or custom taxonomy)
- Small fixes for attribute values
- Small fixes for stockText attribute


## Update 21.4.2020.

- Configuration array at the beginning of page-jeftinije.php
- Support for id or sku in ID node
- Option to ignore product's quantity for stock node
