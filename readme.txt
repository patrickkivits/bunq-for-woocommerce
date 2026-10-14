=== bunq for WooCommerce ===
Contributors: patrickkivits
Donate link: https://bunq.me/patrickkivits
Tags: woocommerce, payment gateway, bunq, ideal, bancontact
Requires at least: 4.7
Tested up to: 7.1
Requires PHP: 7.3
Stable tag: 1.8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept iDEAL, card, Bancontact and bunq payments in your WooCommerce shop with just your bunq account.

== Description ==

Your customers pay through a bunq.me payment request on your own bunq account. There is no payment service provider in between: the money lands on the bunq account you choose.

* iDEAL, credit and debit cards, Bancontact and bunq-to-bunq transfers through a bunq.me payment request.
* Optionally let customers pick the payment method at checkout; methods whose bunq limits do not fit the order total are hidden.
* Orders are confirmed by bunq's callback, again when the customer returns to the shop, and by background checks (WooCommerce's Action Scheduler) in case the callback never arrives.
* Payment requests that bunq reports as expired or cancelled cancel the order; cancelling an order cancels its payment request at bunq.
* Refunds from the WooCommerce order screen, created as a bunq draft payment back to the IBAN the payment came from and approved in the bunq app.
* Works with the classic checkout and the block checkout, and with High-Performance Order Storage.
* Translated into Dutch, French and German.

= Requirements =

* A [bunq](https://www.bunq.com) account.
* WooCommerce.
* HTTPS, so bunq can reach the shop's callback URL.
* The PHP extensions openssl, curl, json and mbstring.
* When PHP's OPcache is enabled: `opcache.save_comments=1` (PHP's default). The bunq SDK reads the types of bunq's responses from code comments, which `opcache.save_comments=0` removes; only your hosting provider can change this setting.

