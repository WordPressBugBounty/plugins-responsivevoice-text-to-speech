=== ResponsiveVoice Text To Speech ===
Contributors: ResponsiveVoice
Author: ResponsiveVoice
Donate link: https://responsivevoice.org/wordpress-text-to-speech-plugin/
Tags: text to speech, tts, accessibility, audio, text to audio
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ResponsiveVoice the leading HTML5 text to speech synthesis solution, is now available for WordPress. Over 51 languages through 158 voices.

== Description ==
[ResponsiveVoice](https://responsivevoice.org/wordpress-text-to-speech-plugin/) adds HTML5 text-to-speech to your WordPress posts and pages — with nothing extra to install, across all smartphone, tablet and desktop devices. Your readers can listen with a tap: add a Listen button through the Gutenberg block or a shortcode, or turn on the WebPlayer to read a whole post aloud.

It supports 51 languages through 158 voices out of the box — plus thousands of premium neural voices from major cloud providers (Google Cloud, Microsoft Azure, OpenAI and more) via bring-your-own-key on v2 accounts.

Languages include UK English, US English, Spanish, French, German, Italian, Greek, Hungarian, Turkish, Russian, Dutch, Swedish, Norwegian, Japanese, Korean, Chinese, Chinese (Hong Kong), Chinese Taiwan, Hindi, Serbian, Croatian, Bosnian, Romanian, Catalan, Australian, Finnish, Afrikaans, Albanian, Arabic, Armenian, Czech, Danish, Esperanto, Hatian Creole, Icelandic, Indonesian, Latin, Latvian, Macedonian, Moldavian, Montenegrin, Polish, Brazilian Portuguese, Portuguese, Serbo-Croatian, Slovak, Spanish Latin American, Swahili, Tamil, Thai, Vietnamese and Welsh.


### Support and Questions visit here first:
> * [Support](https://responsivevoice.org/support)

### Useful Links:
> * [Live Demo](https://responsivevoice.org/wordpress-text-to-speech-plugin/)
> * [Homepage](https://responsivevoice.org/wordpress-text-to-speech-plugin/)
> * [Documentation](https://responsivevoice.org/wordpress-text-to-speech-plugin/)

### Features:
* Listen to any post or page with the tap of a button
* A Gutenberg "Listen" block with a live voice picker — plus shortcodes for the classic editor
* The WebPlayer: a customizable player that reads a whole post aloud (v2 accounts)
* 51 languages through 158 voices, plus thousands of premium neural voices via bring-your-own-key (v2)
* Easy access to your content for every visitor — tap to listen to any page or post
* A more accessible website for a range of users, including the visually impaired and the elderly
* Web Content Accessibility Guidelines (WCAG) 2.0, ADA and BS 8878:2010 features

### Usage:

**Add a Listen button with the block**

In the block editor, insert the **ResponsiveVoice Listen Button** block wherever you want a Listen button. Pick a voice from the live list, adjust rate, pitch and volume, and preview it — all in the editor.

**Turn on the WebPlayer (v2)**

The WebPlayer is a ResponsiveVoice v2 feature that reads the whole post aloud — and the one ResponsiveVoice website feature you control right from WordPress. On a v2 account, open the **ResponsiveVoice** menu in your WordPress admin to enable or disable it and customise its appearance, position and behaviour, choose which content types show it, and override it per post. Your other ResponsiveVoice website features (such as the Welcome message that plays when a page loads) are configured in your ResponsiveVoice account dashboard.

**Shortcodes (classic editor and back-compatible)**

Add a Listen button anywhere in a post or page:
`[responsivevoice_button]`

Set the voice and the button label:
`[responsivevoice_button voice="US English Female" buttontext="Play"]`

Read just a section by wrapping it:
`[responsivevoice]Text you want ResponsiveVoice to read[/responsivevoice]`

Place the button after the text (it appears before by default):
`[responsivevoice buttonposition="after"]Text you want ResponsiveVoice to read[/responsivevoice]`

Adjust rate, pitch and volume:
`[responsivevoice_button rate="1" pitch="1.2" volume="0.8" voice="US English Female" buttontext="Play"]`

Leave the voice unset to use your account's default voice. A full list of voice names is in the [Documentation](https://responsivevoice.org/text-to-speech-languages/).

**Your API key**

Create a [free account](https://responsivevoice.org/register) and add your API key from the **ResponsiveVoice** admin menu to unlock the full voice catalogue and the WebPlayer. Without a key, the plugin runs in demo mode so you can try it first.

For more details, please see the [Documentation](https://responsivevoice.org/wordpress-text-to-speech-plugin/)

= Requirements =

ResponsiveVoice runs in your visitors' browsers, so there is nothing to install or configure on your server. It works in modern browsers across desktop and mobile.

== External services ==

This plugin relies on the ResponsiveVoice service to synthesize speech, so text and your API key are sent to it from visitors' browsers.

- The ResponsiveVoice library/SDK is loaded from `code.responsivevoice.org` / `cdn.responsivevoice.org`, or, in "bundled" delivery mode, served from your own site.
- When a visitor plays audio, the text to read and your site's API key (a public website identifier, not a secret) are sent to the ResponsiveVoice Text-To-Speech API to generate audio.
- The visitor's browser reads your site's ResponsiveVoice configuration — your account's website feature settings — from `texttospeech.responsivevoice.org`, so those features work on your site.
- When the ResponsiveVoice SDK plays audio (the WebPlayer, buttons or shortcodes), it reports the number of characters spoken to `app.responsivevoice.org`. This is a single number for usage accounting: no personal or user information, and not the spoken text, is sent. It is required so your account's API usage is attributed correctly.

Terms of Service: https://responsivevoice.org/terms/

Privacy Policy: https://responsivevoice.org/privacy-policy/

== Frequently Asked Questions ==

You can read our FAQs [here](https://docs.responsivevoice.org/integrations/wordpress/#faq "ResponsiveVoice for WordPress documentation").

If you have experienced any problems with this plugin please let us know by contacting our support department at [Support](https://responsivevoice.org/support "ResponsiveVoice support") website.

== Installation ==

= Install from your WordPress admin (recommended) =

1. Go to Plugins > Add New and search for "ResponsiveVoice Text To Speech".
2. Click Install Now, then Activate.

= Install manually =

1. Upload the plugin under Plugins > Add New > Upload Plugin, or extract the ZIP into the `/wp-content/plugins/` directory.
2. Activate the plugin through the Plugins menu in WordPress.

= Get started =

1. Add a Listen button with the ResponsiveVoice Listen Button block, or a shortcode such as `[responsivevoice_button]`.
2. Open the **ResponsiveVoice** menu in your admin to add your API key and, on a v2 account, enable and customise the WebPlayer.

= Upgrading from 1.7.x =

Your existing shortcodes keep working, so no changes are required. A few things have changed in 2.0:

* The Listen button has a refreshed, neutral look with a ResponsiveVoice icon (previously a speaker emoji). If you styled the button with custom CSS, check it still looks the way you want.
* New in 2.0: the ResponsiveVoice Listen Button block, the WebPlayer, and a settings screen under the **ResponsiveVoice** admin menu.
* When no voice is set, the button now uses your account's default voice instead of a fixed voice.
* The `[responsivevoice_box]` voice-selector shortcode has been removed.

== Screenshots ==

1. Read any page aloud with the WebPlayer.
2. Add a Listen button and choose from live voices — right in the block editor.
3. Enable, position and customise the WebPlayer, with a live preview.
4. Enable or disable the WebPlayer on any individual post or page.

== Changelog ==

= Version 2.0.3 =
* update the bundled ResponsiveVoice speech engine to the latest version

= Version 2.0.2 =
* web player options now match your ResponsiveVoice plan

= Version 2.0.1 =
* [ResponsiveVoice] shortcode no longer distorts layout; button aligns across themes

= Version 2.0.0 =
* Removed the voicebox shortcode.
* Now requires WordPress 6.3+ and PHP 7.4+.
* New v2 WebPlayer with a visual customizer: live preview, Basic/Advanced modes, custom colours, content-column alignment, and a configurable position.
* Per-content-type WebPlayer visibility with per-post overrides.
* Server-rendered Gutenberg "listen button" block with a live voice picker, rate/pitch/volume controls, and voice preview.
* Rendering engine now follows the account's SDK version via a config probe, with a reversible v1 opt-in and keyless demo mode.
* Rebuilt admin settings: API-key verification, tier-aware status, website-verification notices, onboarding guidance, and reset-to-defaults.
* Existing shortcodes keep working after upgrade (parity verified against 1.7.16).

= Version 1.7.16 =
- Tested with upcoming Wordpress v7.0 release

= Version 1.7.15 =
- Tested with upcoming Wordpress v6.8 release

= Version 1.7.14 =
- Tested with upcoming Wordpress v6.7 release

= Version 1.7.13 =
* Fixes a cache invalidation issue when changing settings in ResponsiveVoice App - https://app.responsivevoice.org
- Tested with upcoming Wordpress v6.6 release

= Version 1.7.12 =
* Fix plugin page to use an unique name
* Improve Admin UI CSS

= Version 1.7.11 =
* Update "Tested up to" Wordpress 6.5 release
* Update ResponsiveVoice Website links and copyright notices


= Version 1.7.10 =
* Update "Tested up to" Wordpress 6.4 release

= Version 1.7.9 =
* Update "Tested up to" Wordpress 6.3 release

= Version 1.7.8 =
* Update "Tested up to" Wordpress 6.2 release

= Version 1.7.7 =
* Add sanitation to shortcode attributes 
* Update "Tested up to" Wordpress 6.1.1 release

= Version 1.7.6 =
* Update "Tested up to" for upcoming Wordpress 6.1 release

= Version 1.7.5 =
* Upgrade to ResponsiveVoice 1.8.3

= Version 1.7.4 =
* Fix API Key include logic when the plugin is first installed
* Update "Tested up to" for upcoming Wordpress 6.0 release

= Version 1.7.3 =
* Upgrade to ResponsiveVoice 1.8.2

= Version 1.7.2 =
* Update "Tested up to" for upcoming Wordpress release

= Version 1.7.1 =
* Upgrade to ResponsiveVoice 1.8.1

= Version 1.7.0 =
* Add `responsivevoice_content_before_cleaning` and `responsivevoice_content_after_cleaning` filter hooks for plugin and theme developers.

= Version 1.6.9 =
* Upgrade to ResponsiveVoice 1.8.0
* Analytics improvement

= Version 1.6.8 =
* Prepare for Wordpress 5.7 release

= Version 1.6.7 =
* Fix issue with API key not being recognized by the plugin.
* Upgrade to ResponsiveVoice 1.7.0
* Slower default rate of speech for Croatian Male

= Version 1.6.6 =
* Add API Key configuration to the Wordpress plugin settings

= Version 1.6.5 =
* Upgrade to ResponsiveVoice 1.6.5
* Show permission popup (unless disabled) if TTS is blocked by browser due to lack of user interaction
* Give more informative error if the voice name supplied to responsiveVoice.speak does not exist

= Version 1.6.4 =
* Upgrade to ResponsiveVoice 1.6.4
* Fix permission popup not always appearing if browser doesn't support SpeechSynthesis

= Version 1.6.3 =
* Upgrade to ResponsiveVoice 1.6.3
* Deprecate some voices for which we cannot guarantee a fallback gender: Brazilian Portuguese Male, Czech Male, Danish Male, Finnish Male, Greek Male, Hungarian Male, Russian Male, Slovak Male, Spanish Male.
They have *not* been removed from the platform, and will continue to work in existing installations; however unless the male voice is available on the browser/OS, they will be female instead of male.
* Don't show deprecated voices in voice selector within [ResponsiveVoiceBox] shortcode

= Version 1.6.2 =
* Fix an alternate encoding of em-dashes being read aloud

= Version 1.6.1 =
* Upgrade to ResponsiveVoice 1.6.2
* Fix Chinese speech cut off prematurely
* Fix Greek Female does not support fast speech rate
* Fix US English Female with the wrong gender voice on recent versions of Chrome desktop (MacOS/Windows)
* Fixes Array.from with a polyfill for Internet Explorer 11
* Add comprehensible error handling and messages
* Add console error when not using an API Key
* Fix last version number in this changelog

= Version 1.6.0 =
* Upgrade to ResponsiveVoice 1.5.17
* Fix Classic Editor double-encoding some quotes
* Fix eszett, emdash, and other safe-for-text symbols being encoded and read aloud

= Version 1.5.16 =
* Upgrade to ResponsiveVoice 1.5.16
* Fixed Button won't trigger audio on Android
* Removed references to English United Kingdom (android female) voice in US English Male and UK English Male ResponsiveVoices
* Fixed Japanese Female changed to Japanese Male in Chrome Desktop
* Fixed Siri voices speaking opposite gender with UK Female, US Male, Australian Male, Japanese Male
* Fixed Native tests voices not changing on android
* Added Tamil female voice
* Added iOS 13 voices
* Added Android 9 native voices on all existing voice profiles
* Added Microsoft Edge Dev 77.0.189.3 (Official build) dev (64-bit) server side native voices for US English Male and US English Female
* Added Bangla Bangladesh Male/Female TTS Voice
* Added Bangla India Male/Female TTS Voice
* Added Estonian Male TTS Voice
* Added Filipino Female TTS Voice
* Added French Canadian Female TTS Voice
* Added Khmer Cambodian Female TTS Voice
* Added Nepali Female TTS Voice
* Added Sinhala Sri Lanka Female TTS Voice
* Added Ukrainian Female. TTS Voice

= Version 1.5.15 =
* Upgrade to ResponsiveVoice 1.5.15

= Version 1.5.14 =
* Fixed text cutoff for male fallback voices on very long texts

= Version 1.5.13 =
* Fixed Male Fallback Voices

= Version 1.5.12 =
* Fixed US English Male Fallback Voice

= 1.5.11 =
* Fixed support for OGG

= 1.5.10 =
* Upgrade to ResponsiveVoice 1.5.10
* Fixed iOS 12.0.1 bug with languages in native TTS
* Fixed Hungarian Female voice

= 1.5.9 =
* Upgrade to ResponsiveVoice 1.5.9
* Fix infinite loop with words longer than the character limit
* Add Windows 7 US English Female (Anna) and Chinese Female (Lili) voices

= 1.5.8 =
* Adjust rate, volume and pitch through shortcode
* Full support for US English Male TTS Voice
* Full support for Arabic Male TTS Voice
* Full support for Chinese Male TTS Voice
* Full support for Chinese Hong Kong Male TTS Voice
* Added French Male TTS Voice
* Added Deutsch Male TTS Voice / German Male TTS Voice
* Added Dutch Male TTS Voice
* Added Hindi Male TTS Voice
* Added Indonesian Male TTS Voice
* Added Italian Male TTS Voice
* Added Japanese Male TTS Voice
* Added Korean Male TTS Voice
* Added Polish Male TTS Voice
* Added Brazilian Portuguese Male TTS Voice
* Added Portuguese Male TTS Voice
* Added Spanish Male TTS Voice
* Added Spanish Latin American Male TTS Voice
* Added Thai Male TTS Voice
* Added Turkish Male TTS Voice
* Added Vietnamese Female TTS Voice
* Added Moldavian Female TTS Voice
* Resurrected Greek Male TTS Voice, Swedish Male TTS Voice, Finnish Male TTS Voice, Vietnamese Male TTS Voice, Latin Male TTS Voice
* Added full pitch support for Norwegian Female TTS Voice, Finnish Female TTS Voice, Arabic Female TTS Voice, Armenian Male TTS Voice, Danish Female TTS Voice, Brazilian Portuguese Female TTS Voice, Slovak Female TTS Voice, Spanish (Latin American) TTS Voice
* Romanian Male TTS Voice replaced with Romanian Female TTS Voice
* Deprecated Latin Female TTS Voice, Moldavian Male TTS Voice

= 1.5.7 =
* Upgrade to ResponsiveVoice 1.5.7
* Improve HTML5 audio stability and initialization
* Improve look and behaviour of request for permission
* Improved time estimation for currencies
* Fixed onend event on iOS

= 1.5.6 =
* Upgrade to ResponsiveVoice 1.5.6
* Improve fallback handling

= 1.5.5 =
* Align version with main ResponsiveVoice library to provide more precise support.
* Fix bug causing some paragraphs to be skipped in very long texts.
* Improved HTML5 audio stability.
* Improved HTML5 audio and TTS initialization on iOS and Android.
* Fixed support for HTML5 audio on Android.
* Added 54 Microsoft Edge native voices.
* Improved native voice matching.
* Improved Split sentences.
* Improved Decimal places interpreted as pause.
* Fixed Taiwan native voice priority.
* Use new iOS10 voices when available for native TTS.
* Use Edge non-English voices for native TTS.
* Improved time estimation algorithm.
* Fixed overlap issue on Android fallback mode.
* Improve support of non-latin character voices.
* Deprecated voice: Arabic Male – Updated to Arabic Female.
* Deprecated voice: Danish Male (no longer supported, mapped to female).
* Deprecated voice: Finnish Male (no longer supported, mapped to female).
* Deprecated voice: Greek Male (no longer supported, mapped to female).
* Deprecated voice: Latin Male (no longer supported, mapped to female).
* Deprecated voice: Slovak Male (no longer supported, mapped to female).
* Deprecated voice: Swedish Male (no longer supported, mapped to female).
* Deprecated voice: Vietnamese Male (no longer supported, mapped to female).
* Minor bugfixes and stability improvements.

= 1.1.7 =
* Add buttonposition parameter to [responsivevoice] tag.
* Buttons can now be styled through the responsivevoice-button class.
* Update compatibility with latest Wordpress release.

= 1.1.6 =
* Load ResponsiveVoice through HTTPS.
* Position ResponsiveVoice button to before paragraph instead of after.

= 1.1.5 =
* Update compatibility with Wordpress release.

= 1.1.4 =
* Releases will now be properly tagged, tags can be found in the "tags" folder as usual.
* Added responsivevoice.css and responsivevoice-includes.php.
* FIX: apostrophes, quotation marks, &, <, >, non-breaking spaces and en dashes will no longer be converted to ASCII codes.

= 1.1.3 =
* Removed ResponsiveVoice icon from buttons, now the speaker emoji is displayed instead.
* FIX: Text in the button should not wrap around anymore.

= 1.1.2 =
* FIX: multiple instances of ResponsiveVoice buttons now work on the same page.
* FIX: fixed vertical alignment of the ResponsiveVoice logo in buttons.
* FEATURE: added the possibility to only speak a piece of text. Just surround it with [responsivevoice] and [/responsivevoice]. Its parameters are voice and buttontext, like with [responsivevoice_button].

= 1.1.1 =
* FIX: Text in [responsivevoice_button] won't wrap anymore.
* Added FAQ and Support links to the plugin's action row in Wordpress' "Installed plugins" page.

= 1.1 =
* Clicking on the RVListenButton on a page while a voice is playing will now stop it.
* Added support for new standardized shortcode, [RVListenButton].
* Added support for a "voice" parameter for [RVListenButton], which defaults to UK English Female.
* Added support for a "buttontext" parameter for [RVListenButton], which defaults to "Listen to this".

= 1.0.5 =
* Support for voice attribute in shortcode

= 1.0 =
* This is the initial release of the plugin

== Upgrade Notice ==

= 2.0.3 =
* Upgrade the plugin for the latest improvements.
