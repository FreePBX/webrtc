# webrtc

Module of FreePBX (WebRTC Phone)

The WebRTC Module allows an Administrator to enable a "WebRTC phone" that can be attached to a user's extension which they can connect to through the FreePBX User Control Panel (UCP).
This WebRTC phone will then receive phone calls at the same time as the user's extension using user and device mode behind the scenes.
If you have User and Device Mode enabled on any extension you enable the WebRTC Phone on, a duplicate extension of 99XXXX will be generated (where XXXX is the original extension number).
When the user then views the web interface of the WebRTC phone, they will be connected to device 99XXXX which will receive calls from the original extension.

The WebRTC module was [released in 2014](https://www.freepbx.org/webrtc-softphone-module-now-available-for-freepbx/ "WebRTC blog post").

## FreePBX

FreePBX is the world’s most popular open source Private Branch eXchange (PBX) --
primarily a web-based Graphical User Interface (GUI), written mostly in PHP and JavaScript,
that simplifies management of the Asterisk toolkit for telephony applications.

Learn more about the FreePBX project at the [main organization page on GitHub](https://github.com/FreePBX) including links to our
[issue tracker](https://github.com/FreePBX/issue-tracker) where you can reference specific Pull Requests in individual modules,
[security reporting](https://github.com/FreePBX/security-reporting) for private disclosure of sensitive vulnerabilities,
over one hundred other module [repositories](https://github.com/orgs/FreePBX/repositories?type=all) that you can fork and tinker with,
community resources, documentation, and much, much more! 🐸

<sub>[FreePBX](https://freepbx.org) and [Asterisk](https://asterisk.org) are Registered Trademarks of [Sangoma Technologies](https://sangoma.com/products/open-source/).</sub>
