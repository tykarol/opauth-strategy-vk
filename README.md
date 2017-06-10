opauth-vk
================

[Opauth](https://github.com/opauth/opauth) strategy for vk.com authentication.

Based on Opauth's LinkedIn Oauth2 Strategy.

Getting started
----------------
1. Install opauth-vk:
   ```bash
   cd path_to_opauth/Strategy
   git clone git@github.com:tykarol/opauth-vk.git VK
   ```

2. Create a VK application at https://vk.com/apps?act=manage
   - Make sure that redirect URI is set to actual OAuth 2.0 callback URL, usually `http://path_to_opauth/vk/oauth2callback`

3. Configure opauth-vk strategy.

4. Direct user to `http://path_to_opauth/vk` to authenticate


Strategy configuration
----------------------

Required parameters:

```php
<?php
'VK' => array(
  'app_id' => 'YOUR CLIENT ID',
  'app_secret' => 'YOUR CLIENT SECRET'
)
```

Optional parameters:
`scope`, `response_type`, 'v', 'display'
For `scope`, separate each scopes with a comma (,). Eg. `offline,email,wall`. All available scops can be found [here](https://vk.com/dev/permissions).


References
----------
- [Developer Documentation](http://vk.com/dev/)
- [Authorization Flow](https://vk.com/dev/authcode_flow_user)

License
---------
opauth-vk is MIT Licensed
