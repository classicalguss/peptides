# Powered Up Peptides — Admin Guide

This guide explains how to run the store day-to-day from the admin panel, without needing a developer. It covers everything the admin panel can do, organized by what you're trying to accomplish.

**Admin panel:** `https://powereduppeptides.com/lunar`
*(Login details are not included in this document — ask whoever set up your account, or use "Forgot password" on the login screen.)*

---

## 1. Finding your way around

The left-hand menu is organized into four groups:

- **Catalog** — Products, Brands, Collection Groups (this is where "Research Collections" and shop categories live)
- **Sales** — Orders, Customers, Discounts
- **Website** — Website Text (see section 3 — this is the most important screen for day-to-day wording changes)
- **Settings** — Staff accounts, taxes, currencies, and other store configuration

### The search box

Click the search icon (or press `Ctrl/Cmd + K`) at any time and type a word or phrase. It searches:

- Product names and descriptions
- Every editable text field on every product and collection page
- Every shared site-text entry (navigation, footer, buttons, disclaimers)

Each result tells you **which section of which screen** the text lives on and links you straight there. If you've ever seen a sentence on the live site and wondered "where do I even change that?" — search for a few words from it here first, before going hunting through menus.

---

## 2. Products and collections

### Individual compounds vs. Research Collections

The store has two kinds of product:

- **Individual compounds** — a single peptide/material sold as its own vial (e.g. "BPC-157 20mg")
- **Research Collections** — a bundle of several compounds sold together in a few different quantity sizes (e.g. "BPC-157 + TB-500 Research Collection")

Both are edited from the same place: **Catalog → Products**, then click the product name.

### Changing a price or stock level

1. Catalog → Products → open the product
2. Go to the **Variants** tab, click a variant, then **Pricing** or **Inventory**
3. Save, then check the live page

Note: the *price of each Research Collection size* ("HP Core", "Z Plus", "S Max") is not yet editable from this screen — that's still a developer task. The **Collection Sizes** section on a collection's edit page (see below) only lets you rename the sizes, not reprice them.

### Changing a product image

1. Catalog → Products → open the product → **Media** tab
2. Upload the new image and set it as the main image if needed
3. Save

### Editing the wording on a product's own page

Every product's edit page has a **Website Page Text** section (scroll down past the standard fields). This covers:

- The short label shown above the product/collection name
- The main description
- Research background, storage & handling
- Highlights list
- FAQs

Changes here only affect that one product's page.

### Editing a Research Collection's "What's Included" table

On a Research Collection's edit page, scroll to **What's Included table**. You can change how many vials of each compound are included at the base size (the tiered sizes scale automatically from this). The text description of each row comes from that compound's own "Short description" field (edited on the compound's own product page) — this keeps the description consistent everywhere that compound appears, so you only ever update it in one place.

### Editing a Research Collection's size names

Also on the collection's edit page: **Collection Sizes**. This is where "HP", "Core", "Z", "Plus", "S", "Max" (the short code and full name for each size) are set.

### Renaming a shop category, or moving products between categories

Categories (the filter buttons on the Shop page — currently "Peptides" and "Laboratory Supplies") are **Collections** in Lunar terminology, not the same as "Research Collections" above.

1. Catalog → Collection Groups → **Main**
2. This shows the full tree of collections, including the shop categories
3. Click a collection to rename it, change its products, or update its image

A collection only appears as a filter on the Shop page if it has at least one product in it — if you empty a category out, it disappears from the filter bar automatically.

### Product Types, Brands

**Catalog → Products** — when creating or editing a product, its Product Type and Brand are set from dropdowns on the main edit screen. To rename a Product Type itself or manage the Brand list as a whole, go to the corresponding item under Catalog.

---

## 3. Website Text — the fastest way to change wording

**Website → Website Text** is a list of roughly 130 short pieces of copy that appear across the site: navigation labels, the top announcement bar, trust-bar promises, footer text, disclaimers, button labels, page headings, and the entry pop-up shown to first-time visitors.

Each row shows:
- **Page** and **Section** — where on the site it appears
- **Label** — a plain-English description of what it is
- **Value** — the current text (click to edit)

This is almost always faster than asking a developer, and it's the first place to check for any text that isn't specific to one product.

---

## 4. Orders

**Sales → Orders**

- New orders appear as **Awaiting Payment** — the website does not process payment automatically; confirm payment was received through your payment method separately.
- Once confirmed, update the order status to **Payment Received**.
- Only mark an order **Dispatched** after it has actually shipped.
- Click into any order to see the customer's details, items, and totals.

**Do not** mark an order as paid or dispatched before that has actually happened.

---

## 5. Customers

**Sales → Customers** — look up a customer's contact details and order history. Customer accounts are created automatically when someone checks out or registers; there's no need to create them manually.

---

## 6. Discounts

**Sales → Discounts → New Discount**

- Give every discount a clear end date and a usage limit.
- Test a new code in the shopping cart yourself before sharing it with anyone.
- Discounts can be scoped to specific products, collections, or customer groups from the **Availability** tab.

---

## 7. Lab reports / certificates of analysis (COAs)

Certificate PDFs and batch data (batch number, test date, lab name) are not yet editable from a dedicated admin screen — this remains a developer task for now. Send new certificates, batch numbers, and test dates to your developer to have them added.

Note: purity percentages are currently hidden across the site because the certificate data on file is placeholder/sample data. Once real, verified certificates are uploaded, purity figures can be re-enabled per product.

---

## 8. Staff accounts and security

**Settings → Staff**

- **New Staff** creates another admin login.
- **Access Control** lets you restrict what a staff account can see or change (useful if you bring on a part-time helper who should only manage orders, for example).
- To change a password, open that staff member's edit screen — there's a **Reset password** field right on the form.
- Two-factor authentication can be turned on per account; if you don't see the option on the staff edit screen, check the account menu (top right, once logged in as that user) — strongly recommended for the account with full access.

---

## 9. What not to touch

A few areas are easy to break and rarely need changing. Leave these alone unless a developer asks you to touch them:

- **Taxes, currencies, and channels** (Settings) — store-wide configuration, not per-product settings
- **SKU codes, Product Type, and tax class** on individual products — changing these can affect pricing and order history
- **Deleting** products, collections, staff accounts, or orders — hide/deactivate instead if you need something to stop appearing

If a screen looks unfamiliar or technical, it's safer to stop and ask than to guess.

---

## 10. When you're not sure

1. **Search first** (section 1) — most wording questions are answered by searching for the phrase.
2. If you can't find something or a change doesn't look right after saving, take a screenshot and send it to your developer rather than experimenting further.

---

*This guide is maintained in the project repository alongside the code, so it stays up to date as the admin panel grows.*
