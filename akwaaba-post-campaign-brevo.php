<?php
/**
 * Plugin Name: Akwaaba Post Campaign for Brevo
 * Plugin URI: https://www.samenwerkvakanties.nl/
 * Description: Create and send campaign with Brevo when publishing a post.
 *
 * Version: 1.0.0
 * Requires at least: 6.3
 *
 * Author: Akwaaba multimedia
 * Author URI: https://www.akwaabamultimedia.nl/
 *
 * Text Domain: akwaaba-share-post
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * @author    Akwaaba multimedia <info@akwaabamultimedia.nl>
 * @copyright 2023 Akwaaba multimedia
 * @license   GPL-3.0-or-later
 * @package   Akwaaba\WordPress\SharePost
 */

use Akwaaba\WordPress\SharePost\Plugin;

require_once 'vendor/autoload.php';

Plugin::instance();
