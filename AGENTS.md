# AGENTS.md

Before working in this repository, read the shared workspace instructions in `../../samenwerken/AGENTS.md`; they apply in addition to these repository-specific instructions.

Purpose: contribute safely to the Brevo publication integration with minimal changes.

## Stack and scope
- WordPress plugin that sends Brevo campaigns when posts are published.
- PHP 8.2+ with Composer PSR-4 autoloading.
- Namespace: `Akwaaba\\WordPress\\SharePost`.
- No frontend build; the plugin registers one optional Brevo form block.

## Architecture
- `akwaaba-post-campaign-brevo.php` loads Composer and starts `Plugin`.
- `src/Plugin.php` registers the settings page and initializes `Brevo`.
- `src/Brevo.php` manages the API key, category metadata, the form block and the `transition_post_status` flow.
- Only regular WordPress posts are automatically processed.
- Categories define Brevo lists and segments through term metadata.
- `_akwaaba_share_post_brevo_campaign_id` prevents duplicate campaigns.
- The Brevo API is called through the WordPress HTTP API.

## Required rules
- Make minimal changes and preserve current behavior.
- Sanitize settings, term metadata and external API data.
- Check capabilities and nonces for admin mutations.
- Use WordPress APIs and prefix global calls in namespaced PHP.
- Do not edit `vendor/`.

## Documentation maintenance
- Update this file and the README when hooks, Brevo payloads, storage or settings change.
- Document new external API contracts and idempotency requirements.

## Checks
- `composer phpcbf && composer phpcs`.
- Run `php -l` on changed PHP files.

## Definition of done
- No fatals, warnings, duplicate campaigns or unsafe API behavior.
- Coding standards and documentation are current.
