# 💬 Simple AJAX Shoutbox

**Simple AJAX Shoutbox** is a lightweight WordPress plugin that provides a minimal **real-time AJAX-powered shoutbox**.  
It allows logged-in users to post short public messages while admins can manage, edit, or clear the shoutbox via the dashboard.

![Simple AJAX Shoutbox Screenshot](screenshot.png)

---

## ✨ Features

- ✅ Minimal **left-aligned shoutbox** (no boxes, lightweight design)  
- ✅ **AJAX posting and fetching** (no page reloads)  
- ✅ **Logged-in users only** can post messages  
- ✅ **Admin panel** to:
  - Edit or delete individual messages  
  - Clear all messages  
  - Configure options (enabled, max messages, poll interval)  
- ✅ Messages stored in a dedicated database table  
- ✅ Shortcode `[ajax_shoutbox]` for easy embedding  

---

## 📦 Installation

1. Download or clone the repository into `wp-content/plugins/simple-ajax-shoutbox/`:

   git clone https://github.com/yourname/simple-ajax-shoutbox.git
2. In your WordPress Dashboard → Plugins, activate Simple AJAX Shoutbox

3. A new settings page will appear under Settings → Simple Shoutbox

⚙️ Configuration

From the admin panel (Settings → Simple Shoutbox) you can:

Enable/disable the shoutbox

Set the maximum number of stored messages (default: 50)

Set the polling interval in seconds (default: 10s)

Admins can also:

Edit existing messages

Delete single messages

Clear all messages with one click

🚀 Usage

To display the shoutbox in a post, page, or widget, simply use the shortcode:

[ajax_shoutbox]


Example frontend behavior:

Logged-in users see a text field and "send" button.

Guests see a message: "You must log in to post messages."

Messages update automatically every few seconds without page reload.

📂 Plugin Structure
simple-ajax-shoutbox/
│── simple-ajax-shoutbox.php   # Main plugin file

🛡️ Security

Messages are sanitized before being stored.

AJAX requests are protected with nonces.

Posting is limited to logged-in users only.

📜 License

This project is licensed under GPL-2.0+.
You are free to use, modify, and redistribute it under the license terms.

👨‍💻 Author

Teo
If you enjoy this plugin, please give it a ⭐ on GitHub!
