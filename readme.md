# FluentCRM Tag to Woo Membership

Automatically add or remove **WooCommerce Memberships** memberships based on **FluentCRM tags**.

This plugin is intended for WordPress site administrators who use FluentCRM to control access to WooCommerce Memberships plans.

## What It Does

You create simple rules that connect a FluentCRM tag to a WooCommerce Memberships plan.

For example:

| FluentCRM Tag | WooCommerce Membership Plan |
| --- | --- |
| Nexus Member | Aspen Nexus Foundation |
| Training Access | Training Library |

When a mapped FluentCRM tag is **added** to a contact, the plugin enrolls that contact's linked WordPress user in the mapped membership plan.

When the mapped tag is **removed**, the plugin removes that user's mapped membership.

## Requirements

Your site must have:

- WordPress
- WooCommerce
- WooCommerce Memberships
- FluentCRM
- FluentCRM contacts linked to WordPress user accounts

The plugin only acts on contacts that have a WordPress user ID. A FluentCRM contact that is not connected to a WordPress user will be ignored.

## Installation

1. Download the plugin files.
2. Place them in a folder named `fluentcrm-tag-to-woo-membership` inside `wp-content/plugins/`.
3. In WordPress, go to **Plugins**.
4. Activate **FluentCRM Tag to Woo Membership**.

## Configure Membership Rules

After activation:

1. In WordPress admin, go to **WooCommerce → FluentCRM Membership Rules**.
2. Choose a **FluentCRM Tag**.
3. Choose the **Membership Plan** that tag should control.
4. Leave **Active** checked.
5. Click **Save Rules**.

After saving, another blank row is added so you can create additional mappings.

To remove a rule, leave either dropdown blank and save.

To temporarily stop a rule without deleting it, uncheck **Active** and save.

## How Memberships Are Handled

### When a tag is added

If the user does not already have the mapped membership, the plugin creates it.

If the user already has the membership, the plugin sets its status to **Active**.

### When a tag is removed

The plugin permanently deletes the mapped WooCommerce user membership record.

This is different from merely pausing, expiring, or cancelling a membership.

## Important Behavior and Limitations

### Changes are event-based

The plugin responds when FluentCRM reports that a tag has been **added** or **removed**.

Creating a new mapping does **not** automatically scan your existing contacts and synchronize memberships they should already have. If a contact already has the tag when you create the rule, no membership change occurs until that tag is added or removed again.

### Membership removal is destructive

Removing a mapped FluentCRM tag permanently deletes the corresponding WooCommerce membership record.

If that membership was originally granted manually, through a purchase, through another automation, or through some other process, the plugin does not distinguish the source. A matching tag-removal event will still remove the membership.

### Avoid multiple rules that independently control the same membership unless that behavior is intentional

The plugin evaluates each tag event independently.

For example, if both **Tag A** and **Tag B** map to the same membership plan, removing **Tag A** can remove the membership even if the user still has **Tag B**.

For predictable behavior, use one authoritative FluentCRM tag for each membership plan unless you specifically understand and want the interaction between multiple rules.

### No automatic historical reconciliation

The plugin does not periodically compare all FluentCRM tags against all WooCommerce memberships. It only reacts to supported FluentCRM tag-added and tag-removed events.

## Example Workflow

Suppose you create this rule:

**FluentCRM Tag:** `Active Nexus Subscriber`  
**Membership Plan:** `Nexus Membership`

Then:

- Adding `Active Nexus Subscriber` to a linked FluentCRM contact creates or activates that user's `Nexus Membership`.
- Removing `Active Nexus Subscriber` permanently removes that user's `Nexus Membership`.

This makes the FluentCRM tag the effective on/off switch for that membership.

## Troubleshooting

### I added a rule, but existing tagged contacts did not receive memberships

That is expected. Rules are not retroactive. The plugin acts when FluentCRM fires a tag-added or tag-removed event.

### A contact has the correct tag, but nothing happens

Confirm that:

- the FluentCRM contact is linked to a WordPress user;
- WooCommerce Memberships is active;
- the rule is marked **Active**;
- the correct FluentCRM tag is selected;
- the correct membership plan is selected.

### The rule dropdowns are empty

The plugin reads FluentCRM tags and WooCommerce Memberships plans directly from WordPress.

Create at least one FluentCRM tag and one WooCommerce Memberships plan first, then return to **WooCommerce → FluentCRM Membership Rules**.

## Access

Only WordPress administrators or other users with the `manage_woocommerce` capability can manage the mapping rules.

## Version

Current plugin version: **1.0.1**

## License

GPL-2.0-or-later
