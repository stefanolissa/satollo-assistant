# Assistant

An Assistant able to use the new WP abilities to execute tasks. Of course it is based on
AI.

## Disclaimer

This plugin is an experiment, do not use it on production sites.

Abilities can execute destructive tasks it's your own responsability to ask or not ask the
assistant to invoke them. A configuration is under work to enable/disable specific abilities.

## Prerequisites

WordPress 6.9.

## Install

The first install is by uploading the file https://www.satollo.net/repo/assistant/assistant.zip,
then it will update automatically.

Of course you can use this repo directly.

## Usage

You need to add an API key from your preferred AI provider; see the settings page.

Then you need one or more plugins implementing the abilities. If you need something to start with,
you can try the [Mailer](https://github.com/stefanolissa/mailer) plugin (a dummy newsletter management
plugin).

## Monitor

To show a list of the available abilities, dump the input and output schema, log the executions
I adapted the plugin [Monitor](https://github.com/stefanolissa/monitor).


