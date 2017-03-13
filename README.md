# Subscriber Plugin

The **Subscriber** Plugin is for [Grav CMS](http://github.com/getgrav/grav).

## Description

This plugin offers a simple way to (un)subscribe users to/from a list or a newsletter. But this plugin doesn't handle the list itself. You, the admin or a defined e-mail address will receive a notification to manually add/remove the address to/from the list.
The main reason for developing this plugin was the situation that our website is seperated from our newsletter-tool. But we want to offer our users a simple way to unsubscribe from the newsletter with a single click. (In some countrys this is a legal requirement.) This tool provides subscribing as well.

## Features

* Provide a link in your newsletters to (un)subscribe to/from the list.
* You'll receive an e-mail notification about the action. (Email plugin required!)
* All messages and notifications are configurable.
* Place the user message at every place on your website.
* Supported translations: en, de, fr, es

## Setup

* Install the plugin to `/your/site/grav/user/plugins/`
* Navigate to the plugin-settings in your Admin-Panel and enable Subscriber.
* Check the settings and enter your sender and receiver e-mail address.
* Navigate to the page where you want to process the messages. (e.g. `/user/pages/newsletter/default.md`)
* Edit the frontmatter of your page and set these options:
```
title: 'This is my page title'
process:
    markdown: true
    twig: true
cache_enable: false
```
* Edit the page content and set this code at your preferred position:
 `{{ subscriber() }}`
* Visit your Page and you will note, that nothing new happens. That's correct. Now it's needed to add some parameters to your URL to test the plugin.
* Enable translation support in your GRAV site. [see the GRAV Documentation how to do this](https://learn.getgrav.org/content/multi-language#single-language-different-than-english)
* There are two parameters you have to set to start working.
 * **action** defines the task. The value can be **subscribe** or **unsubscribe**
 * **email** ist the e-mail address to (un)subscribe.
* Modify this example to your needs and enter the URL in your browser. (Note: It is **always** a good idea to use https when transferring userdata!)
```
https://www.example.com/newsletter?action=unsubscribe&email=user@example.com
```
* The user will receive a message on the webpage which confirms the action and you'll get an e-mail with the submitted data.
* When you send a newsletter to your clients/users/customers, you can provide a link to let the user simply click on that link to unsubscribe.

### Custom messages and notifications

If you don't like the standard messages, you can change them to your needs. You can do that by copying the `user/plugins/subscriber/languages.yaml` file into `user/config/plugins/subscriber/languages.yaml` and make your changes there. If you can't find your language I would apreciate your help in translation. Just send me a push requests.

### Custom CSS styles

By default this plugin creates some CSS rules for displaying result messages to the user. You can disable this in the plugin settings and create your own CSS styles in your template. This example is pretty self explanatory.
```
.grav-plugin-subscriber {
  border: 1px dotted black;
  line-height: 1.5em;
  font-weight: bold;
  padding: 1em;
  margin: 1em 0;
}
```

## ToDo

This tool is still under development, but the main goal is reached for me. I think, there is much more potential to do with such a plugin and I would be glad to receive comments ideas and/or pull requests.

I think, these features are worth to integrate in future:
- Create a form for the users to manually subscribe. (At the moment, I use the form-plugin in Grav)
- Add functionality to save and manage the e-mail address list
- Provide a double opt-in procedure with e-mail verification.

