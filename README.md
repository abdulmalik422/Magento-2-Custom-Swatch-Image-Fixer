# Magento 2 Swatch Image Fix (Custom Filter Swatch Image)

## Overview

In standard Magento 2 functionality, layered navigation allows customers to filter products by configurable attributes such as `color`. When a user selects a color filter (e.g., "Red"), Magento automatically updates the product listing images (category view) to display the specific child product variant image that matches the selected filter option.

However, websites with extensive color palettes often run into usability issues when displaying every single color in layered navigation. A common solution is creating a custom grouped attribute, such as **`color_family`** (e.g., grouping "Crimson", "Burgundy", and "Rose" under "Red"). 

By default, Magento 2 does not recognize custom attributes like `color_family` for swatch image switching in product listings, causing the store to display the default parent product image instead of the matching child variant image.

This module resolves this limitation. It extends the core listing image plugin logic to map custom grouping attributes (like `color_family`) to their corresponding configurable child product options, ensuring that selecting a custom filter updates the catalog listing with the correct child product variant image.

---

## Features

- **Custom Swatch Image Switcher:** Ensures child product variant images are displayed in layered navigation search/category results when filtering by custom attributes like `color_family`.
- **Seamless Layered Navigation:** Enhances user experience by accurately reflecting the filtered color family visually on the catalog page.
- **Lightweight & Plug-and-Play:** Uses Magento's native plugin architectural pattern (`di.xml`) without altering core database tables.

---

## Technical Architecture

The module intercepts the product listing image generation process using a frontend plugin targeting Magento's configurable swatch image logic.

### File Structure

```text
SwatchImage/
├── Plugin/
│   └── ReplaceListingImageByFilter.php
├── etc/
│   ├── frontend/
│   │   └── di.xml
│   └── module.xml
└── registration.php
```

### Key Components

1. **`etc/module.xml`**: Declares the module `Vendor_SwatchImage` and its dependency on `Magento_ConfigurableProduct` and `Magento_Swatches`.
2. **`etc/frontend/di.xml`**: Registers the plugin `ReplaceListingImageByFilter` around the catalog listing product image rendering.
3. **`Plugin/ReplaceListingImageByFilter.php`**: Intercepts the request parameters to detect custom filter selections (`color_family`) and maps them to child product images.

---

## Installation

### Prerequisites

- **Magento:** 2.3.x or 2.4.x
- **PHP:** Compatible with your installed Magento version

### Step-by-Step Installation

1. **Upload Module:**
   Upload or place the `SwatchImage` code under your Magento root directory at:
   ```text
   app/code/Vendor/SwatchImage/
   ```

2. **Enable Module:**
   Open your CLI / Terminal, navigate to the Magento root directory, and run the following commands:

   ```bash
   php bin/magento module:enable Vendor_SwatchImage
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy -f
   php bin/magento cache:flush
   ```

---

## Configuration & Usage

1. **Create/Configure Custom Attribute:**
   - Go to **Stores > Attributes > Product**.
   - Ensure your custom attribute (e.g., `color_family`) is created and assigned to your configurable product attribute set.
   - Set **Use in Layered Navigation** to *Filterable (with results)* or *Filterable (no results)*.
   - Set **Used in Product Listing** to *Yes*.

2. **Assign Attributes to Products:**
   - Assign the `color_family` values to your simple (child) products under their respective configurable parent.

3. **Verify Frontend Behavior:**
   - Navigate to a catalog page on the frontend.
   - Select an option from the `color_family` filter in the Layered Navigation.
   - The product card images in the grid will update to display the child product variant image matching the selected color family.

---

## Troubleshooting

- **Image not updating after filter selection:**
  - Ensure the child products have an image assigned (Base/Small/Thumbnail).
  - Clear the Magento cache (`php bin/magento cache:flush`).
  - Reindex the catalog target: `php bin/magento indexer:reindex catalog_product_attribute catalogsearch_fulltext`.

---

## License

This project is open-source and available under the [MIT License](LICENSE).