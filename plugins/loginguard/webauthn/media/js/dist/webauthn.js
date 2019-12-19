"use strict";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { keys.push.apply(keys, Object.getOwnPropertySymbols(object)); } if (enumerableOnly) keys = keys.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

/*
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
// Namespace
var akeeba = akeeba || {};
akeeba.LoginGuard = akeeba.LoginGuard || {};
akeeba.LoginGuard.webauthn = akeeba.LoginGuard.webauthn || {
  authData: null
};
/**
 * Utility function to convert array data to base64 strings
 */

akeeba.LoginGuard.webauthn.arrayToBase64String = function (a) {
  return btoa(String.fromCharCode.apply(String, _toConsumableArray(a)));
};
/**
 * Ask the user to link an authenticator using the provided public key (created server-side).
 */


akeeba.LoginGuard.webauthn.setUp = function () {
  // Make sure the browser supports Webauthn
  if (!('credentials' in navigator)) {
    alert(Joomla.JText._('PLG_LOGINGUARD_WEBAUTHN_ERR_NOTAVAILABLE_HEAD'));
    console.log('This browser does not support Webauthn');
    return;
  }

  var rawPKData = document.forms['loginguard-method-edit'].querySelectorAll('input[name="pkRequest"]')[0].value;
  var publicKey = JSON.parse(atob(rawPKData)); // Convert the public key infomration to a format usable by the browser's credentials managemer

  publicKey.challenge = Uint8Array.from(window.atob(publicKey.challenge), function (c) {
    return c.charCodeAt(0);
  });
  publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), function (c) {
    return c.charCodeAt(0);
  });

  if (publicKey.excludeCredentials) {
    publicKey.excludeCredentials = publicKey.excludeCredentials.map(function (data) {
      return _objectSpread({}, data, {
        id: Uint8Array.from(window.atob(data.id), function (c) {
          return c.charCodeAt(0);
        })
      });
    });
  } // Ask the browser to prompt the user for their authenticator


  navigator.credentials.create({
    publicKey: publicKey
  }).then(function (data) {
    var publicKeyCredential = {
      id: data.id,
      type: data.type,
      rawId: akeeba.LoginGuard.webauthn.arrayToBase64String(new Uint8Array(data.rawId)),
      response: {
        clientDataJSON: akeeba.LoginGuard.webauthn.arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
        attestationObject: akeeba.LoginGuard.webauthn.arrayToBase64String(new Uint8Array(data.response.attestationObject))
      }
    }; // Store the WebAuthn reply

    document.getElementById('loginguard-method-code').value = btoa(JSON.stringify(publicKeyCredential)); // Submit the form

    document.forms['loginguard-method-edit'].submit();
  }, function (error) {
    // An error occurred: timeout, request to provide the authenticator refused, hardware / software error...
    akeeba.LoginGuard.webauthn.handle_error(error);
  });
};

akeeba.LoginGuard.webauthn.handle_error = function (message) {
  alert(message);
  console.log(message);
};

akeeba.LoginGuard.webauthn.validate = function () {
  // Make sure the browser supports Webauthn
  if (!('credentials' in navigator)) {
    alert(Joomla.JText._('PLG_LOGINGUARD_WEBAUTHN_ERR_NOTAVAILABLE_HEAD'));
    console.log('This browser does not support Webauthn');
    return;
  }

  console.log(akeeba.LoginGuard.webauthn.authData);
  var publicKey = akeeba.LoginGuard.webauthn.authData;

  if (!publicKey.challenge) {
    akeeba.LoginGuard.webauthn.handle_error(Joomla.JText._('PLG_LOGINGUARD_WEBAUTHN_ERR_NO_STORED_CREDENTIAL'));
    return;
  }

  publicKey.challenge = Uint8Array.from(window.atob(publicKey.challenge), function (c) {
    return c.charCodeAt(0);
  });
  publicKey.allowCredentials = publicKey.allowCredentials.map(function (data) {
    return _objectSpread({}, data, {
      id: Uint8Array.from(atob(data.id), function (c) {
        return c.charCodeAt(0);
      })
    });
  });
  navigator.credentials.get({
    publicKey: publicKey
  }).then(function (data) {
    var publicKeyCredential = {
      id: data.id,
      type: data.type,
      rawId: akeeba.LoginGuard.webauthn.arrayToBase64String(new Uint8Array(data.rawId)),
      response: {
        authenticatorData: akeeba.LoginGuard.webauthn.arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
        clientDataJSON: akeeba.LoginGuard.webauthn.arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
        signature: akeeba.LoginGuard.webauthn.arrayToBase64String(new Uint8Array(data.response.signature)),
        userHandle: data.response.userHandle ? akeeba.LoginGuard.webauthn.arrayToBase64String(new Uint8Array(data.response.userHandle)) : null
      }
    };
    document.getElementById('loginGuardCode').value = btoa(JSON.stringify(publicKeyCredential));
    document.forms['loginguard-captive-form'].submit();
  }, function (error) {
    // Example: timeout, interaction refused...
    console.log(error);
    akeeba.LoginGuard.webauthn.handle_error(error);
  });
};
//# sourceMappingURL=webauthn.js.map