# Powered Up Peptides — Admin Guide

This guide explains how to run the store day-to-day from the admin panel, without needing a developer. It covers everything the admin panel can do, organized by what you're trying to accomplish.

**Admin panel:** `https://powereduppeptides.com/lunar`
*(Login details are not included in this document — ask whoever set up your account, or use "Forgot password" on the login screen.)*

---

## 1. Finding your way around

The left-hand menu is organized into four groups:

- **Catalog** — Products, Brands, Collection Groups (this is where "Research Collections" and shop categories live)
- **Sales** — Orders, Customers, Discounts
- **Website** — Website Text (section 3), Website Lists (section 6b), and Lab Reports (COAs) (section 7) — the screens for day-to-day wording, list, and certificate changes
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

This works the same for Research Collections: each size ("HP Core", "Z Plus", "S Max") is a variant, and its price in **Variants → Pricing** is what the collection page shows and what the cart charges. The "save X%" figures are calculated automatically from the component prices.

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

Changes here only affect that one product's page. (The checkmark lines under the add-to-cart button are shared across products — see Website Lists.)

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

**Website → Website Text** is a list of roughly 160 pieces of copy that appear across the site: navigation labels, the top announcement bar, trust-bar promises, footer text, disclaimers, button labels, page headings, and the entry pop-up shown to first-time visitors.

Each row shows:
- **Page** and **Section** — where on the site it appears
- **Label** — a plain-English description of what it is
- **Value** — the current text (click to edit)

This is almost always faster than asking a developer, and it's the first place to check for any text that isn't specific to one product. Every piece of website text of four words or more is editable here or in Website Lists; on the edit screen, **Preview Page** opens the live page and **Restore Original Text** puts the original wording back.

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

## 6b. Website Lists (repeating content)

**Website → Website Lists**

Some sections are lists rather than single pieces of text: the FAQ on the Contact page, the trust-bar promises, the "How It Works" steps and "Commitments" on the About page, the homepage trust cards, and the checkmark lines under add-to-cart and checkout buttons. Each list has its own page here, grouped by the website page it appears on, so you can change *how many* items there are — not just their wording.

On a list's page:

- Each item is a card. Click a card's title bar to collapse or expand it.
- **Add item** adds a new card at the bottom; the trash icon removes one.
- Use the **up / down arrows** on a card to change the order.
- Press **Save changes** — the website updates immediately, numbered steps renumber themselves, and the **Preview Page** button shows the result.

---

## 7. Lab reports / certificates of analysis (COAs)

**Website → Lab Reports (COAs)**

Each product has one row holding its current batch: batch number, analysis date, laboratory, HPLC purity, testing status, and the certificate PDF. This feeds both the public **Lab Reports** page and the **Current Batch** box on each product page, so they always match.

When a new batch is tested:

1. Open the product's row and click **Edit**.
2. Update the batch number, analysis date, purity, and laboratory to match the new certificate.
3. Upload the new certificate PDF (this replaces the one linked on the website).
4. Keep **Publication status** on **Pass** and save.

The **Publication status** field controls what visitors see:

- **Pass**: shows all batch details, the purity figure, a PASS badge, and the View COA button.
- **Not published**: hides all of that and shows your own status pill instead — click it to type the wording and pick one of the preset colours — plus an optional note underneath. Use this for a batch being retested ("Additional Testing in Progress"), one awaiting paperwork ("Documentation Pending"), or one that failed ("Did Not Pass").

Never enter estimated or placeholder values — if a result isn't confirmed by a certificate in hand, use "Not published" with an honest status message instead.

**Adding a brand-new product:** click **Add product batch**, pick the product (only compounds without a batch record are listed — research collections show their components' batches automatically), and fill in the same fields. The Lab Reports page and the product's Current Batch box pick it up immediately — no developer needed.

The **Name as printed on the certificate** field is how the product is listed on the Lab Reports page; it is pre-filled from the product name and can differ from the shop listing (for example "CJC-1295 / Ipamorelin 20mg").

---

## 8. Staff accounts and security

**Settings → Staff**

- **New Staff** creates another admin login.
- **Access Control** lets you restrict what a staff account can see or change (useful if you bring on a part-time helper who should only manage orders, for example).
- To change a password, open that staff member's edit screen — there's a **Reset password** field right on the form.
- Two-factor authentication can be turned on per account; if you don't see the option on the staff edit screen, check the account menu (top right, once logged in as that user) — strongly recommended for the account with full access.

---

## 9. Take extra care

Everything in the admin is yours to change. A few settings simply have wider effects than they look, so it's worth pausing before editing them:

- **Taxes, currencies, and channels** (Settings) — store-wide configuration that affects every price and checkout
- **Tax class** on individual products — changes how that product is taxed at checkout
- **Orders** — keep them rather than deleting; they are your sales records

If a screen looks unfamiliar or technical, it's safer to stop and ask than to guess.

---

## 10. When you're not sure

1. **Search first** (section 1) — most wording questions are answered by searching for the phrase.
2. If you can't find something or a change doesn't look right after saving, take a screenshot and send it to your developer rather than experimenting further.

---

*This guide is maintained in the project repository alongside the code, so it stays up to date as the admin panel grows.*
