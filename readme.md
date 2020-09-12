# bunq for WooCommerce

## Caution: This plugin is still under heavy development. Use it at your own risk.

Contributors: patrickkivits\
Donate link: https://bunq.me/patrickkivits \
Tags: woocommerce, psp, payment gateway, bunq, ideal, credit card, sofort\
Requires at least: 3.8\
Tested up to: 5.3\
License: GPLv2 or later\
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept payments in your WooCommerce shop with just your bunq account.

## Requirements

- [bunq premium account](https://bunq.com/invite/patrickkivits)
- HTTPS/SSL (for callbacks/payment processing to work)

## Installation

1. Download the [latest zip](https://github.com/patrickkivits/bunq-for-woocommerce/archive/master.zip) of this repository.
2. In your WordPress admin panel, navigate to **Plugins** > **Add New**.
3. Click Upload Plugin.
4. Upload the zip file that you downloaded.

## Configuration

1. In your WordPress admin panel, navigate to **WooCommerce** > **Settings** > **Payments** > **bunq**.
2. In the bunq app, navigate to **Profile** > **Security & Preferences** > **Developers** > **OAuth** > **Show Client Details**.
3. Enter the **Client ID** and **Client Secret** in the plugin settings and **Save changes**.
4. Click the **OAuth Authorization Request** to authorize the plugin in the bunq app.

<img width="228" src="https://user-images.githubusercontent.com/727174/92739792-8a00ef00-f37d-11ea-8b28-4f8d02b145d4.png">

5. This will redirect you to the bunq website.

<img width="629" src="https://user-images.githubusercontent.com/727174/92740458-0d224500-f37e-11ea-8e6a-cb896762f0fc.png">

6. Scan the QR code with the bunq app to select the bank accounts to want to access.

<img width="100%" src="https://user-images.githubusercontent.com/727174/92741238-afdac380-f37e-11ea-95bd-9d5f93fabbf9.jpg">

7. After confirmation in the bunq app, the bunq website will redirect you back to the plugin settings.

8. **Enable** the plugin and **Save changes**.

## Disclaimer

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