The plugin is developed by Patrick Kivits and is the WooCommerce integration bunq presents to its business customers, see [WooCommerce on bunq.com](https://www.bunq.com/business/features/integrations/woocommerce) and the [bunq Help article](https://help.bunq.com/nl-nl/articles/how-can-i-accept-woocommerce-payments-with-my-bunq-account).

== External services ==

This plugin connects your shop to [bunq](https://www.bunq.com), the bank that holds the merchant's account and processes the payments. Without bunq the plugin has no function. Depending on the "Test mode" setting it talks to bunq's production environment or to bunq's sandbox.

**OAuth authorization** (oauth.bunq.com, api.oauth.bunq.com; sandbox: oauth.sandbox.bunq.com, api-oauth.sandbox.bunq.com)
When the shop administrator clicks "Connect to bunq" on the settings page, the browser is sent to bunq with the OAuth client ID and the settings page URL. When bunq sends the administrator back, the plugin sends the OAuth client ID, client secret, the authorization code and the settings page URL to bunq to obtain the API key.

**bunq API** (api.bunq.com; sandbox: public-api.sandbox.bunq.com)
With the API key the plugin registers the shop's server with bunq (a generated public key; bunq sees the server's IP address) and then:

* lists the merchant's bank accounts on the settings page;
* registers the shop's callback URL for payment request notifications;
* creates a bunq.me payment request when a customer places an order, with the order total, the currency, the order number and the URL of the order confirmation page;
* reads the payment request and the payments made through it when bunq calls the callback URL, when the customer returns to the shop and in background checks for up to two days;
* cancels the payment request when the order is cancelled in WooCommerce;
* reads the payment and creates a draft payment back to the customer's IBAN when the merchant refunds an order, with the refund amount, the customer's IBAN and name as bunq reported them, and a description with the order number, the shop name and the refund reason.

**bunq.me** (bunq.me)
To pay, the customer is sent to the bunq.me page of the payment request, where they choose a payment method and enter their payment details at bunq.

bunq's terms and privacy policy apply to this data:

* [bunq API terms and conditions](https://www.bunq.com/documents/api-terms-conditions)
* [bunq.me terms and conditions](https://www.bunq.com/documents/bunq-me-terms-conditions)
* [bunq privacy policy](https://www.bunq.com/documents/privacy-policy)

== Installation ==

1. In your WordPress admin panel, navigate to **Plugins** > **Add New**, search for "bunq for WooCommerce", then install and activate it.
2. Navigate to **WooCommerce** > **Settings** > **Payments** > **bunq**.
3. Under **bunq account**, leave **Test mode** unchecked to receive real payments. To try the plugin first, check **Use the bunq sandbox** and use a bunq sandbox account in the steps below.
4. Click **Copy** next to the **Redirect URL**. In the bunq app, navigate to **Developers** > **OAuth**, tap **Add Redirect URL** and paste it.
5. In the bunq app, tap **Show Client Details** and copy the **Client ID** and **Client Secret** into the fields with the same name.
6. Click **Connect to bunq**. The settings are saved and bunq shows a QR code: scan it with the bunq app, choose the bank accounts the shop may use and approve. bunq sends you back to the settings page, where **Connection** now says "Connected to bunq as" followed by your name.
7. Under **Receiving payments**, select the **Bank account** payments are paid into. Check **Enable bunq Gateway** at the top of the page and click **Save changes**.

Under **Checkout** you set the title and description customers see. With **Enable direct gateway** customers choose iDEAL, card, Bancontact or a bunq transfer on the checkout page; **Payment methods** limits which of them are offered.

= Managing the connection =

Once the shop is connected, the settings page hides the Client ID and Client Secret and shows three actions next to **Connection**:

* **Connect again**: scan a new QR code, for example after bunq rejected the connection.
* **Change OAuth client**: shows the Client ID and Client Secret fields again to switch to another OAuth client.
* **Disconnect**: the shop forgets its access to bunq. Remove the connection in the bunq app to revoke it at bunq as well.

Live mode and the sandbox each have their own OAuth client and connection; the **Test mode** checkbox switches between them. The Client Secret is never shown again after saving it: leave the field empty to keep it.

The bank account list is cached for an hour. Click **Refresh bank accounts** below the bank account select after adding an account in the bunq app.

== Frequently Asked Questions ==

= How do refunds work? =

Open the order in WooCommerce, click **Refund**, enter the amount and choose **Refund via bunq**. The plugin creates a draft payment at bunq back to the IBAN the payment came from and adds a note with the draft payment id to the order. Approve the draft in the bunq app to actually send the money: bunq does not let an app connected through OAuth pay an external IBAN without that approval.

WooCommerce records the refund as soon as the draft is created, so an unapproved draft leaves the order marked as refunded while the customer has not received anything. Payments without a counterparty IBAN (for example card payments) cannot be refunded through the API; refund those from the bunq app.

= An order does not change status. Where do I look? =

Everything the plugin does with bunq (callbacks received, payments matched, refunds, errors) is written to **WooCommerce** > **Status** > **Logs** (source: `bunq`).

= The settings page still says "Not connected" after the authorization, or the bank account list says "bunq rejected the saved connection" =

* The plugin shows the error returned by bunq at the top of the settings page right after the redirect back from bunq, with details in the WooCommerce log.
* Make sure the redirect URL registered in the bunq app is **exactly** the redirect URL shown on the settings page, including `&section=bunq`.
* bunq ties the API key to the IP address the shop's server connects from. If your host sends outgoing requests from changing IP addresses, bunq may reject the session with "Incorrect API key or IP address".
* If the error mentions `opcache.save_comments=0`, or the log shows "Installation::getToken(): Return value must be of type bunq\Model\Core\Token, array returned", ask your hosting provider to set `opcache.save_comments=1`.

= Unpaid orders are cancelled before the customer paid =

By default WooCommerce holds stock for unpaid orders for 60 minutes and then cancels them. Some payment methods offered by bunq.me can take longer. Increase or disable **WooCommerce** > **Settings** > **Products** > **Inventory** > **Hold stock (minutes)**. The plugin keeps checking unpaid orders in the background for two days and cancels an order once bunq reports its payment request as expired.

= Can I try it without real money? =

Yes. Check **Use the bunq sandbox** under **Test mode** and connect the OAuth client of a bunq sandbox account; the plugin then talks to bunq's sandbox. The live connection is kept: uncheck the box to switch back.

= Which language does the payment page use? =

The bunq.me payment page is hosted by bunq and follows the language of the customer's browser.

== Changelog ==

= 1.8.1 =
* Servers whose PHP OPcache removes code comments (`opcache.save_comments=0`) could not connect to bunq: the bunq SDK needs those comments to read bunq's responses, and connecting failed with "Installation::getToken(): Return value must be of type bunq\Model\Core\Token, array returned". The settings page now explains that the hosting provider has to set `opcache.save_comments=1`, before sending you to bunq and in the bank account select.
* `opcache.save_comments=1` is listed in the requirements.
* Updated Dutch, French and German translations.

= 1.8.0 =
* Redesigned settings page: a Connection row shows whether the shop is connected to bunq and to which account, the redirect URL to register in the bunq app has a Copy button, and the fields are grouped into bunq account, Receiving payments and Checkout.
* "Connect to bunq" saves the settings and opens bunq's QR code in one step; it replaces the "OAuth Authorization Request" button.
* New: Disconnect, which forgets the shop's access to bunq and keeps the OAuth client so connecting again takes one click.
* The test mode checkbox switches the OAuth client and connection on the settings page right away, without saving first.
* The OAuth redirect URL is always the plugin settings page URL (`/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq`), however the page was opened. Before, extra parameters on the page URL could make bunq reject the authorization.
* The browser no longer fills in the WordPress login password in the Client Secret field.
* Errors from bunq during the authorization show bunq's error descriptions instead of the raw response, and the bank account select says when bunq rejected the saved connection.
* The payment method select of the direct gateway in the block checkout looks like the other checkout fields in every theme and has a "Payment method" label.
* Updated Dutch, French and German translations.

= 1.7.0 =
* Security: only an administrator returning from bunq to the plugin settings page can complete the bunq authorization, and only with the state of the authorization they started. Before, the authorization could be completed by requests from anyone.
* Security: the OAuth client secret, the client ID, the authorization code and the API key are kept out of the WooCommerce log, the PHP error log and the error shown on the settings page, also when bunq repeats them in an error.
* Security: the API key and the API context (which holds the private key) are no longer settings fields. They are not shown on the settings page or in the WooCommerce REST API, cannot be changed through either, and are stored apart from the settings without being loaded on every request. The OAuth client secret is no longer put in the settings page; leave the field empty to keep it.
* Security: callbacks arriving in quick succession for the same payment request make one request to bunq and queue a single follow-up check.
* Restructured the plugin into autoloaded, namespaced classes following the WordPress coding standards. Code that refers to the WC_Bunq_Gateway or WC_Bunq_WooCommerce_Block_Checkout classes keeps working.
* Requires WordPress 4.7 or newer and declares WooCommerce as a required plugin.
* Fix: the block checkout no longer fails with a fatal error on servers that do not meet the plugin's requirements.
* Tested up to WordPress 7.1 and WooCommerce 11.2.

= 1.6.4 =
* Fix: no warning about the other mode's OAuth client on the first save of the settings.

= 1.6.3 =
* Refunds are created as bunq draft payments that you approve in the bunq app.

= 1.6.2 =
* Fix: strip characters bunq rejects from the refund description.

= 1.6.1 =
* Fix: the payment method chosen at checkout opens directly on bunq.me again.

= 1.6.0 =
* Payments are confirmed when the customer returns to the shop and by background checks.
* Expired or cancelled payment requests cancel the order, and cancelled orders cancel their payment request.
* Refunds from the WooCommerce order screen.
* Dutch, French and German translations.

= 1.5.10 =
* Fix: order lookup and amount check of bunq's callback, and registration of the callback URL.

= 1.5.9 =
* Fix: the settings fields are defined outside the admin as well.

= 1.5.8 =
* Supports PHP 7.3 up to 8.5.

= 1.5.7 =
* Updated Guzzle and the OAuth client library.

= 1.5.6 =
* Errors from bunq during the OAuth setup are shown instead of silently ignored.

== Upgrade Notice ==

= 1.8.1 =
Explains what to ask your host when connecting to bunq fails with "Installation::getToken(): Return value must be of type ... Token, array returned" (opcache.save_comments=0).

= 1.8.0 =
Redesigned settings page with one-click "Connect to bunq". The redirect URL must be exactly the plugin settings page URL shown on the settings page: update it in the bunq app if you registered a different one. Settings and the bunq connection are kept.

= 1.7.0 =
Security release. Requires WordPress 4.7 or newer. Settings, the bunq connection, orders and pending payment checks are kept.
