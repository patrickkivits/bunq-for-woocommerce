# bunq for WooCommerce

Contributors: patrickkivits\
Donate link: https://bunq.me/patrickkivits \
Tags: woocommerce, psp, payment gateway, bunq, ideal, credit card, bancontact\
License: GPLv2 or later\
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept payments in your WooCommerce shop with just your bunq account.

## Features

- iDEAL, credit and debit cards, Bancontact and bunq-to-bunq transfers through a [bunq.me](https://bunq.me) payment request.
- Orders are confirmed by bunq's callback, again when the customer returns to the shop, and by background checks (WooCommerce's Action Scheduler) in case the callback never arrives.
- Payment requests that bunq reports as expired or cancelled cancel the order; cancelling an order cancels its payment request at bunq.
- Refunds from the WooCommerce order screen, sent back to the IBAN the payment came from.
- Works with the classic checkout and the block checkout, and with High-Performance Order Storage.
- Translated into Dutch, French and German; other languages can be added from the `languages/` template.

## Demo

https://bunq-for-woocommerce.patrickkivits.com/

## Requirements

- [bunq account](https://bunq.com/invite/patrickkivits)
- HTTPS/SSL certificate (for callbacks/payment processing to work)
- PHP version: >= 7.3 (up to and including 8.5)
- PHP extensions: openssl, curl, json, mbstring
- Wordpress version: >= 3.8
- WooCommerce version: >= 2.2

## Installation

1. Download `bunq-for-woocommerce.zip` from the [latest release](https://github.com/patrickkivits/bunq-for-woocommerce/releases/latest/download/bunq-for-woocommerce.zip).
2. In your WordPress admin panel, navigate to **Plugins** > **Add New**.
3. Click Upload Plugin.
4. Upload the zip file that you downloaded.

### Updating

Upload the new `bunq-for-woocommerce.zip` the same way. WordPress recognises the installed plugin and offers **Replace current with uploaded**, which keeps your settings.

## Configuration

1. In your WordPress admin panel, navigate to **WooCommerce** > **Settings** > **Payments** > **bunq**.
2. In the bunq app, navigate to **Developers** > **OAuth** > **Add Redirect URL**.
3. Enter the **full URL** of the plugin page (e.g. https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq).
4. In the bunq app, navigate to **Developers** > **OAuth** > **Show Client Details**.
5. Enter the **Client ID** and **Client Secret** in the plugin settings and **Save changes**.
6. Click the **OAuth Authorization Request** to authorize the plugin in the bunq app.

<img width="228" src="https://user-images.githubusercontent.com/727174/92739792-8a00ef00-f37d-11ea-8b28-4f8d02b145d4.png">

7. This will redirect you to the bunq website.

<img width="629" src="https://user-images.githubusercontent.com/727174/92740458-0d224500-f37e-11ea-8e6a-cb896762f0fc.png">

8. Scan the QR code with the bunq app to select the bank accounts you want to access.

<img width="100%" src="https://user-images.githubusercontent.com/727174/92741238-afdac380-f37e-11ea-95bd-9d5f93fabbf9.jpg">

9. After confirmation in the bunq app, the bunq website will redirect you back to the plugin settings.

10. Select your **Bank account** and **Enable** the plugin and **Save changes**.

The bank account list is cached for an hour. Use **Refresh bank accounts** on the settings page after adding an account in the bunq app.

## Refunds

Open the order in WooCommerce, click **Refund**, enter the amount and choose **Refund via bunq**. The money is sent back to the IBAN the payment came from and a note with the bunq payment id is added to the order. Payments without a counterparty IBAN (for example card payments) cannot be refunded through the API; refund those from the bunq app.

## Troubleshooting

Everything the plugin does with bunq (callbacks received, payments matched, refunds, errors) is written to **WooCommerce** > **Status** > **Logs** (source: `bunq`). Look there first when an order does not change status.

If the **Live API Context** stays empty after the OAuth authorization, or the **Bank account** dropdown shows *API key not valid or not setup yet*:

1. The plugin shows the error returned by bunq as a notice at the top of the plugin settings page right after the redirect back from bunq.
2. Full details are written to **WooCommerce** > **Status** > **Logs** (source: `bunq`).
3. Make sure the redirect URL registered in the bunq app is **exactly** the plugin settings page URL, including `&section=bunq`.
4. Every attempt whitelists the IP address the request was sent from. If your host sends outgoing requests from changing IP addresses, bunq may reject the session with *Incorrect API key or IP address*.

## Optional configuration

By default WooCommerce will **hold stock** for unpaid orders for **60 minutes**. When this limit is reached, the pending order will be **cancelled**.

Some payment methods provided by bunq.me may take longer than that to complete. To avoid premature cancellation this setting can be increased or disabled in the WooCommerce configuration here: **WooCommerce** > **Settings** > **Products** > **Inventory** > **Hold stock (minutes)**. The plugin keeps checking unpaid orders in the background for two days and cancels an order once bunq reports its payment request as expired.

## Translations

The plugin ships with Dutch (`nl_NL`), French (`fr_FR`) and German (`de_DE`) translations. To add a language, translate `languages/bunq-for-woocommerce.pot` with a tool such as Poedit and save the `.po` and `.mo` files as `languages/bunq-for-woocommerce-<locale>.po`. After changing strings in the code, regenerate the template with:

```
php .github/scripts/make-pot.php
```

The bunq.me payment page itself is hosted by bunq and follows the language of the customer's browser.

## Disclaimer

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
